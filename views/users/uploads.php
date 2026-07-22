<?php

use app\models\Users;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = 'Фотографии: ' . $model->Ename;
$this->params['breadcrumbs'][] = ['label' => Users::$titles, 'url' => ['/users/index']];
$this->params['breadcrumbs'][] = ['label' => $model->Ename, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Фотографии';
?>
<div class="users-uploads">

	<h1><?= Html::encode($this->title) ?></h1>

	<?= $this->render('_photos_form', ['model' => $model]) ?>

	<br />
	<?= Html::a('Назад', ['view', 'id' => $model->id], ['class' => 'btn btn-success']) ?>

</div>
