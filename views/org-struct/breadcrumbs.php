<?php

use app\models\OrgStruct;
use app\models\Partners;

/* @var $this yii\web\View */
/* @var $partner Partners */
/* @var $model OrgStruct */

//выходим на список
if (is_object($partner)) {
	$this->params['breadcrumbs'][] = ['label' => Partners::$titles, 'url'=>['partners/index']];
	$this->params['breadcrumbs'][] = ['label' => $partner->bname, 'url'=>['partners/view','id'=>$partner->id]];
	$this->params['breadcrumbs'][] = ['label' => OrgStruct::$titles, 'url' => ['index','org_id'=>$partner->id]];
} else {
	$this->params['breadcrumbs'][] = ['label' => OrgStruct::$titles, 'url' => ['index']];
}

//выходим на экземпляр
if (is_object($model)) foreach ($model->chain as $item) $this->params['breadcrumbs'][]=[
	'label'=>$item->name,
	'url'=>['org-struct/view','id'=>$item->id,'org_id'=>$item->org_id],
];
