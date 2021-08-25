<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */

$this->title = 'Редактирование: ' . $model->num;
$this->params['breadcrumbs'][] = ['label' => \app\models\Techs::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->num, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="techs-update">

    <h1><?= Html::encode($this->title) ?></h1>

	<?= $this->render('/scans/_form', [
		'model' => $model,
		'link' => 'techs_id',
	]) ?>
</div>
