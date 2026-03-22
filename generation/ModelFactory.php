<?php

namespace app\generation;

use app\generation\generators\GeneratorInterface;
use app\generation\generators\GeneratorResolver;
use app\models\base\ArmsModel;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * Фабрика моделей для генеративного создания экземпляров ARMS моделей.
 * 
 * Назначение:
 * - Создание ВАЛИДНЫХ моделей с автоматически сгенерированными атрибутами для acceptance-тестов
 * - Возможность генерации модели в конкретной роли:
 * 	 - Techs/ARM
 *   - Schedules/ScheduledAccess
 *   - и т.д
 * - Возможность переопределения конкретных атрибутов
 * - Возможность указания задачи генерации:
 * 	 - Создание модели с пустыми атрибутами
 *   - Создание модели с заполненными атрибутами и связями (на указанную глубину)
 * 
 * Pipeline:
 * 1. Создать модель model
 * 2. Заполнить attributes (AttributeGenerator)
 * 3. Применить presets (role), overrides
 * 4. validate
 * 5. retry if fail
 * 6. save
 * 7. retry if fail
 */
class ModelFactory
{
	/**
	 * Максимальное количество попыток валидации
	 */
	public const MAX_VALIDATE_RETRIES = 7;
	
	/**
	 * Максимальное количество попыток сохранения
	 */
	public const MAX_SAVE_RETRIES = 7;
	
	/**
	 * Создать модель с автоматически сгенерированными атрибутами.
	 *
	 * @param string|ActiveRecord $modelClass Класс модели или экземпляр
	 * @param array $config Конфигурация модели (как для Yii2::createObject)
	 * @param array $options Опции генерации
	 * @return ActiveRecord|null Созданная модель или null при неудаче
	 * @throws Exception
	 */
	public static function create(string|ArmsModel $modelClass, array $config = [], array $options = []): ?ArmsModel
	{
		$options = array_merge([
			'empty' => false,           // Генерировать пустые значения (nullable)
			'role' => null,            // Preset для связей (например 'pc' для Techs)
			'overrides' => [],          // Переопределение конкретных атрибутов
			'save' => true,             // Сохранять ли модель в БД
			'validateRetries' => self::MAX_VALIDATE_RETRIES,
			'saveRetries' => self::MAX_SAVE_RETRIES,
		], $options);
		
		// Создаём экземпляр модели
		$model = Yii::createObject(array_merge(['class' => $modelClass], $config));
		
		if (!$model instanceof ArmsModel) {
			throw new Exception('ModelFactory работает только с моделями наследующими ArmsModel');
		}
		
		// Генерируем атрибуты
		self::generateAttributes($model, $options);
		
		// Применяем preset (роль)
		if (!empty($options['role'])) {
			self::applyPreset($model, $options['role']);
		}
		
		// Применяем переопределения
		if (!empty($options['overrides'])) {
			self::applyOverrides($model, $options['overrides']);
		}
		
		// Валидация с retry
		$validateSuccess = self::validateWithRetry($model, $options['validateRetries']);
		if (!$validateSuccess) {
			Yii::error('ModelFactory: не удалось создать валидную модель ' . get_class($model), 'generation');
			return null;
		}
		
		// Сохранение с retry
		if ($options['save']) {
			$saveSuccess = self::saveWithRetry($model, $options['saveRetries']);
			if (!$saveSuccess) {
				Yii::error('ModelFactory: не удалось сохранить модель ' . get_class($model), 'generation');
				return null;
			}
		}
		
		return $model;
	}
	
	/**
	 * Сгенерировать атрибуты модели на основе их типов.
	 *
	 * @param ArmsModel $model Модель для заполнения
	 * @param array $options Опции генерации
	 */
	protected static function generateAttributes(ArmsModel $model, array $options): void
	{
		$attributeData = $model->attributeData();
		
		foreach ($attributeData as $attribute => $data) {
			// Пропускаем служебные атрибуты
			if (self::isSystemAttribute($attribute)) {
				continue;
			}
			
			// Пропускаем атрибуты с явным значением в overrides
			if (isset($options['overrides'][$attribute])) {
				continue;
			}
			
			// Получаем данные атрибута
			$attrData = $model->getAttributeData($attribute);
			if ($attrData === null) {
				continue;
			}
			
			// Пропускаем readonly атрибуты
			if (isset($attrData['readOnly']) && $attrData['readOnly']) {
				continue;
			}
			
			// Генерируем значение
			$value = self::generateAttributeValue($model, $attribute, $attrData, $options);
			$model->$attribute = $value;
		}
	}
	
	/**
	 * Определить, является ли атрибут служебным.
	 *
	 * @param string $attribute Имя атрибута
	 * @return bool
	 */
	protected static function isSystemAttribute(string $attribute): bool
	{
		// Системные атрибуты, которые не нужно генерировать
		$systemAttributes = [
			'id',
			'updated_at',
			'updated_by',
			'created_at',
			'created_by',
		];
		
		return in_array($attribute, $systemAttributes);
	}
	
