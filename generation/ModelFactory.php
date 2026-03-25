<?php

namespace app\generation;

use app\generation\context\AttributeContext;
use app\generation\context\GenerationContext;
use app\generation\exceptions\ModelGenerationException;
use app\generation\generators\GeneratorInterface;
use app\generation\generators\GeneratorResolver;
use app\models\base\ArmsModel;
use Random\RandomException;
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
	 * @param string $modelClass Класс модели или экземпляр
	 * @param array            $options Опции генерации
	 * @return ArmsModel|null Созданная модель или null при неудаче
	 * @throws Exception
	 * @throws RandomException
	 */
	public static function create(string $modelClass, array $options = []): ?ArmsModel
	{
		$options = array_merge([
			'empty' => false,           // Генерировать пустые значения (nullable)
			'role' => null,             // Preset для связей (например 'pc' для Techs)
			'overrides' => [],          // Переопределение конкретных атрибутов
			'save' => true,             // Сохранять ли модель в БД
			'seed' => null,             // Seed для детерминизма (если null - случайный)
			'validateRetries' => self::MAX_VALIDATE_RETRIES,
			'saveRetries' => self::MAX_SAVE_RETRIES,
		], $options);
				
		// Устанавливаем seed для детерминизма
		$baseSeed = $options['seed'] ?? random_int(1, 100000);
		
		$lastError = null;
		for ($i = 0; $i < $options['validateRetries']; $i++) {
			for ($j = 0; $j < $options['saveRetries']; $j++) {

				//новый seed на каждую попытку
				$options['seed'] = $baseSeed + $i*$options['saveRetries'] + $j;
				
				$result = self::createOnce($modelClass, $options);
				
				if ($result->isSuccess()) {
					
					$model = $result->model;
					if ($options['save']){ try {
						if ($model->save(false)) {
							return $model;
						}
					} catch (\Throwable) {
						Yii::debug("ModelFactory: save retry {$i} для {$modelClass}", 'generation');
						$lastError = new ModelGenerationException(
							modelClass: $modelClass,
							stage: 'create/save',
							errors: $model->getErrors(),
							seed: $options['seed']
						);
						continue;
					}} else { //no save
						return $model;
					}
				} else $lastError = $result->error;
			}
			Yii::debug("ModelFactory: validate retry {$i} для {$modelClass}", 'generation');
		}

		Yii::error("ModelFactory: не удалось создать модель {$modelClass}", 'generation');
		
		if ($lastError) throw $lastError;
		
		throw new ModelGenerationException(
			modelClass: $modelClass,
			stage: 'create',
			errors: ['Не удалось создать модель после всех попыток'],
			seed: $options['seed'],
		);
	}
	
	/**
	 * Сгенерировать атрибуты модели на основе их типов.
	 *
	 * @param ArmsModel $model Модель для заполнения
	 * @param GenerationContext $context Контекст генерации
	 * @param array $options Опции генерации (для overrides)
	 */
	protected static function generateAttributes(ArmsModel $model, GenerationContext $context, array $options): void
	{
		foreach ($model->safeAttributes() as $attribute) {
			
			// Пропускаем служебные атрибуты
			if (self::isSystemAttribute($attribute)) {
				continue;
			}
			
			// Пропускаем атрибуты с явным значением в overrides
			if (isset($options['overrides'][$attribute])) {
				continue;
			}

			// Пропускаем связи, т.к. они заполняются в другом месте
			if ($model->attributeIsLink($attribute)) {
				continue;
			}
			
			// Получаем данные атрибута
			$attributeData = $model->getAttributeData($attribute);
			if (!is_array($attributeData)) {
				throw new ModelGenerationException(
					modelClass: get_class($model),
					stage: 'generateAttributes/getAttributesData',
					errors: ['invalid attribute data: '.print_r($attributeData, true)],
					seed: $options['seed'],
				);
			}
			
			// Пропускаем readonly атрибуты
			if (!empty($attributeData['readOnly'])) {
				continue;
			}
			
			// Создаём контекст атрибута
			$attrContext = new AttributeContext(
				attribute: $attribute,
				attributeData: $attributeData,
				model: $model,
				generationContext: $context,
			);
			
			// Получаем генератор и генерируем значение
			try {
				$generator = GeneratorResolver::resolve($attrContext);
				$model->$attribute = $generator->generate($attrContext);
			} catch (\Throwable $e) {
				throw new ModelGenerationException(
					modelClass: get_class($model),
					stage: 'generateAttributes/generate',
					seed: $context->seed,
					attribute: $attribute,
					previous: $e
				);
			}
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
		
	private static function createOnce(string $modelClass, array $options): ModelGenerationResult
	{
		$model = Yii::createObject(['class' => $modelClass]);
		
		if (!$model instanceof ArmsModel) {
			throw new ModelGenerationException(
				modelClass: $modelClass,
				stage: 'createOnce::createObject',
				errors:['ModelFactory работает только с ArmsModel']
			);
		}

		$context = new GenerationContext(
			empty: $options['empty'] ?? false,
			seed: $options['seed'] ?? random_int(1, 100000),
			depth: $options['depth'] ?? 0,
    		maxDepth: $options['maxDepth'] ?? 2,
		);

		Yii::debug("ModelFactory: seed={$context->seed} для " . get_class($model), 'generation');
		
		try {
			self::generateAttributes($model, $context, $options);    //заполняем атрибуты
			
			self::applyRelations($model, $context, $options);          //заполняем связи
			
			if (!empty($options['role'])) {
				self::applyPreset($model, $options['role']);
			}
			
			if (!empty($options['overrides'])) {
				self::applyOverrides($model, $options['overrides']);
			}
			
			// только validate, без retry
			if (!$model->validate()) {
				return new ModelGenerationResult(
					model: null,
					error: new ModelGenerationException(
						modelClass: $modelClass,
						stage: 'createOnce/validate',
						errors: $model->getErrors(),
						seed: $context->seed,
					)
				);
			}
			return new ModelGenerationResult($model,null);
		} catch (ModelGenerationException $e) {
			return new ModelGenerationResult(null,$e);
		}
	}

	protected static function applyRelations(ArmsModel $model, GenerationContext $context, array $options): void
	{
		if (!method_exists($model, 'getLinksSchema')) {
			return;
		}

		if ($context->depth >= $context->maxDepth) {
			return;
		}

		foreach ($model->getLinksSchema() as $attribute => $config) {
			
			// many-to-many и reverse-ссылки пока не генерируем
			if (str_ends_with($attribute, '_ids')) {
				continue;
			}

			// если уже задан (например preset или override)
			if (array_key_exists('overrides', $options) && array_key_exists($attribute, $options['overrides'])) {
				continue;
			}

			if ($model->$attribute !== null) {
				continue;
			}

			if (!is_array($config)) {
				$config = [$config];
			}

			$class = $config['class'] ?? ($config[0] ?? null);
			if (!$class) {
				continue;
			}
			
			$skipEmpty = $context->empty								// если собираем пустую модель
				&& !$model->getAttributeIsRequired($attribute)			// связь не обязательна
				&& $model->getAttributeIsNullable($attribute);			// и может быть null
			if ($skipEmpty) {
				continue;
			}

			$role = $config['role'] ?? null;
			
			try {
				$related = self::create(
					$class,
					[
						'seed' => $context->seed + crc32($attribute),
						'empty' => $context->empty,
						'role' => $role,
						'save' => true,
						// увеличиваем глубину
						'depth' => $context->depth + 1,
						'maxDepth' => $context->maxDepth,
					]
				);
			} catch (ModelGenerationException $e) {
				throw new ModelGenerationException(
					modelClass: get_class($model),
					stage: 'applyRelations',
					seed: $context->seed,
					attribute: $attribute,
					relatedClass: $class,
					depth: $context->depth,
					previous: $e
				);
			}
			
			if ($related) {
				$model->$attribute = $related->getPrimaryKey();
			} else {
				throw new ModelGenerationException(
					modelClass: get_class($model),
					stage: 'applyRelations',
					errors: ['Failed to create relation model'],
					seed: $context->seed,
					attribute: $attribute,
					relatedClass: $class,
					depth: $context->depth
				);
			}
		}
	}
}
