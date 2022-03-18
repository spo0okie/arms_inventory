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

			if (count($model->armsHead)) {
				echo '<h4>АРМ числящиеся за подчиненными</h4><p>';
				foreach ($model->armsHead as $arm) echo $this->render('/arms/item',['model'=>$arm]);
				echo '</p><br />';
			}

			if (count($model->armsIt)) {
				echo '<h4>Обслуживаемые АРМ через отдел IT</h4><p>';
				foreach ($model->armsIt as $arm) echo $this->render('/arms/item',['model'=>$arm]);
				echo '</p><br />';
			}

			if (count($model->armsResponsible)) {
				echo '<h4>АРМ в ответственности</h4><p>';
				foreach ($model->armsResponsible as $arm) echo $this->render('/arms/item',['model'=>$arm]);
				echo '</p><br />';
			}
			
			if (count($model->techsIt)) {
				echo '<h4>Обслуживает технику</h4><p>';
				foreach ($model->techsIt as $tech) echo $this->render('/techs/item',['model'=>$tech]);
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
