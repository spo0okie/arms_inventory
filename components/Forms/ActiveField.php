<?php

namespace app\components\Forms;

use app\components\formInputs\DokuWikiEditor;
use app\components\formInputs\TextAutoResizeWidget;
use app\components\Forms\assets\Select2FieldAsset;
use app\controllers\ArmsBaseController;
use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use kartik\datecontrol\DateControl;
use kartik\markdown\MarkdownEditor;
use kartik\select2\Select2;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 * @property ArmsModel $model
 */
class ActiveField extends \yii\bootstrap5\ActiveField
{
	/**
	 * @var string Название поля для label
	 */
	private $labelText;
	
	/**
	 * @var string Подсказка для нашего label
	 */
	private $hintText;
	
	/**
	 * Наша иконочка в label - признак, что есть подсказка
	 */
	public const labelHintIcon = '<i class="far fa-question-circle"></i>';
	
	
	/**
	 * Формирует поля для options=>[] для всплывающей подсказки у label
	 * @param string $title
	 * @param string $text
	 * @return array|string[]
	 */
	public static function hintTipOptions(string $title, string $text)
	{
		return $text ? [
			'qtip_ttip' => '<div class="card">' .
				'<div class="card-header">' . $title . '</div>' .
				'<div class="card-body">' .
				'<p class="card-text">' . $text . '</p>' .
				'</div>' .
				'</div>',
			'qtip_side' => 'top,bottom,right,left',
			'qtip_theme' => 'tooltipster-shadow tooltipster-shadow-infobox',
		] : [];
	}
	
	
	/**
	 * В отличие от родительского метода тут не происходит рендер
	 * Только фиксируем (в $this->labelText) какой текст надо использовать для label
	 * @param $label
	 * @param $options
	 * @return $this|ActiveField
	 */
	public function label($label = null, $options = []): \yii\bootstrap5\ActiveField
	{
		if ($label === false) {
			$this->labelText = '';
			$this->enableLabel = false;
			return $this;
		}
		
		if (is_null($label))
			$this->labelText = $this->model->getAttributeLabel($this->attribute);
		else
			$this->labelText = $label;
		
		$this->enableLabel = true;
		$this->labelOptions = array_merge($this->labelOptions, $options);
		
		return $this;
	}
	
	/**
	 * В отличие от родительского метода тут не происходит рендер
	 * Только фиксируем (в $this->parts['{hint}']), какой текст надо использовать для hint
	 * @param $hint
	 * @param $options
	 * @return $this|ActiveField
	 */
	public function hint($hint = null, $options = [])
	{
		if ($hint === false) {
			$this->hintText = '';
			return $this;
		}
		
		if (is_null($hint))
			$this->hintText = $this->model->getAttributeHint($this->attribute);
		else
			$this->hintText = $hint;
		
		
		//Если честно, непонятно что с этим делать. Как-то потом учесть может?
		$this->hintOptions = array_merge($this->hintOptions, $options);
		
		return $this;
	}
	
	/**
	 * Метод, чтобы прикрутить классическую подсказку после input
	 * @param $hint
	 * @param $options
	 * @return ActiveField
	 */
	public function classicHint ($hint = false, $options = [])
	{
		if ($hint === null) {
			$hint=($this->model->getAttributeData($this->attribute)['classicHint'])??false;
		}
		return parent::hint($hint,$options);
	}
	
	/**
	 * Это вызывается при рендере input.
	 * Тут мы всовываем всю магию по засовыванию подсказок в label
	 * @return $this
	 */
	protected function renderLabelParts(string $label = null, array $options = []):void
	{
		//если у нас не вызывался метод label() или hint(), то вызываем их
		if (is_null($this->labelText)) $this->label();
		if (is_null($this->hintText)) $this->hint();
		
		//нет label - не рендерим его
		if (!$this->labelText) {
			$this->parts['{label}'] = '';
			$this->parts['{beginLabel}'] = '';
			$this->parts['{labelTitle}'] = '';
			$this->parts['{endLabel}'] = '';
			return;
		}
		
		$label = $this->labelText;
		//если у нас есть подсказка, то
		if ($this->hintText) {
			//добавляем к label иконку
			$label .= ' '.static::labelHintIcon;
			
			//добавляем к options наш tooltip с подсказкой
			$this->labelOptions = array_merge(
				$this->labelOptions,
				static::hintTipOptions($label, $this->hintText)
			);
			
		}
		
		
		//рендерим части для bootsrap5 шаблона через родительский метод
		//(они используются только при специальных template)
		parent::renderLabelParts($label,$this->labelOptions);
		//Собираем вместе label из bootstrap5 частей, т.к. мы его не рендерили методе label (как сделано у родителя)
		$this->parts['{label}']=strtr('{beginLabel}{labelTitle}{endLabel}',$this->parts);
	}
	
