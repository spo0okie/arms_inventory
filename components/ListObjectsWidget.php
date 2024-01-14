<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 19.05.2023
 * Time: 16:05
 */

namespace app\components;

use app\helpers\ArrayHelper;
use app\helpers\HtmlHelper;
use app\models\ArmsModel;
use Yii;
use yii\base\Widget;
use yii\helpers\Inflector;

/**
* Class ItemObjectWidget
 * @package app\components
 * @property ArmsModel[] $models
 */
class ListObjectsWidget extends Widget
{
	public $models;				//модели, список которых нам нужен
	public $title;				//заголовок списка
	public $show_archived;		//флаг отображения архивного элемента
	public $item_options=[];	//опции для рендера элемента
	public $card_options=['cardClass'=>'mb-3'];	//опции для рендера карточки
	public $archived;			//признак того что весь список состоит из архивных элементов
	public $lineBr=true;		//переносить строку между элементами
	public $glue=' ';			//чем разделять элементы
	public $show_empty=false;	//отображать заголовок если список пуст
	public $itemViewPath;		//путь для рендера элемента
	public $modelClass;
	public $raw_items=false;	//не конвертировать текстовые итемы в HTML (уже сконверчены)
	
	/**
	 * @var ArmsModel
	 */
	private $model;
	private $is_object=false;
	private $empty;
	private $modelClassName; 	//не путь класса а только его имя
	private $modelClassPath;	 //путь где живут классы моделей, который откусываем от имени класса
	// модели для формирования пути view
	
	
	public function init(){
		parent::init();
		
		//пустой ли список?
		$this->empty=!count($this->models);
		
		if (!$this->empty) {
			//берем себе одну модель для образца
			$this->model=reset($this->models);
			//это вообще объект?
			$this->is_object=is_object($this->model);
		}
		
		//определяем класс перечисляемых моделей если у нас там объекты
		if (!isset($this->modelClass) && $this->is_object) {
			$this->modelClass='ArmsModel';
			
			if (!$this->empty )
				$this->modelClass=get_class($this->model);
			
			//формируем полный путь до класса
			$this->modelClassPath=explode('\\',$this->modelClass);

			//выкидываем адрес пути до всех моделей
			$this->modelClassName=$this->modelClassPath[count($this->modelClassPath)-1];
		}
		
		//строим путь до view model/item
		if (!isset($this->itemViewPath)) {
			$this->itemViewPath='/'.Inflector::camel2id($this->modelClassName).'/item';
		}
		
		//вытаскиваем заголовок из модели
		if (!isset($this->title)) {
			$this->title='$Title_error';
			if ($this->is_object) {
				$class=$this->modelClass;
				if ($this->model->hasProperty('titles')) {
					$this->title=$class::$titles;
				} elseif ($this->model->hasProperty('title')) {
					$this->title=$class::$title;
				} else $this->title=$this->modelClassName;
			}
		}
		
		//проверяем не содержит ли список только архивные элементы
		if (!isset($this->archived) && $this->is_object && $this->model->hasProperty('archived')) {
			$allArchived=true;
			foreach ($this->models as $model) $allArchived=$allArchived&&$model->archived;
			$this->archived=$allArchived;
		}
		
		if (!isset($this->card_options['cardClass']))
			$this->card_options['cardClass']='';
		
		$this->card_options['cardClass'].=' '.($this->lineBr?'line-break':'line-nobr');
		
		//если не знаем показывать ли архивные - смотрим по запросу
		if (!isset($this->show_archived)) $this->show_archived=Yii::$app->request->get(
			'showArchived',
			ShowArchivedWidget::$defaultValue
		);
	}
	
	public function run()
	{
		//если список пуст, а пустое не показываем
		if ($this->empty && !$this->show_empty) return '';
		
		//заголовок
		$titleClass=($this->archived && !$this->show_empty)?ShowArchivedWidget::$itemClass:'';
		$titleDisplay=HtmlHelper::ArchivedDisplay($this,$this->show_empty||$this->show_archived);
		$title=$this->title?"<h4 class='$titleClass' $titleDisplay >{$this->title}</h4>":'';
		
		//список
		$listItems=[];
		foreach ($this->models as $model) {
			if ($this->is_object) {
				$listItems[]=$this->render($this->itemViewPath,ArrayHelper::recursiveOverride([
					'model'=>$model,'show_archived'=>$this->show_archived
				],$this->item_options));
			} elseif (!$this->raw_items) {
				$listItems[]=Yii::$app->formatter->asText($model);
			} else {
				$listItems[]=$model;
			}
		}
		$list=implode($this->glue,$listItems);
		
		//карточка
		return ExpandableCardWidget::widget(ArrayHelper::recursiveOverride([
			'content'=>$title.$list
		],$this->card_options));
		
		//return "<span class=\"$cssClass\" $display>{$this->link}</span> ";
	}
}