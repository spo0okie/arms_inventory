<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

$acl_mode=(count($model->acls));


$this->title = $model->name;
if (!$acl_mode) {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Schedules::$titles, 'url' => ['index']];
} else {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$scheduleTitles, 'url' => ['index-acl']];
}
$this->params['breadcrumbs'][] = $this->title;
$schedule_id=$model->id;
\yii\web\YiiAsset::register($this);

if (!$acl_mode) { ?>
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
<?php } else { ?>
	<div class="schedules-view">

		<div class="row">
			<div class="col-md-6">
				<?= $this->render('acl',['model'=>$model]) ?>
			</div>
			<div class="col-md-6">
				<?= $this->render('exceptions',['model'=>$model]) ?>
			</div>
		</div>
	</div>
	
<?php } ?>
