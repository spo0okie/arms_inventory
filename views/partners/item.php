<?php
/**
 * Элемент услуги связи
 * User: spookie
 * Date: 03.01.2019
 * Time: 01:21
 */

/* @var \app\models\Partners $model */

use app\components\ItemObjectWidget;
use yii\helpers\Url;
if (is_object($model)) {
	if (!isset($static_view)) $static_view=false;
	if (!isset($name)) $name=null;
	switch ($name) {
		case null:
		case 'short': $modelName=$model->bname;
			break;
		case 'long': $modelName=$model->longName;
			break;
		case 'medium': $modelName=$model->sname;
			break;
		default: $modelName = $name;
	}
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$modelName,
		'static'=>$static_view,
		'ttipUrl'=>Url::to(['/partners/ttip','id'=>$model->id]),
	]);
}
