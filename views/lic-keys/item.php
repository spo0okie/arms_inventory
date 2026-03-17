<?php
/**
 * Элемент лицензионный ключ
 * User: spookie
 * Date: 05.11.2018
 * Time: 21:55
 */

/* @var \app\models\LicKeys $model */
/* @var $this yii\web\View */

use app\components\ItemObjectWidget;
use yii\helpers\Url;

if (is_object($model)) {
	if (!isset($static_view)) $static_view=false;
	if (!isset($name)) $name=$model->keyShort;
	$ttipUrl=$static_view?null:Url::to(['/lic-keys/ttip','id'=>$model->id]);
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'static'=>$static_view,
		'ttipUrl'=>$ttipUrl,
	]);
} else echo "Отсутствует";
