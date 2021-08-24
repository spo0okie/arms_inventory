<?php
/* @var $this yii\web\View */
/* @var $model app\models\Acls */
if (!isset($static_view)) $static_view=false;
if (!isset($show_item)) $show_item=true;

if ($model->schedules_id) {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$scheduleTitles, 'url' => ['schedules/index-acl']];
	$this->params['breadcrumbs'][] = ['label' => $model->schedule->name, 'url' => ['schedules/view','id'=>$model->schedules_id]];
} else {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$titles, 'url' => ['index']];
}

if ($show_item) {
	if ($static_view)
		$this->params['breadcrumbs'][] = $model->sname;
	else
		$this->params['breadcrumbs'][] = ['label' => $model->sname, 'url' => ['acls/view','id'=>$model->id]];
}

