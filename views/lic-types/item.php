<?php
/**
 * Элемент схемы лицензирования
 * User: spookie
 * Date: 02.12.2020
 * Time: 22:27
 */

/* @var \app\models\LicTypes $model */
/* @var $this yii\web\View */

use app\components\ItemObjectWidget;
use yii\helpers\Url;
if (!isset($static_view)) $static_view=false;

if (is_object($model)) {
	$ttipUrl=$static_view?null:Url::to(['/lic-types/ttip','id'=>$model->id]);
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->descr,
		'static'=>$static_view,
		'ttipUrl'=>$ttipUrl,
	]);
} else echo "Отсутствует";
