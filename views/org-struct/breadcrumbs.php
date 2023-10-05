<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $partner \app\models\Partners */
/* @var $model \app\models\OrgStruct */

//выходим на список
if (is_object($partner)) {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Partners::$titles, 'url'=>['partners/index']];
	$this->params['breadcrumbs'][] = ['label' => $partner->bname, 'url'=>['partners/view','id'=>$partner->id]];
	$this->params['breadcrumbs'][] = ['label' => \app\models\OrgStruct::$titles, 'url' => ['index','org_id'=>$partner->id]];
} else {
	$this->params['breadcrumbs'][] = ['label' => \app\models\OrgStruct::$titles, 'url' => ['index']];
}

//выходим на экземпляр
if (is_object($model)) foreach ($model->chain as $item) $this->params['breadcrumbs'][]=[
	'label'=>$item->name,
	'url'=>['org-struct/view','id'=>$item->id,'org_id'=>$item->org_id],
];
