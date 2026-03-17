<?php
/** Элемент софта
 * Created by PhpStorm.
 * User: spookie
 * Date: 04.11.2018
 * Time: 15:03
 */
use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $model \app\models\Soft */

if (!isset($static_view)) $static_view=false;
if (!isset($show_vendor)) $show_vendor=false;
if (!isset($hitlist)) $hitlist=null;


if (is_object($model)) {
if (!isset($name)) $name=$model->descr;
	$ttipUrl=Url::to([
		'/soft/ttip',
		'id'=>$model->id,
		'hitlist'=>$hitlist
	]);
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'static'=>$static_view,
		'ttipUrl'=>$ttipUrl,
	]);
}