	/**
	 * Сгенерировать значение для одного атрибута.
	 *
	 * @param ArmsModel $model Модель
	 * @param string $attribute Имя атрибута
	 * @param array $attrData Метаданные атрибута
	 * @param array $options Опции генерации
	 * @return mixed Сгенерированное значение
	 */
	protected static function generateAttributeValue(ArmsModel $model, string $attribute, array $attrData, array $options): mixed
	{
		// Определяем параметры для генератора
		$params = [
			'empty' => $options['empty'],
			'nullable' => $model->getAttributeIsNullable($attribute),
		];
		
		// Получаем класс генератора
		try {
			$generatorClass = GeneratorResolver::resolve($model, $attribute);
		} catch (\Exception $e) {
			Yii::warning("ModelFactory: не найден генератор для атрибута {$attribute}: " . $e->getMessage(), 'generation');
			return null;
		}
		
		// Генерируем значение
		/** @var GeneratorInterface $generatorClass */
		return $generatorClass::generate($attrData);
	}

	
	/**
	 * Применить preset (роль) к модели.
	 * 
	 * Позволяет создать связанные модели для FK-полей.
	 * Например, для Techs с preset='pc' создастся TechType->TechModel->Techs.
	 *
	 * @param ArmsModel $model Модель
	 * @param string $role Имя preset
	 */
	protected static function applyPreset(ArmsModel $model, string $role): void
	{
		// Проверяем есть ли статический метод roles() у модели
		if (!method_exists($model, 'roles')) {
			return;
		}
		
		$roles = $model::roles();
		if (!isset($roles[$role])) {
			return;
		}
		
		$preset = $roles[$role];
		if (is_callable($preset)) {
			$preset($model);
		}
	}
	
	/**
	 * Применить переопределения атрибутов.
	 *
	 * @param ArmsModel $model Модель
	 * @param array $overrides Атрибуты для переопределения
	 */
	protected static function applyOverrides(ArmsModel $model, array $overrides): void
	{
		foreach ($overrides as $attribute => $value) {
			$model->$attribute = $value;
		}
	}
	
	/**
	 * Валидация с механизмом повторных попыток.
	 *
	 * @param ArmsModel $model Модель
	 * @param int $maxRetries Максимум попыток
	 * @return bool Успех валидации
	 */
	protected static function validateWithRetry(ArmsModel $model, int $maxRetries): bool
	{
		for ($i = 0; $i < $maxRetries; $i++) {
			if ($model->validate()) {
				return true;
			}
			
			// Пробуем исправить ошибки валидации
			if (!$model->hasErrors()) {
				continue;
			}
			
			$errors = $model->getErrors();
			Yii::debug("ModelFactory: попытка валидации $i неудачна, ошибки: " . json_encode($errors), 'generation');
			
			// Пробуем исправить типичные проблемы
			self::fixValidationErrors($model, $errors);
		}
		
		return $model->validate();
	}
	
	/**
	 * Попытаться исправить типичные ошибки валидации.
	 *
	 * @param ArmsModel $model Модель
	 * @param array $errors Ошибки валидации
	 */
	protected static function fixValidationErrors(ArmsModel $model, array $errors): void
	{
		foreach ($errors as $attribute => $messages) {
			foreach ($messages as $message) {
				// Пробуем перегенерировать проблемный атрибут
				try {
					$attrData = $model->getAttributeData($attribute);
					if ($attrData === null) {
						continue;
					}
					
					$generatorClass = GeneratorResolver::resolve($model, $attribute);
					$params = [
						'empty' => false,
						'nullable' => $model->getAttributeIsNullable($attribute),
					];
					
					/** @var GeneratorInterface $generator */
					$generator = new $generatorClass();
					$newValue = $generator::generate($params);
					$model->$attribute = $newValue;
					
				} catch (\Exception $e) {
					// Игнорируем ошибки при исправлении
				}
			}
		}
		
		// Очищаем ошибки для повторной валидации
		$model->clearErrors();
	}
	
	/**
	 * Сохранение с механизмом повторных попыток.
	 *
	 * @param ArmsModel $model Модель
	 * @param int $maxRetries Максимум попыток
	 * @return bool Успех сохранения
	 */
	protected static function saveWithRetry(ArmsModel $model, int $maxRetries): bool
	{
		for ($i = 0; $i < $maxRetries; $i++) {
			try {
				if ($model->save(false)) { // false = без валидации (уже провалидировали)
					return true;
				}
			} catch (\Throwable $e) {
				Yii::warning("ModelFactory: ошибка сохранения попытка $i: " . $e->getMessage(), 'generation');
			}
			
			// При ошибке сохранения пробуем ещё раз с валидацией
			$model->clearErrors();
			if (!$model->validate()) {
				self::fixValidationErrors($model, $model->getErrors());
			}
		}
		
		return false;
	}
}
