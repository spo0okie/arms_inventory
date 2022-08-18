<?php


namespace app\helpers;


use app\models\ArmsModel;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;


class FieldsHelper
{
	
	/**
	 * Формирует поля для options=>[] для всплывающей подсказки
	 */
	public static function toolTipOptions($title,$text) {
		return $text?[
			'qtip_ttip'=>'<div class="card">'.
				'<div class="card-header">'.$title.'</div>'.
				'<div class="card-body">'.
				'<p class="card-text">'.$text.'</p>'.
				'</div>'.
				'</div>',
			'qtip_side'=>'top,bottom,right,left',
			'qtip_theme'=>'tooltipster-shadow tooltipster-shadow-infobox'
		]:[];
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function Select2Field($form,$model,$attr,$options=[]) {
		return $form
			->field($model, $attr)
			->widget(Select2::classname(),\app\helpers\ArrayHelper::recursiveOverride([
				'options'=>[
					'placeholder'=>'Начните набирать для поиска',
				],
				'toggleAllSettings'=>['selectLabel' => null],
				'pluginOptions' => [
					'allowClear' => true
				]
			],$options)
			)->label(
				null,
				static::toolTipOptions(
					$model->getAttributeLabel($attr),
					$model->getAttributeHint($attr)
				)
			)
			->hint(false);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function TextAutoresizeField($form,$model,$attr,$options=[]) {
		$options=\app\helpers\ArrayHelper::recursiveOverride([
			'lines'=>1
		],$options);

		$lines=$options['lines'];
		unset($options['lines']);
		
		$fieldId=strtolower(\yii\helpers\StringHelper::basename($model::className()).'-'.$attr);
		$form->view->registerJs("$('#$fieldId').autoResize({extraSpace:25}).trigger('change.dynSiz');");
		return $form->field($model, $attr)
			->textarea(['rows' => max($lines, count(explode("\n", $model->$attr)))])
			->label(
				null,
				static::toolTipOptions(
					$model->getAttributeLabel($attr),
					$model->getAttributeHint($attr)
				)
			)
			->hint(false);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function TextInputField($form,$model,$attr,$options=[]) {
		return $form
			->field($model, $attr)
			->textInput(\app\helpers\ArrayHelper::recursiveOverride([
				'maxlength'=>true
			],$options))
			->label(
				null,
				static::toolTipOptions(
					$model->getAttributeLabel($attr),
					$model->getAttributeHint($attr)
				)
			)
			->hint(false);
		
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function CheckboxField($form,$model,$attr,$options=[]) {
		$options=\app\helpers\ArrayHelper::recursiveOverride([
			'class'=>"card d-flex flex-row pt-2 pb-1",
			'itemOptions'=>[
				'class'=>'p-2'
			],
		],$options);
		$items=$options['data'];
		unset ($options['data']);
		//https://www.yiiframework.com/doc/api/2.0/yii-helpers-basehtml#activeCheckboxList()-detail -->
		return $form
			->field($model, $attr)->checkboxList(
				$items,$options
			)
			->label(
				null,
				static::toolTipOptions(
					$model->getAttributeLabel($attr),
					$model->getAttributeHint($attr)
				)
			)
			->hint(false);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function MarkdownField($form,$model,$attr,$options=[])
	{
		return $form
			->field($model, $attr)
			->widget(\kartik\markdown\MarkdownEditor::className(),
				\app\helpers\ArrayHelper::recursiveOverride([
					'showExport'=>false
				],$options)
			)
			->label(
				null,
				static::toolTipOptions(
					$model->getAttributeLabel($attr),
					$model->getAttributeHint($attr)
				)
			)
			->hint(false);
	}
	
	
	
}