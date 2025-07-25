<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 31.12.2023
 * Time: 15:45
 * Собственно решил я в новый год сделать таки виджет который выводит поле модели.
 * Задача оценить что в поле, объект, строка, массив объектов или массив строк и вывести как надо
 */

namespace app\components;

use app\components\Forms\ActiveField;
use app\helpers\ArrayHelper;
use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;

/**
* Class ItemObjectWidget
 * @package app\components
 * @property ArmsModel[] $models
 */
class ModelFieldWidget extends Widget
{
	/**
	 * @var ArmsModel модель, поле которой нам нужно
	 */
	public $model;
	public $models;
	public $field;				//поле модели, которое нам нужно
	public $fieldType;			//тип поля (если нам надо переопределить или отрендерить как...)
	public $title;				//заголовок поля
	public $title_options=[];	//опции для рендера заголовка
	public $show_archived;		//флаг отображения архивного элемента
	public $item_options=[];	//опции для рендера элемента
	public $card_options=['cardClass'=>'mb-3'];	//опции для рендера карточки
	public $archived;			//признак того что весь список состоит из архивных элементов
	public $lineBr=true;		//переносить строку между элементами
	public $glue=' ';			//чем разделять элементы
	public $show_empty=false;	//отображать карточку если список пуст
	public $message_on_empty='';//отображать сообщение вместо списка, если он пуст
	public $itemViewPath;		//путь для рендера элемента
	public $modelClass;			//класс объектов из списка значений поля
	public $raw_items=false;	//не конвертировать текстовые items в HTML (уже сконвертированы)
	
	private $data=[];
	
	/**
	 * Загружает в свой накопитель $data данные из модели
	 * @param ArmsModel $model
	 * @return void
	 */
	public function loadModelData($model) {
		if (!isset($this->fieldType) && is_object($this->model)) {
			$this->fieldType=$this->model->getAttributeType($this->field);
		}
		
		switch ($this->fieldType) {
			case 'text':
				$this->data[]=TextFieldWidget::widget(['model'=>$model,'field'=>$this->field]);
				$this->raw_items=true;
				$this->lineBr=false;	//иначе вставленные в wiki-render элементы будут обрывать строку
				return;
			case 'urls':
				$links=new UrlListWidget(['list'=>$model->links]);
				$links->renderItems();
				$this->data = array_merge($this->data,$links->rendered);
				$this->raw_items=true;
				return;
		}
		
		$field=$this->field;
		if ($model->hasMethod('attributeIsLink') && $model->attributeIsLink($field)) {
			$field=$model->attributeLinkLoader($field);
		}
		
		//вытаскиваем поле в отдельную переменную, чтобы больше не городить такое
		$modelData=ArrayHelper::getValue($model,$field);
		if (is_array($modelData)) {
			$this->data=array_merge($this->data,$modelData);
		} else {
			if (!empty($modelData))
				$this->data[]=$modelData;
		}
		
	}
	
	/**
	 * @param ArmsModel $model
	 * @param string $field
	 * @return array
	 */
	public static function fieldTitle($model,$field,$view)
	{
		$options=[];
		$label=$model->getAttributeLabel($field);
		$hint=$model->getAttributeHint($field);
		
		if ($model->attributeIsInheritable($field)) {
			//если атрибут наследуемый, то дописываем явно задано значение или унаследовано
			$inheritedFrom=$model->findRecursiveAttrNode($field);
			$hint.='<br><strong>Наследуемый атрибут</strong>: ';
			if (is_object($inheritedFrom)) {
				if ($model->id===$inheritedFrom->id)
					$hint.='значение задано явно';
				else {
					$hint.='унаследовано от '.$inheritedFrom->renderItem($view,['static_view'=>true]);
				}
			} else {
				$hint.='не задан ни на одном из узлов ветви.';
			}
			
		}
		
		if (!empty($hint)) {
			$options=ActiveField::hintTipOptions($label,$hint);
		}
		return [$label,$options];
	}
	
	public static function renderFieldTitle($model,$field,$view,$tag='h4')
	{
		[$title,$options]=static::fieldTitle($model,$field,$view);
		return Html::tag($tag,$title,$options);
	}
	
	public function init(){
		parent::init();
		
		if (is_array($this->models)) {
			$this->model=reset($this->models);
			foreach ($this->models as $model)
				$this->loadModelData($model);
		} else {
			$this->loadModelData($this->model);
		}
		
		if (!isset($this->title)) {
			[$this->title,$title_options]=static::fieldTitle($this->model,$this->field,$this->view);
			$this->title_options=ArrayHelper::recursiveOverride($title_options,$this->title_options);
		}
	}
	
	public function run()
	{
		return ListObjectsWidget::widget([
			'models'=>$this->data,
			'title'=>$this->title,
			'title_options'=>$this->title_options,
			'item_options'=>$this->item_options,
			'card_options'=>$this->card_options,
			'archived'=>$this->archived,
			'lineBr'=>$this->lineBr,
			'glue'=>$this->glue,
			'show_empty'=>$this->show_empty,
			'message_on_empty'=>$this->message_on_empty,
			'itemViewPath'=>$this->itemViewPath,
			'modelClass'=>$this->modelClass,
			'raw_items'=>$this->raw_items,
		]);
	}
}