<?php

namespace app\models;

use app\generation\context\GenerationContext;
use app\generation\ModelFactory;
use app\helpers\SoftwareVersionFilter;
use app\models\base\ArmsModel;
use app\types\StringType;
use app\types\TextType;
use Yii;

/** Условие назначения тега установке конкретного продукта.
 * @property int $soft_id
 * @property int $tag_id
 * @property string $format
 * @property string $conditions
 * @property Tags $tag
 */
class SoftVersionTagRule extends ArmsModel
{
	/** Таблица правил продукта. */
	public static function tableName()
	{
		return 'soft_version_tag_rules';
	}

	/** Описывает правило в справочнике моделей. */
	public static function modelDescription(): string
	{
		return 'Правило назначает тег обнаруженной установке ПО, если все условия совпали на версии одного пакета.';
	}

	/** Задаёт подписи, подсказки и типы полей для справки и API. */
	public function attributeData()
	{
		return array_merge(parent::attributeData(), [
			'soft_id'=>['Программный продукт', 'hint'=>'Карточка ПО, к установкам которого применяется правило.'],
			'tag_id'=>['Тег', 'hint'=>'Неархивный тег, который показывается у подходящей установки.'],
			'format'=>[
				'Формат версии',
				'hint'=>'Выберите формат разбора версии пакета: он определяет доступные поля для сравнения.',
				'typeClass'=>StringType::class,
			],
			'conditions'=>[
				'Условия',
				'hint'=>'Задайте сравнения для полей версии. Все условия должны выполниться на версии одного пакета.',
				// В БД хранится JSON-строка; структуру проверяет валидатор правила.
				'typeClass'=>TextType::class,
			],
		]);
	}

	/** Проверяет тег, формат и условия до записи. */
	public function rules()
	{
		return [
			[['soft_id','tag_id','format','conditions'],'required'],
			[['soft_id','tag_id'],'integer'],
			['tag_id','exist','targetClass'=>Tags::class,'targetAttribute'=>'id','filter'=>['archived'=>0]],
			['format','in','range'=>array_keys(Yii::$app->params['soft.version_formats']),'strict'=>true],
			['conditions','validateConditions'],
		];
	}

	/** Заполняет правило допустимыми значениями при генерации демоданных. */
	public function afterGenerate(GenerationContext $context, array $options = []): void
	{
		parent::afterGenerate($context, $options);
		$overrides = $options['overrides'] ?? [];
		$formats = Yii::$app->params['soft.version_formats'];
		if (!array_key_exists('format', $overrides)) $this->format = array_key_first($formats);
		if (!array_key_exists('conditions', $overrides)) {
			// Имена полей зависят от выбранного формата, поэтому случайная строка не подходит.
			$field = array_key_first($formats[$this->format]['fields'] ?? []);
			$this->conditions = $field === null ? '{}' : json_encode([$field=>['op'=>'=', 'value'=>'1']], JSON_THROW_ON_ERROR);
		}
		if (!array_key_exists('soft_id', $overrides)) {
			$this->soft_id = Soft::find()->select('id')->orderBy('id')->scalar()
				?: ModelFactory::create(Soft::class, ['seed'=>$context->seed])?->id;
		}
		if (!array_key_exists('tag_id', $overrides)) {
			$this->tag_id = Tags::find()->select('id')->where(['archived'=>0])->orderBy('id')->scalar()
				?: ModelFactory::create(Tags::class, ['seed'=>$context->seed, 'overrides'=>['archived'=>0]])?->id;
		}
	}

	/** Тег для вывода на вкладке обнаружений. */
	public function getTag()
	{
		return $this->hasOne(Tags::class, ['id'=>'tag_id']);
	}

	/** Декодирует условия, сохраняя различие между пустым набором и ошибкой. */
	public function getConditionsArray(): ?array
	{
		$data = json_decode($this->conditions ?? '', true);
		return is_array($data) ? $data : null;
	}

	/** Возвращает ошибку в том числе для правил, чей формат изменился после записи. */
	public function conditionError(): ?string
	{
		$format = is_string($this->format) ? (Yii::$app->params['soft.version_formats'][$this->format] ?? null) : null;
		if (!$format) return 'Выберите доступный формат версии.';
		$data = $this->getConditionsArray();
		return $data === null ? 'Некорректные условия.' : SoftwareVersionFilter::error($format, $data);
	}

	/** Сообщает об ошибке формы при сохранении правила. */
	public function validateConditions()
	{
		if ($error = $this->conditionError()) $this->addError('conditions', $error);
	}

	/** Проверяет все условия на одной версии, пропуская устаревшие правила. */
	public function matches(string $version): bool
	{
		return !$this->conditionError() && SoftwareVersionFilter::matches(
			$version, Yii::$app->params['soft.version_formats'][$this->format], $this->getConditionsArray()
		);
	}
}
