<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип «выбор из фиксированного списка»: значение атрибута — ключ из
 * 'fieldList' в attributeData ( 'fieldList' => ['key'=>'подпись', ...] ).
 *
 * Ввод — dropdown по списку, вывод — подпись значения,
 * генерация — случайный ключ списка.
 */
class ChoiceType extends BaseType
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'choice';
	}

	/**
	 * Список значений атрибута из его fieldList
	 * @param ArmsModel $model
	 * @param string $attribute
	 * @return array
	 */
	protected function fieldList(ArmsModel $model, string $attribute): array
	{
		$list = $model->getAttributeData($attribute)['fieldList'] ?? [];
		if (is_callable($list)) $list = $list();
		return is_array($list) ? $list : [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		/** @var ArmsModel $model */
		$model = $field->model;
		$prompt = $model->getAttributeData($field->attribute)['placeholder'] ?? '';
		return $field->dropDownList(
			$this->fieldList($model, $field->attribute),
			['prompt' => $prompt]
		);
	}

	/**
	 * Выводим подпись значения из fieldList (неизвестное значение — как есть)
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute;
		$list = $this->fieldList($model, $attribute);
		return Html::encode((string)($list[$value] ?? $value));
	}

	/**
	 * {@inheritdoc}
	 */
	public function apiSchema(): array
	{
		//enum/пример дописывает декоратор fieldList в AttributeAnnotationModelTrait
		return ['type' => 'string'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function gridColumnClass(): ?string
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function samples(): array
	{
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		$keys = array_keys($this->fieldList($context->model, $context->attribute));
		if (!count($keys)) {
			return $context->isNullable() ? null : '';
		}

		return AttributeContext::pickRandomValue($keys, $context->randomizer());
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('in', [
				'range' => array_keys($this->fieldList($context->model, $context->attribute)),
			]),
		];
	}
}
