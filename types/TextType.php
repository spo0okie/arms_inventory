<?php

namespace app\types;

use app\components\Forms\ActiveField;
use app\components\WikiTextWidget;
use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use kartik\markdown\Markdown;
use Yii;
use yii\helpers\Html;
use yii\web\View;

class TextType extends BaseType
{
	public static function name(): string
	{
		return 'text';
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->text();
	}

	/**
	 * Готовый HTML значения: по настройке params['textFields'] атрибут
	 * рендерится как markdown, dokuwiki или простой многострочный текст
	 * (ntext). Единственная точка этой логики: TextFieldWidget делегирует
	 * сюда. options['outerClass'] — css-класс обёртки (ntext/dokuwiki).
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$outerClass=$options['outerClass']??'';
		switch (ActiveField::textFieldType(get_class($model),$attribute)) {
			case 'markdown':
				return Markdown::convert($model->$attribute);
			case 'dokuwiki':
				return WikiTextWidget::widget([
					'model'=>$model,
					'field'=>$attribute,
					'outerClass'=>$outerClass,
				]);
			default:
				$text=Yii::$app->formatter->asNtext($model->$attribute);
				return $outerClass?
					Html::tag('div',$text,['class'=>$outerClass]):
					$text;
		}
	}

	public function apiSchema(): array
	{
		return ['type' => 'string'];
	}

	public function gridColumnClass(): ?string
	{
		return null;
	}

	public function samples(): array
	{
		return [];
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		$config = $context->generatorConfig();
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();
		
		$min = $context->min ?? 20;
		$max = $context->max ?? 100;
		$length = $rng->getInt($min, $max);

		$words = ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 
				  'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
				  'magna', 'aliqua', 'Ut', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
				  'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo'];
		
		$result = '';
		while (true) {
			$word = AttributeContext::pickRandomValue($words, $rng);
			$candidate = $result === '' ? $word : $result . ' ' . $word;
			if (strlen($candidate) > $length) {
				break;
			}
			$result = $candidate;
		}

		return $result;
	}
	
	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
		];
	}
}