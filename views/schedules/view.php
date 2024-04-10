<?php

use app\models\Acls;
use app\models\Schedules;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

$acl_mode=(count($model->acls));
if (!isset($static_view)) $static_view=false;

$this->title = $model->name;
if (!$acl_mode) {
	$this->params['breadcrumbs'][] = ['label' => Schedules::$titles, 'url' => ['index']];
} else {
	$this->params['breadcrumbs'][] = ['label' => Acls::$scheduleTitles, 'url' => ['index-acl']];
}
$this->params['breadcrumbs'][] = $this->title;
Url::remember();


YiiAsset::register($this);
echo $this->render('card',['model'=>$model]);
