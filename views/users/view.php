<?php

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = $model->Ename;
$this->params['breadcrumbs'][] = ['label' => \app\models\Users::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="users-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model,'static_view'=>false]) ?>
		</div>
		<div class="col-md-6">
			<?= \app\models\Users::isAdmin()?$this->render('roles',['model'=>$model,'static_view'=>false]):'' ?>
			<br />
			<br />
			<?php if (count($model->services)) {
				echo '<h4>Ответственный за сервисы</h4>';
				foreach ($model->services as $service) {
					echo $this->render('/services/item',['model'=>$service]);
				}
			} ?>
		</div>
	</div>
</div>
