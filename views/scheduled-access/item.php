<?php
/**
 * Рендер элемента расписания
 * Created by PhpStorm.
 * User: reviakin.a
 * Date: 18.10.2020
 * Time: 17:01
 */

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;
if (!isset($empty)) $empty='- расписание отсутствует -';

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'uvolen',
		'archivedProperty'=>'Uvolen',
		'name'=>$name,
		'static'=>$static_view,
		'ttipUrl'=> Url::to(['scheduled-access/ttip','id'=>$model->id]),
	]);
} else {
	echo $empty;
}
