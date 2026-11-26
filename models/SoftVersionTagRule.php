<?php

namespace app\models;

use app\helpers\SoftwareVersionFilter;
use app\models\base\ArmsModel;
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
