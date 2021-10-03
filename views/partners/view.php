 <?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Partners */

$this->title = $model->uname;
$this->params['breadcrumbs'][] = ['label' => 'Контрагенты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="partners-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model]) ?>
		</div>
		<div class="col-md-6">
			<?php if (count($model->services)) { ?>
				<h4><?= \app\models\Services::$titles ?></h4>
				<p>
					<?php
					$items=[];
					foreach ($model->services as $service)
						$items[]=$this->render('/services/item',['model'=>$service]);
					echo implode('<br />',$items);
					?>
				</p>
			<?php } ?>

			<?php if (count($model->contracts)) { ?>
				<h4><?= \app\models\Contracts::$titles ?></h4>
				<p>
					<?php
					$items=[];
					foreach ($model->contracts as $contract)
						$items[]=$this->render('/contracts/item',['model'=>$contract]);
					echo implode('<br />',$items);
					?>
				</p>
			<?php } ?>
		</div>
	</div>
</div>
