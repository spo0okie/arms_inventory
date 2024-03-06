<?php

/**
 * Виджет выбрасывающий alert, если переданный объект архивирован
 */

namespace app\components;


use app\models\ArmsModel;
use yii\base\Widget;

class IsArchivedObjectWidget extends Widget
{
	/** @var ArmsModel */
	public $model;					//какой объект проверяем
	public $title;					//что выводить в алерт
	
	public function init()
	{
		parent::init();
		if (!isset($this->title) && is_object($this->model)) {
			$class=get_class($this->model);
			$this->title=($class::$title).' перенесен в архив';
		}
		$this->title=mb_strtoupper($this->title);
	}
	
	public function run() {
		if (is_object($this->model)&&$this->model->archived) {
			return StripedAlertWidget::widget(['title'=>$this->title]);
		}
		return '';
	}
}