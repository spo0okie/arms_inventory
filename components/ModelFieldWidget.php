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

use app\models\ArmsModel;
use yii\base\Widget;

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
	public $field;				//поле модели, которое нам нужно
	public $title;				//заголовок поля
	public $show_archived;		//флаг отображения архивного элемента
	public $item_options=[];	//опции для рендера элемента
	public $card_options=['cardClass'=>'mb-3'];	//опции для рендера карточки
	public $archived;			//признак того что весь список состоит из архивных элементов
	public $lineBr=true;		//переносить строку между элементами
	public $glue=' ';			//чем разделять элементы
	public $show_empty=false;	//отображать заголовок если список пуст
	public $itemViewPath;		//путь для рендера элемента
	public $modelClass;			//класс объектов из списка значений поля
	public $raw_items=false;
	
	private $data;
	
	public function init(){
		parent::init();
		
		if (!isset($this->data)) {
			if ($this->field==='links') {
				$links=new UrlListWidget(['list'=>$this->model->links]);
				$links->renderItems();
				$this->data = $links->rendered;
				$this->raw_items=true;
			} else {
				//вытаскиваем поле в отдельную переменную, чтобы больше не городить такое
				$this->data=$this->model->{$this->field};
			}
		}
		
		if (!is_array($this->data)) {
			$this->data=empty($this->data)?[]:[$this->data];
		};
		
		if (!isset($this->title)) {
			$this->title=$this->model->getAttributeLabel($this->field);
		}
		
	}
	
	public function run()
	{
		return ListObjectsWidget::widget([
			'models'=>$this->data,
			'title'=>$this->title,
			'item_options'=>$this->item_options,
			'card_options'=>$this->card_options,
			'archived'=>$this->archived,
			'lineBr'=>$this->lineBr,
			'glue'=>$this->glue,
			'show_empty'=>$this->show_empty,
			'itemViewPath'=>$this->itemViewPath,
			'modelClass'=>$this->modelClass,
			'raw_items'=>$this->raw_items
		]);
	}
}