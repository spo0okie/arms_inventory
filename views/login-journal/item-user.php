<?php

use yii\helpers\Html;
use yii\widgets\DetailView;


use app\components\widgets\page\ModelWidget;
/** @var yii\web\View $this */
/** @var app\models\LoginJournal $model */
/** @var string $name */
/** @var string $suffix */

if (!isset($suffix)) $suffix='';


if (!isset($static_view)) $static_view=false;

if (is_object($model)) {
	echo ModelWidget::widget(['model'=>$model->user,'options'=>['name'=>$model->userDescr]]);
	echo $this->render('/login-journal/status',['model'=>$model,'static_view'=>$static_view]);
	echo $suffix;
}
