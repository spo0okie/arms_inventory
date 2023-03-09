<?php


namespace app\helpers;


use app\components\assets\FieldsHelperAsset;
use app\models\ArmsModel;
use kartik\date\DatePicker;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;


class FieldsHelper
{
	
	public const labelHintIcon='<i class="far fa-question-circle"></i>';
	
	/**
	 * Формирует поля для options=>[] для всплывающей подсказки у label
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
	
	public static function cutSingleOption(&$options,$option,$default=null) {
		if (!isset($options[$option])) return $default;
		$result=$options[$option];
		unset($options[$option]);
		return $result;
	}
	
	public static function cutHint(&$options) {
		return static::cutSingleOption($options,'classicHint',false);
	}
	
	public static function cutHintOptions(&$options) {
		return static::cutSingleOption($options,'classicHintOptions',[]);
	}
	
	public static function cutItemsHintsOptions(&$options) {
		return static::cutSingleOption($options,'itemsHintsUrl','');
	}
	
	public static function labelOption($model,$attr,$options) {
		$label=static::cutSingleOption($options,'label');
		$hint=static::cutSingleOption($options,'hint');
		if (empty($label)) $label=$model->getAttributeLabel($attr);
		if (empty($hint)) $hint=$model->getAttributeHint($attr);
		if (!$label) return null;
		if (!$hint) return [$label,[]];
		return [
			$label.' '.static::labelHintIcon,
			static::toolTipOptions($label,$hint)
		];
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function Select2Field($form,$model,$attr,$options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		list($label,$labelOptions)=static::labelOption($model,$attr,$options);
		$itemsHintsUrl=static::cutItemsHintsOptions($options);
		$pluginOptions=['allowClear' => true];
		if (strlen($itemsHintsUrl)) {
			FieldsHelperAsset::register($form->view);
			$pluginOptions['templateResult']=new JsExpression('function(item){return formatSelect2ItemHint(item,"'.$itemsHintsUrl.'")}');
			$pluginOptions['templateSelection']=new JsExpression('function(item){return formatSelect2ItemHint(item,"'.$itemsHintsUrl.'")}');
			$pluginOptions['escapeMarkup']=new JsExpression('function(m) { return m; }');
			if (\app\helpers\ArrayHelper::getTreeValue($options,['pluginOptions','multiple'],false))
				$pluginOptions['selectionAdapter']=new JsExpression('$.fn.select2.amd.require("QtippedMultipleSelectionAdapter")');
			else
				$pluginOptions['selectionAdapter']=new JsExpression('$.fn.select2.amd.require("QtippedSingleSelectionAdapter")');
			
		}
		return $form
			->field($model, $attr)
			->widget(Select2::classname(),\app\helpers\ArrayHelper::recursiveOverride([
				'options'=>[
					'placeholder'=>'Начните набирать для поиска',
				],
				'toggleAllSettings'=>['selectLabel' => null],
				'pluginOptions' => $pluginOptions
			],$options)
			)
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function TextAutoresizeField($form,$model,$attr,$options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		list($label,$labelOptions)=static::labelOption($model,$attr,$options);
		$options=\app\helpers\ArrayHelper::recursiveOverride([
			'lines'=>1
		],$options);

		$lines=$options['lines'];
		unset($options['lines']);
		
		$fieldId=strtolower(\yii\helpers\StringHelper::basename($model::className()).'-'.$attr);
		$form->view->registerJs("$('#$fieldId').autoResize({extraSpace:25}).trigger('change.dynSiz');");
		return $form->field($model, $attr)
			->textarea(['rows' => max($lines, count(explode("\n", $model->$attr)))])
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function TextInputField($form,$model,$attr,$options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		list($label,$labelOptions)=static::labelOption($model,$attr,$options);
		return $form
			->field($model, $attr)
			->textInput(\app\helpers\ArrayHelper::recursiveOverride([
				'maxlength'=>true
			],$options))
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function CheckboxField($form,$model,$attr,$options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		list($label,$labelOptions)=static::labelOption($model,$attr,$options);
		return $form
			->field($model, $attr)->checkbox()
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function CheckboxListField($form,$model,$attr,$options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		list($label,$labelOptions)=static::labelOption($model,$attr,$options);
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
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
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
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		list($label,$labelOptions)=static::labelOption($model,$attr,$options);
		return $form
			->field($model, $attr)
			->widget(\kartik\markdown\MarkdownEditor::className(),
				\app\helpers\ArrayHelper::recursiveOverride([
					'showExport'=>false
				],$options)
			)
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param ArmsModel $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function DateField($form,$model,$attr,$options=[])
	{
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		
		list($label,$labelOptions)=static::labelOption($model,$attr,$options);
		return $form
			->field($model, $attr)
			->widget(DatePicker::classname(), \app\helpers\ArrayHelper::recursiveOverride([
				'options' => ['placeholder' => 'Введите дату ...'],
				'pluginOptions' => [
					'autoclose'=>true,
					'weekStart' => '1',
					'format' => 'yyyy-mm-dd'
				]
			],$options))
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	
	
}