	/**
	 * Добавляет авторесайз к обычному textarea
	 * @param $options
	 * @return ActiveField
	 */
	public function textAutoresize ($options = ['rows'=>4])
	{

		return $this->widget(TextAutoResizeWidget::class,$options);
	}
	

	/**
	 * Выводит выпадающий список с возможностью поиска
	 * поле multiple (если не указано) определяется автоматически на основании _ids у имени атрибута
	 * @param array $options
	 * @return ActiveField
	 * @throws \yii\base\InvalidConfigException
	 */
	public function select2($options=[]) {
		//задаем параметры select2 по умолчанию, также допускаем передачу их через опции (а не через pluginOptions)
		$pluginOptions=array_merge([
			'allowClear' => ArrayHelper::remove($options,'allowClear',true),
			'multiple' => ArrayHelper::remove($options,'multiple', StringHelper::endsWith($this->attribute,'_ids')),
		],$options['pluginOptions']??[]);
		
		//ищем на какую модель ссылается атрибут
		$linkClass=ArrayHelper::remove($options,'linkModel','');
		
		//если не передали через опции виджета, то смотрим через схему связей модели
		if (!$linkClass && $this->model instanceof ArmsModel) {
			$linkClass=$this->model->attributeLinkClass($this->attribute);
		}
		
		if (!isset($options['data']) && $linkClass && method_exists($linkClass, 'fetchNames')) {
			//если не передали данные, то пробуем получить их из модели
			$options['data']=$linkClass::fetchNames();
		}

		//проверяем есть ли у нас в опциях itemsHintsUrl
		$itemsHintsUrl = ArrayHelper::remove($options, 'itemsHintsUrl', 'auto');
		
		//если стоит авто (по умолчанию)
		if ($itemsHintsUrl=='auto') {
			//по умолчанию мы не знаем как найти подсказки
			$itemsHintsUrl='';
			
			//но если у нас есть модель
			if ($linkClass) {
				/** @var ArmsBaseController $controller */
				if (class_exists($controllerClass= StringHelper::class2Controller($linkClass))) {
					$controller=new $controllerClass(\Yii::$app->id, \Yii::$app);
					$classId=StringHelper::class2id($linkClass);
					if (
						//если метод ttip не заблокирован
						!in_array('ttip', $controller->disabledActions())
						&&
						(//и если у этой модели есть ttip
							
							file_exists($_SERVER['DOCUMENT_ROOT'].'/views/'.$classId.'/card.php')
							||
							file_exists($_SERVER['DOCUMENT_ROOT'].'/views/'.$classId.'/ttip.php')
						)
					) {
						//то строим ссылку
						$itemsHintsUrl = Url::to([ $classId. '/ttip', 'q' => 'dummy']);
					}
				}
			}
		}

		//если надо подтягивать тултипы - регистрируем нужные библиотеки и функции
		if ($itemsHintsUrl) {
			Select2FieldAsset::register($this->form->view);
			
			$pluginOptions['templateResult']=new JsExpression('function(item){return formatSelect2ItemHint(item,"'.$itemsHintsUrl.'")}');
			$pluginOptions['templateSelection']=new JsExpression('function(item){return formatSelect2ItemHint(item,"'.$itemsHintsUrl.'")}');
			$pluginOptions['escapeMarkup']=new JsExpression('function(m) { return m; }');
			if ($pluginOptions['multiple']??false)
				$pluginOptions['selectionAdapter']=new JsExpression('jQuery.fn.select2.amd.require("QtippedMultipleSelectionAdapter")');
			else
				$pluginOptions['selectionAdapter']=new JsExpression('jQuery.fn.select2.amd.require("QtippedSingleSelectionAdapter")');
			
		}
		
		$placeholder='Начните набирать для поиска';
		if ($this->model->hasMethod('getAttributePlaceholder')) {
			$placeholder=$this->model->getAttributePlaceholder($this->attribute);
		}
		
		return $this->widget(Select2::class, ArrayHelper::recursiveOverride([
			'options'=>[
				'placeholder'=>$placeholder,
			],
			'toggleAllSettings'=>['selectLabel' => null],
			'pluginOptions' => $pluginOptions,
		],$options));
	}
	
