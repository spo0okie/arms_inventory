<?php

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = $model->Ename;
$this->params['breadcrumbs'][] = ['label' => \app\models\Users::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$services=$model->services;
$comps=count($services)?$model->compsTotal:$model->comps;

?>
<div class="users-view">
	<div class="row">
		<div class="col-md-4">
			<?= $this->render('card',['model'=>$model,'static_view'=>false]) ?>
		</div>
		<div class="col-md-4">
			<h2>Рабочее место</h2>
			<?php foreach ($model->arms as $arm)
				echo $this->render('/arms/card',['model'=>$arm,'no_users'=>true,'no_specs'=>true])?>
		</div>
		<div class="col-md-4">
			<?= \app\models\Users::isAdmin()?$this->render('roles',['model'=>$model,'static_view'=>false]):'' ?>
			<br />
			<br />
			<?php
			if (count($model->services)) {
				echo '<h4>Ответственный за сервисы</h4><p>';
				foreach ($model->services as $service) echo $this->render('/services/item',['model'=>$service]);
				echo '</p><br />';
			}

			if (count($comps)) {
				echo '<h4>Ответственный за ОС</h4><p>';
				foreach ($comps as $comp) echo $this->render('/comps/item',['model'=>$comp]);
				echo '</p><br />';
			}
			
			if (count($model->materials)) {
				echo '<h4>Ответственный материалы</h4><p>';
				foreach ($model->materials as $material) echo $this->render('/materials/item',['model'=>$material]).'<br />';
				echo '</p><br />';
			}
			?>

		</div>
	</div>
</div>
