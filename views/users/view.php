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
			<?php foreach ($model->techs as $arm) if ($arm->isComputer)
				echo $this->render('/techs/compact',['model'=>$arm,'no_users'=>true,'no_specs'=>true])?>
		</div>
		<div class="col-md-4">
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
			
			if (count($model->techs)) {
				echo '<h4>Оборудование числящиеся за сотрудником</h4><p>';
				foreach ($model->techsHead as $arm) if (!$arm->isComputer) echo $this->render('/techs/item',['model'=>$arm]);
				echo '</p><br />';
			}
			
			if (count($model->techsHead)) {
				echo '<h4>АРМ/оборудование числящиеся за подчиненными</h4><p>';
				foreach ($model->techsHead as $arm) echo $this->render('/techs/item',['model'=>$arm]);
				echo '</p><br />';
			}
			
			if (count($model->techsIt)) {
				echo '<h4>Обслуживаемое сотрудником оборудование</h4><p>';
				foreach ($model->techsIt as $arm) echo $this->render('/techs/item',['model'=>$arm]);
				echo '</p><br />';
			}

			if (count($model->techsResponsible)) {
				echo '<h4>АРМ/оборудование в ответственности</h4><p>';
				foreach ($model->techsResponsible as $arm) echo $this->render('/techs/item',['model'=>$arm]);
				echo '</p><br />';
			}
			
			if (count($model->materials)) {
				echo '<h4>Ответственный материалы</h4><p>';
				foreach ($model->materials as $material) if ($material->rest>0) echo $this->render('/materials/item',[
						'model'=>$material,
						'responsible'=>false,
						'from'=>true,
						'rest'=>true,
					]).'<br />';
				echo '</p><br />';
			}
			?>

			<br />
			<?= \app\models\Users::isAdmin()?$this->render('roles',['model'=>$model,'static_view'=>false]):'' ?>

		</div>
	</div>
</div>
