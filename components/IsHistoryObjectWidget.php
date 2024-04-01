<?php

/**
 * Виджет добавляющий иконку если переданный объект получен не из оперативной БД а из журналов аудита
 */

namespace app\components;


use app\models\ArmsModel;
use app\models\HistoryModel;
use yii\base\Widget;
use yii\helpers\Html;

class IsHistoryObjectWidget extends Widget
{
	/** @var ArmsModel */
	public $model;							//какой объект проверяем
	public $iconClass='fas fa-book';		//какую иконку рисовать для объекта из архива
	public $class='float-end';				//в какой класс завернуть иконку
	public $tag='div';						//в какой тег завернуть иконку
	public $hint='Данные восстановлены из журнала аудита.'."\n".
		'Оперативные данные могут отличаться';
	public $else='';						//что вывести, если объект не из журнала
	
	public function run() {
		if ($this->model instanceof HistoryModel) {
			return Html::tag(
				$this->tag,
				Html::tag('i','',['title'=>$this->hint,'class'=>$this->iconClass]),
				['class'=>$this->class]
			);
		}
		return $this->else;
	}
}