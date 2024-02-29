<?php
/**
 * Рендер элемента расписания
 * Created by PhpStorm.
 * User: reviakin.a
 * Date: 18.10.2020
 * Time: 17:01
 */

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;
if (!isset($empty)) $empty='- расписание отсутствует -';

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'static'=>$static_view,
			'name'=>$name,
		]),
	]); } else {
	echo $empty;
}
