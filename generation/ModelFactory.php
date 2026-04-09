<?php

namespace app\generation;

use app\generation\context\AttributeContext;
use app\generation\context\GenerationContext;
use app\generation\exceptions\ModelGenerationException;
use app\models\base\ArmsModel;
use app\models\Soft;
use Random\RandomException;
use Throwable;
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
	public const defaultOptions = [
		'empty' => false,           // Генерировать пустые значения (nullable)
		'role' => null,             // Preset для связей (например 'pc' для Techs)
		'overrides' => [],          // Переопределение конкретных атрибутов
		'save' => true,             // Сохранять ли модель в БД
		'seed' => null,             // Seed для детерминизма (если null - случайный)
		'validateRetries' => 1,		// Кол-во попыток генерации при провале валидации
		'saveRetries' => 1,			// Кол-во попыток сохранения модели при ошибке сохранения
		'depth' => 0,				// Текущая глубина генерации (для внутренних нужд)
		'maxDepth' => 4,			// Максимальная глубина генерации связанных моделей (для предотвращения бесконечной рекурсии)
	];
	
	public static int $seed=1000;
	
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
		$options = array_merge(self::defaultOptions, $options);
				
		// Устанавливаем seed для детерминизма
		$baseSeed = $options['seed'] ?? ++static::$seed;
		
		$lastError = null;
		for ($i = 0; $i < $options['validateRetries']; $i++) {
			for ($j = 0; $j < $options['saveRetries']; $j++) {

				//новый seed на каждую попытку
				$options['seed'] = $baseSeed + $i*$options['saveRetries'] + $j;
				
				$result = self::createOnce($modelClass, $options);
				
				if ($result->isSuccess()) {
					
					$model = $result->model;
					if ($options['save']) {
						//отключаем рескан софта
						Soft::$disable_rescan = true;
						try {
							if ($model->save(false)) {
								return $model;
							}
						} catch (Throwable $e) {
							Yii::debug("ModelFactory: save retry {$i} for {$modelClass}", 'generation');
							$errors = $model->getErrors();
							$errors['exception'][] = $e->getMessage();
							$lastError = new ModelGenerationException(
								modelClass: $modelClass,
								stage: 'create/save',
								errors: $errors,
								seed: $options['seed'],
								previous: $e
							);
							continue;
						} finally {
							Soft::$disable_rescan = false;
						}
					} else { //no save
						return $model;
					}
				} else $lastError = $result->error;
			}
			Yii::debug("ModelFactory: validate retry {$i} for {$modelClass}", 'generation');
		}

		Yii::error("ModelFactory: не удалось создать модель {$modelClass}", 'generation');
		
		throw $lastError?
		$lastError:
		new ModelGenerationException(
			modelClass: $modelClass,
			stage: 'create',
			errors: ['Не удалось создать модель после всех попыток'],
			seed: $options['seed'],
		);
	}
	
	
	/**
	 * Генерирует связанную модель для атрибута-связи на основе схемы связей модели (linksSchema).
	 * @param ArmsModel        $model
	 * @param AttributeContext $attributeContext
	 * @return void
	 * @throws Exception
	 * @throws ModelGenerationException
	 * @throws RandomException
	 */
	protected static function generateRelation(ArmsModel $model, AttributeContext $attributeContext): void
	{
		$attribute=$attributeContext->attribute;
		$model = $attributeContext->model;
		$context=$attributeContext->generationContext;
		
		if (!$model->attributeIsLink($attribute)) {
			throw new ModelGenerationException(
				modelClass: get_class($model),
				stage: 'generateRelation',
				errors: ['Атрибут не является связью'],
				attribute: $attribute
			);
		}
		
		$class=$model->attributeLinkClass($attribute);	//с кем связь
		
		$canGet = $model->canGetProperty($attribute);	//аттрибут читаемый
		$canSet = $model->canSetProperty($attribute);	//аттрибут устанавливаемый?
		
		if ($canGet && !ArmsModel::attrIsEmpty($model,$attribute)) {
			return;	//атрибут уже заполнен, пропускаем
		}
		
		if (!$canSet) {
			throw new ModelGenerationException(
				modelClass: get_class($model),
				stage: 'generateRelation',
				errors: ['Атрибут связи не устанавливаемый'],
				attribute: $attribute,
				relatedClass: $class
			);
		}
		
		
		$isRequired = $model->getAttributeIsRequired($attribute);

		if (!is_subclass_of($class, ArmsModel::class)) {
			if ($isRequired) {
				throw new ModelGenerationException(
					modelClass: get_class($model),
					stage: 'applyRelations',
					errors: ['Связь с внешним классом не поддерживается генератором'],
					seed: $context->seed,
					attribute: $attribute,
					relatedClass: $class,
					depth: $context->depth
				);
			}
			return;
		}

		// Проверка на self-reference (связь модели на саму себя)
		// Структура связей НЕ ДОЛЖНА включать обязательные self-reference атрибуты
		if ($class === get_class($model) && $isRequired) {
			throw new ModelGenerationException(
				modelClass: get_class($model),
				stage: 'applyRelations',
				errors: ['Обязательная self-reference связь не поддерживается генератором. ' .
					'Убедитесь, что linksSchema для "' . $attribute . '" не помечен как required, ' .
					'или измените структуру модели.'],
				seed: $context->seed,
				attribute: $attribute,
				relatedClass: $class,
				depth: $context->depth
			);
		}
				
		//если мы уже на максимальной глубине
		//или генерируем пустую модель,
		//то заполняем только обязательные связи, остальные пропускаем
		if (
			(	//макс глубина
				($context->depth >= $context->maxDepth)
				||	//или пустая модель
				$attributeContext->empty
			) //не обязательный атрибут
			&& !$isRequired
		) {
			return;
		}
		
		// генерация many-to-many и reverse-ссылок
		$config = $model->attributeLinkSchema($attribute);
		$role = $config['role'] ?? null;
		
		try {
			$related = self::create(
				$class,
				[
					// Уникальный seed: базовый + хэш модели + хэш атрибута
					// Это гарантирует что разные модели с одинаковыми связями получат разные seed
					'seed' => $context->seed 
						+ crc32(get_class($model)) 
						+ crc32($attribute)
						+ $context->depth + 1,
					'empty' => $attributeContext->empty || $context->depth < $context->maxDepth,
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
			if (str_ends_with($attribute, '_ids')) {
				$model->$attribute = [$related->getPrimaryKey()];
			} else {
				$model->$attribute = $related->getPrimaryKey();
			}
			return;
		}
		
		throw new ModelGenerationException(
			modelClass: get_class($model),
			stage: 'applyRelation',
			errors: ['Не удалось создать модель для связи'],
			seed: $context->seed,
			attribute: $attribute,
			relatedClass: $class,
			depth: $context->depth
		);
	}

	protected static function findExistingModel(string $class): ?ActiveRecord
	{
		if (!is_subclass_of($class, ActiveRecord::class)) {
			return null;
		}

		$pk = $class::primaryKey();
		if (!empty($pk)) {
			$order = [];
			foreach ($pk as $col) {
				$order[$col] = SORT_ASC;
			}
			return $class::find()->orderBy($order)->one();
		}

		return $class::find()->one();
	}
	
	/**
	 * Сгенерировать значение для атрибута на основе его типа и правил валидации.
	 */
	public static function generateAttribute(AttributeContext $context): void {
		$attribute = $context->attribute;	// Атрибут, для которого генерируем значение

		//если есть rules для max и min передаем их в контекст для генератора
		self::resolveMaxLength($context);
		self::resolveMinLength($context);
		
		//если это связь - то генерим связь
		if ($context->model->attributeIsLink($attribute)) {
			self::generateRelation($context->model, $context);
			return;
		}
		
		// иначе получаем тип атрибута и генерируем значение
		try {
			$type = $context->model->getAttributeTypeClass($attribute);
			$context->model->$attribute = $type->generate($context);
		} catch (Throwable $e) {
			throw new ModelGenerationException(
				modelClass: get_class($context->model),
				stage: 'generateAttribute',
					seed: $context->generationContext->seed,
					attribute: $attribute,
					previous: $e
				);
			}
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
		$linksSchema = $model->getLinksSchema();
		/*$existAttributes = [];
		foreach ($model->rules() as $rule) {
			if (($rule[1] ?? null) !== 'exist') {
				continue;
			}
			foreach ((array)($rule[0] ?? []) as $attr) {
				$existAttributes[$attr] = true;
			}
		}*/

		$attributes=$model->safeAttributes();
		foreach ($attributes as $attribute) {
			
			// Пропускаем служебные атрибуты
			if (self::isSystemAttribute($attribute) && !$model->getAttributeIsRequired($attribute)) {
				continue;
			}

			// Пропускаем не устанавливаемые атрибуты
			if (!$model->canSetProperty($attribute)) {
				continue;
			}
			
			// Пропускаем атрибуты с явным значением в overrides
			if (isset($options['overrides'][$attribute])) {
				continue;
			}
			
			// Пропускаем readonly атрибуты
			if ($model->getAttributeIsReadOnly($attribute)) {
				continue;
			}
			
			// Создаём контекст атрибута
			$attrContext = new AttributeContext(
				attribute: $attribute,
				empty: $context->empty && !$model->getAttributeIsRequired($attribute),
				model: $model,
				generationContext: $context,
			);

			//генерируем значение согласно контекста
			self::generateAttribute($attrContext);
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
			empty: $options['empty'],
			seed: $options['seed'],
			depth: $options['depth'],
    		maxDepth: $options['maxDepth'],
		);

		Yii::debug("ModelFactory: seed={$context->seed} для " . get_class($model), 'generation');
		
		try {
			self::generateAttributes($model, $context, $options);    //заполняем атрибуты
			
			if (!empty($options['role'])) {
				self::applyPreset($model, $options['role']);
			}
			
			if (!empty($options['overrides'])) {
				self::applyOverrides($model, $options['overrides']);
			}

			// Вызываем метод модели для применения бизнес-правил генерации
			$model->afterGenerate($context, $options);
			
			// только validate, без retry
			if (!$model->validate()) {
				return new ModelGenerationResult(
					model: null,
					error: new ModelGenerationException(
						modelClass: $modelClass,
						stage: 'createOnce/validate',
						errors: $model->getErrors(),
						seed: $context->seed,
						values: array_intersect_key($model->getAttributes(),$model->getErrors())
					)
				);
			}
			return new ModelGenerationResult($model,null);
		} catch (ModelGenerationException $e) {
			return new ModelGenerationResult(null,$e);
		}
	}
	
	
	/*protected static function applyRelations(ArmsModel $model, GenerationContext $context, array $options): void
	{
		foreach ($model->getLinksSchema() as $attribute => $config) {
			
			// если уже задан (например preset или override)
			if (array_key_exists('overrides', $options) && array_key_exists($attribute, $options['overrides'])) {
				continue;
			}
			
			$attrContext = new AttributeContext(
				attribute: $attribute,
				empty: $context->empty,
				model: $model,
				generationContext: $context,
			);
			
			self::generateRelation($model, $attrContext);
			
		}
	}*/
	
	
	/**
	 * Добавляет в контекст атрибута min длину для строковых атрибутов на основе правил валидации модели.
	 */
	private static function resolveMinLength(AttributeContext $context): void
	{
		foreach ($context->model->rules() as $rule) {
			if (in_array($context->attribute, (array)$rule[0], true) && $rule[1] === 'string') {
				if (isset($rule['min'])) {
					$context->min = (int)$rule['min'];
				}
			}
		}
	}

	/**
	 * Добавляет в контекст атрибута max длину для строковых атрибутов на основе правил валидации модели.
	 */
	private static function resolveMaxLength(AttributeContext $context): void
	{
		foreach ($context->model->rules() as $rule) {
			if (in_array($context->attribute, (array)$rule[0], true) && $rule[1] === 'string') {
				if (isset($rule['max'])) {
					$context->max = (int)$rule['max'];
				}
			}
		}
	}


}







