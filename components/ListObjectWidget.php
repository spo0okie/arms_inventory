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
class ListObjectWidget extends Widget
{
	public static $modelsPath='app\\models\\';	//путь где живут классы моделей, который откусываем от имени класса
								// модели для формирования пути view
	
	public $models;				//модели, список которых нам нужен
	public $title=null;			//заголовок списка
	public $show_archived=null;	//флаг отображения архивного элемента
	public $item_options=[];	//опции для рендера элемента
	public $card_options=[];	//опции для рендера карточки
	public $archived=null;		//признак того что весь список состоит из архивных элементов
	public $lineBr=true;		//переносить строку между элементами
	public $show_empty=false;	//отображать заголовок если список пуст
	public $itemViewPath=null;	//путь для рендера элемента
	public $modelClass=null;
	
	private $model=null;
	private $empty;
	
	public function init(){
		parent::init();
		
		$this->empty=!count($this->models);
		if (!$this->empty) {
			//берем себе одну модель для образца
			$this->model=reset($this->models);
		}
		
		//определяем класс перечисляемых моделей
		if (is_null($this->modelClass)) {
			$this->modelClass='ArmsModel';
			if (!$this->empty)
				$this->modelClass=$this->model::className();
			
			//выкидываем адрес пути до всех моделей
			if (strpos($this->modelClass,static::$modelsPath)===0)
				$this->modelClass=substr($this->modelClass,strlen(static::$modelsPath));
		}
		
		//строим путь до view model/item
		if (is_null($this->itemViewPath)) {
			$this->itemViewPath='/'.Inflector::camel2id($this->modelClass).'/item';
		}
		
		//вытаскиваем заголовок из модели
		if (is_null($this->title)) {
			$this->title='$Title_error';
			if (!$this->empty) {
				if ($this->model->hasProperty('titles')) {
					$this->title=$this->model->titles;
				} elseif ($this->model->hasProperty('title')) {
					$this->title=$this->model->title;
				}
			}
		}
		
		//проверяем не содержит ли список только архивные элементы
		if (is_null($this->archived) && !$this->empty && $this->model && $this->model->hasProperty('archived')) {
			$allArchived=true;
			foreach ($this->models as $model) $allArchived=$allArchived&&$model->archived;
			$this->archived=$allArchived;
		}
		
		if (!isset($this->card_options['cardClass']))
			$this->card_options['cardClass']='';
		
		$this->card_options['cardClass'].=' '.($this->lineBr?'line-break':'line-nobr');
		
		//если не знаем показывать ли архивные - смотрим по запросу
		if (is_null($this->show_archived)) $this->show_archived=Yii::$app->request->get(
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
			$listItems[]=$this->render($this->itemViewPath,ArrayHelper::recursiveOverride([
				'model'=>$model,'show_archived'=>$this->show_archived
			],$this->item_options));
		}
		$list=implode(' ',$listItems);
		
		//карточка
		return ExpandableCardWidget::widget(ArrayHelper::recursiveOverride([
			'content'=>$title.$list
		],$this->card_options));
		
		//return "<span class=\"$cssClass\" $display>{$this->link}</span> ";
	}
}