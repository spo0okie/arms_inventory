<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */

\yii\helpers\Url::remember();

$this->title = $model->num;
$this->params['breadcrumbs'][] = ['label' => \app\models\Techs::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="techs-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model,'static_view'=>false,'no_model'=>true]) ?>
		</div>
		<div class="col-md-6">
			<h1>&nbsp;</h1>
			<h4><?= Html::a('Изображения',['uploads','id'=>$model->id]) ?></h4>
			<?php
			if (is_array($scans=$model->scans)&&count($scans)) foreach ($scans as $scan)
				echo $this->render('/scans/thumb',['model'=>$scan,'contracts_id'=>$model->id,'static_view'=>true]);
			?>
			<p>

			<?= $this->render('model',['model'=>$model]) ?>
			<h3><?= \app\models\Ports::$titles ?></h3>
			<?= $this->render('ports',['model'=>$model,'static_view'=>false]) ?>
		</div>
	</div>
</div>