	/**
	 * Добавлена поддержка placeholder
	 * @param $options
	 * @return $this
	 */
	public function textInput($options=[])
	{
		$placeholder='';
		if ($this->model->hasMethod('getAttributePlaceholder')) {
			$placeholder=$this->model->getAttributePlaceholder($this->attribute);
		}
		
		return parent::textInput(ArrayHelper::recursiveOverride([
			'placeholder'=>$placeholder,
		],$options));
	}
	
	/**
	 * Возвращает тип текстового поля у класса (text/markdown/dokuwiki)
	 * @param $class
	 * @param $attribute
	 * @return string
	 */
	public static function textFieldType ($class,$attribute)
	{
		if (isset(\Yii::$app->params['textFields'][StringHelper::className($class).'.'.$attribute]))
			return \Yii::$app->params['textFields'][StringHelper::className($class).'.'.$attribute];
		
		if (StringHelper::endsWith($attribute,'Recursive')) {
			//если это рекурсивное поле, то смотрим на его базовое поле
			$baseAttribute=substr($attribute,0,strlen($attribute)-strlen('Recursive'));
			if (isset(\Yii::$app->params['textFields'][StringHelper::className($class).'.'.$baseAttribute]))
				return \Yii::$app->params['textFields'][StringHelper::className($class).'.'.$baseAttribute];
		}
		
		return \Yii::$app->params['textFields']['default'];
	}
	
	
	/**
	 * Создает поле ввода текста в зависимости от типа
	 * @param array $options
	 * @return ActiveField
	 * @throws \Exception
	 */
	public function text($options=[])
	{
		switch (self::textFieldType(get_class($this->model),$this->attribute)) {
			case 'markdown':
				ArrayHelper::remove($options,'rows');
				return $this->widget(MarkdownEditor::class,array_merge([
					'showExport'=>false,
				],$options));
			case 'dokuwiki':
				ArrayHelper::remove($options,'height');
				return $this->widget(DokuWikiEditor::class,$options);
			default:
				ArrayHelper::remove($options,'height');
				return $this->textAutoresize($options);
		}
	}
	
	public function checkboxList($items,$options=[]): \yii\bootstrap5\ActiveField
	{
		$options=array_merge([
			'class'=>'card d-flex flex-wrap flex-row pt-2 pb-1',
			'itemOptions'=>['class'=>'p-2'],
		],$options);
		return parent::checkboxList($items,$options);
	}
	
	public function date($options=[])
	{
		$placeholder='Введите дату ...';
		if ($this->model->hasMethod('getAttributePlaceholder')) {
			$placeholder=$this->model->getAttributePlaceholder($this->attribute);
		}
		
		return $this->widget(DateControl::class, ArrayHelper::recursiveOverride([
			'options' => ['placeholder' => $placeholder],
			'pluginOptions' => [
				'autoclose'=>true,
				'weekStart' => '1',
				'format' => 'yyyy-mm-dd',
			],
		],$options));
	}
	
	public function datetime($options=[])
	{
		$placeholder='Введите дату/время ...';
		if ($this->model->hasMethod('getAttributePlaceholder')) {
			$placeholder=$this->model->getAttributePlaceholder($this->attribute);
		}
		
		return $this->widget(DateControl::class, ArrayHelper::recursiveOverride([
			'options' => [
				'placeholder' => $placeholder,
				'type'=>DateControl::FORMAT_DATETIME,
			],
			'pluginOptions' => [
				'autoclose'=>true,
				'weekStart' => '1',
				'format' => 'yyyy-mm-dd hh:ii',
			],
		],$options));
	}
	
	public function autoInput()
	{
		if ($this->model->hasMethod('attributeIsLink') && $this->model->attributeIsLink($this->attribute)) {
			return $this->select2();
		}
		
		if ($this->model->hasMethod('getAttributeType')) {
			switch ($this->model->getAttributeType($this->attribute)) {
				case 'boolean': return $this->checkbox();
				case 'date': return $this->date();
				case 'datetime': return $this->datetime();
				case 'ntext':  return $this->textAutoresize();
				case 'text':  return $this->text();
			}
		}
	
		return $this->textInput();
		
	}
	
	public function render($content = null): string
	{
		if ($content === null) {
			if (!isset($this->parts['{hint}'])) {
				$this->classicHint(null);
			}
			if (!isset($this->parts['{input}'])) {
				$this->autoInput();
			}
		}
		return parent::render($content);
	}
}