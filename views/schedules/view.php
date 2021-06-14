<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Расписания', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$schedule_id=$model->id;
\yii\web\YiiAsset::register($this);
?>
<div class="schedules-view">

	<div class="row">
		<div class="col-md-6">
			<?= $this->render('week',['model'=>$model])?>
			<?= $this->render('7days',['model'=>$model])?>
		</div>
		<div class="col-md-6">
			<?= $this->render('week-edit',['model'=>$model])?>
			<?= $this->render('exceptions',['model'=>$model])?>
		</div>
	</div>
</div>
<div class="schedules-view">

	<div class="row">
		<div class="col-md-6">
		</div>
		<div class="col-md-6">
		</div>
	</div>
</div>
