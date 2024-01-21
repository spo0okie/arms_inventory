<?php


namespace app\helpers;


use app\components\assets\FieldsHelperAsset;
use Exception;
use kartik\date\DatePicker;
use kartik\markdown\MarkdownEditor;
use yii\base\Model;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii\web\JsExpression;


class FieldsHelper
{
	
	public const labelHintIcon='<i class="far fa-question-circle"></i>';
	
	/**
	 * Формирует поля для options=>[] для всплывающей подсказки у label
	 * @param string $title
	 * @param string $text
	 * @return array|string[]
	 */
	public static function toolTipOptions(string $title, string $text) {
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
	 * Вырезает и возвращает значение из массива
	 * @param      $options
	 * @param      $option
	 * @param null $default
	 * @return mixed|null
	 */
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
	
	public static function cutItemsHintsOptions(&$options,$attr) {
		
		$itemsHintsUrl=static::cutSingleOption($options,'itemsHintsUrl','');
		if (strlen($hintModel=static::cutSingleOption($options,'hintModel',''))) {
			$itemsHintsUrl=Url::to([Inflector::camel2id($hintModel).'/ttip','q'=>'dummy']);
		} elseif ($itemsHintsUrl=='auto') {
			//если выставлено в авто, вытаскиваем ссылку к тултипу исходя из имени поля
			$hintModelTokens=explode('_',$attr);
			if ($hintModelTokens[count($hintModelTokens)-1] == 'id' || $hintModelTokens[count($hintModelTokens)-1] == 'ids') {
				unset($hintModelTokens[count($hintModelTokens)-1]);
			}
			$hintModel=implode('-',$hintModelTokens);
			$itemsHintsUrl=Url::to([$hintModel.'/ttip','q'=>'dummy']);
		}
		
		return $itemsHintsUrl;
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
	 * @param Model  $model
	 * @param string     $attr
	 * @param array      $options
	 * @return mixed
	 * @throws Exception
	 */
	public static function Select2Field(ActiveForm $form, Model $model, string $attr, $options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		[$label,$labelOptions]=static::labelOption($model,$attr,$options);
		$pluginOptions=['allowClear' => true];
		
		$itemsHintsUrl=static::cutItemsHintsOptions($options,$attr);
		
		if (strlen($itemsHintsUrl)) {
			FieldsHelperAsset::register($form->view);
			$pluginOptions['templateResult']=new JsExpression('function(item){return formatSelect2ItemHint(item,"'.$itemsHintsUrl.'")}');
			$pluginOptions['templateSelection']=new JsExpression('function(item){return formatSelect2ItemHint(item,"'.$itemsHintsUrl.'")}');
			$pluginOptions['escapeMarkup']=new JsExpression('function(m) { return m; }');
			if (ArrayHelper::getTreeValue($options,['pluginOptions','multiple'],false))
				$pluginOptions['selectionAdapter']=new JsExpression('$.fn.select2.amd.require("QtippedMultipleSelectionAdapter")');
			else
				$pluginOptions['selectionAdapter']=new JsExpression('$.fn.select2.amd.require("QtippedSingleSelectionAdapter")');
			
		}
		return $form
			->field($model, $attr)
			->widget(Select2::class, ArrayHelper::recursiveOverride([
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
	 * @param Model $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function TextAutoresizeField(ActiveForm $form, Model $model, string $attr, $options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		[$label,$labelOptions]=static::labelOption($model,$attr,$options);
		$options= ArrayHelper::recursiveOverride([
			'lines'=>1
		],$options);

		$lines=$options['lines'];
		unset($options['lines']);
		
		$fieldId=strtolower(\yii\helpers\StringHelper::basename($model::className()).'-'.$attr);
		$form->view->registerJs("$('#$fieldId').autoResize({extraSpace:25,minLines:$lines}).trigger('change.dynSiz');");
		return $form->field($model, $attr)
			->textarea(['rows' => max($lines, count(explode("\n", $model->$attr)))])
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param Model $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function TextInputField(ActiveForm $form, Model $model, string $attr,$options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		[$label,$labelOptions]=static::labelOption($model,$attr,$options);
		return $form
			->field($model, $attr)
			->textInput(ArrayHelper::recursiveOverride([
				'maxlength'=>true
			],$options))
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param Model $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function CheckboxField(ActiveForm $form, Model $model, string $attr, $options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		[$label,$labelOptions]=static::labelOption($model,$attr,$options);
		return $form
			->field($model, $attr)->checkbox($options)
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param Model $model
	 * @param string $attr
	 * @param array $options
	 * @return mixed
	 */
	public static function CheckboxListField(ActiveForm $form, Model $model, string $attr, $options=[]) {
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		[$label,$labelOptions]=static::labelOption($model,$attr,$options);
		$options= ArrayHelper::recursiveOverride([
			'class'=>"card d-flex flex-wrap flex-row pt-2 pb-1",
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
	 * @param Model  $model
	 * @param string     $attr
	 * @param array      $options
	 * @return mixed
	 * @throws Exception
	 */
	public static function MarkdownField(ActiveForm $form, Model $model, string $attr,$options=[])
	{
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		[$label,$labelOptions]=static::labelOption($model,$attr,$options);
		return $form
			->field($model, $attr)
			->widget(MarkdownEditor::class,
				ArrayHelper::recursiveOverride([
					'showExport'=>false
				],$options)
			)
			->label($label,$labelOptions)
			->hint($hint,$hintOptions);
	}
	
	/**
	 * @param ActiveForm $form
	 * @param Model  $model
	 * @param string     $attr
	 * @param array      $options
	 * @return mixed
	 * @throws Exception
	 */
	public static function DateField(ActiveForm $form, Model $model, string $attr, $options=[])
	{
		$hint=static::cutHint($options);
		$hintOptions=static::cutHintOptions($options);
		
		[$label,$labelOptions]=static::labelOption($model,$attr,$options);
		return $form
			->field($model, $attr)
			->widget(DatePicker::class, ArrayHelper::recursiveOverride([
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