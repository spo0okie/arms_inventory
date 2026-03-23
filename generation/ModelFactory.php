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
			'role' => null,             // Preset для связей (например 'pc' для Techs)
			'overrides' => [],          // Переопределение конкретных атрибутов
			'save' => true,             // Сохранять ли модель в БД
			'seed' => null,             // Seed для детерминизма (если null - случайный)
			'validateRetries' => self::MAX_VALIDATE_RETRIES,
			'saveRetries' => self::MAX_SAVE_RETRIES,
		], $options);
				
		// Устанавливаем seed для детерминизма
		$baseSeed = $options['seed'] ?? random_int(1, 100000);
		
		for ($i = 0; $i < $options['validateRetries']; $i++) {
			for ($j = 0; $j < $options['saveRetries']; $j++) {

				//новый seed на каждую попытку
				$options['seed'] = $baseSeed + $i*$options['saveRetries'] + $j;

				$model = self::createOnce($modelClass, $config, $options);

				if ($model !== null) {

					if ($options['save']) {
						try {
							if ($model->save(false)) {
								return $model;
							}
						} catch (\Throwable) {
							Yii::debug("ModelFactory: save retry {$i} для {$modelClass}", 'generation');
							continue;
						}
					} else { //no save
						return $model;
					}
				}

			}
			Yii::debug("ModelFactory: validate retry {$i} для {$modelClass}", 'generation');
		}

		Yii::error("ModelFactory: не удалось создать модель {$modelClass}", 'generation');

		return null;
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
			
			// Получаем данные атрибута
			$attributeData = $model->getAttributeData($attribute);
			if ($attributeData === null) {
				continue;
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
			$generator = GeneratorResolver::resolve($attrContext);
			$model->$attribute = $generator->generate($attrContext);
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
		
	private static function createOnce(string|ArmsModel $modelClass, array $config, array $options): ?ArmsModel
	{
		$model = Yii::createObject(array_merge(['class' => $modelClass], $config));

		if (!$model instanceof ArmsModel) {
			throw new Exception('ModelFactory работает только с ArmsModel');
		}

		$context = new GenerationContext(
			empty: $options['empty'] ?? false,
			seed: $options['seed'] ?? random_int(1, 100000),
			depth: $options['depth'] ?? 0,
    		maxDepth: $options['maxDepth'] ?? 2,
		);

		Yii::debug("ModelFactory: seed={$context->seed} для " . get_class($model), 'generation');

		self::generateAttributes($model, $context, $options);	//заполняем атрибуты

		self::applyRelations($model, $context);					//заполняем связи

		if (!empty($options['role'])) {
			self::applyPreset($model, $options['role']);
		}

		if (!empty($options['overrides'])) {
			self::applyOverrides($model, $options['overrides']);
		}

		// только validate, без retry
		if (!$model->validate()) {
			return null;
		}

		return $model;
	}

	protected static function applyRelations(ArmsModel $model, GenerationContext $context): void
{
    if (!method_exists($model, 'linksSchema')) {
        return;
    }

    if ($context->depth >= $context->maxDepth) {
        return;
    }

    foreach ($model->linksSchema() as $attribute => $config) {

        // если уже задан (например preset или override)
        if (!empty($model->$attribute)) {
            continue;
        }

        $class = $config['class'] ?? null;
        if (!$class) {
            continue;
        }
		
		$empty = $config['empty'] //если мы собираем пустую модель
			&& !$model->getAttributeIsRequired($attribute)		//и эта связь не обязательная
			&& $model->getAttributeIsNullable($attribute);		//и может быть null
		if ($empty) {
			continue;											//пропускаем ее
		}		

        $role = $config['role'] ?? null;

        $related = self::create(
            $class,
            [],
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

        if ($related) {
            $model->$attribute = $related->primaryKey;
        }
    }
}
}
