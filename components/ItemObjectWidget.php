<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 19.05.2023
 * Time: 8:05
 */

namespace app\components;

use app\helpers\StringHelper;
use app\models\ArmsModel;
use Yii;
use yii\base\Widget;


/**
* Class ItemObjectWidget
 * @package app\components
 * @property ArmsModel $model
 */
class ItemObjectWidget extends Widget
{
	public $model;				//модель на которую ссылаемся
	public $link;				//ссылка на модель (Html::a)
	public $show_archived=null;	//флаг отображения архивного элемента
	public $archivedProperty='archived'; //какое свойство объекта означает признак "архивирован"
	public $archived=null;		//явное указание что объект архивирован
	public $archived_class='text-muted text-decoration-line-through';	//класс который добавлять к архивному элементу
	public $item_class=null;	//класс который добавлять к элементу

	public function run()
	{

		//если прямо не сказано что за класс элемента - стряпаем сами
		if (is_null($this->item_class)) {
			if (is_object($this->model))
				$this->item_class= StringHelper::class2Id(get_class($this->model)).'-item';
		}

		//к архивному классу кроме оформительской части всегда должен быть добавлен флажок архивного элемента
		//(для переключателя отображения)
		if (!strpos($this->archived_class,'archived-item')) $this->archived_class .= ' archived-item';
		
		//если не знаем показывать ли архивные - смотрим по запросу
		if (is_null($this->show_archived)) $this->show_archived=Yii::$app->request->get(
			'showArchived',
			ShowArchivedWidget::$defaultValue
		);
		
		if (!isset($this->link)) $this->link=LinkObjectWidget::widget(['model'=>$this->model]);
		
		if (!isset($this->archived)) {
			$archivedProperty = $this->archivedProperty;
			$archived = $this->model->hasProperty($archivedProperty) ? $this->model->$archivedProperty : false;
		} else {
			$archived=$this->archived;
		}

		$display=($archived&&!$this->show_archived)?'style="display:none"':'';
		
		$cssClass='object-item '. $this->item_class.' '.($archived?$this->archived_class:'');
		
		return "<span class=\"$cssClass\" $display>{$this->link}</span> ";
	}
}