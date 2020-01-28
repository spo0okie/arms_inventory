<?php

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = $model->Ename;
$this->params['breadcrumbs'][] = ['label' => \app\models\Users::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$isAdmin=(empty(Yii::$app->params['useRBAC']) || Yii::$app->user->can('admin_access'));

?>
<div class="users-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model,'static_view'=>false]) ?>
		</div>
		<div class="col-md-6">
			<?= $isAdmin?$this->render('roles',['model'=>$model,'static_view'=>false]):'' ?>
		</div>
	</div>
</div>
