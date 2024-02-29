<?php


namespace app\components;


use app\models\ArmsModel;
use app\models\HistoryModel;
use yii\base\Widget;
use yii\helpers\Html;

class HistoryRecordWidget extends Widget
{
	/** @var ArmsModel */
	public $model;
	public $iconClass='fas fa-book';
	public $class='float-end';
	public $tag='div';
	public $hint='Данные восстановлены из журнала аудита.'."\n".
		'Оперативные данные могут отличаться';
	
	public function run() {
		if ($this->model instanceof HistoryModel) {
			return Html::tag(
				$this->tag,
				Html::tag('i','',['title'=>$this->hint,'class'=>$this->iconClass]),
				['class'=>$this->class]
			);
		}
		return '';
	}
}