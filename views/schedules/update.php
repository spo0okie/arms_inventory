<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

$acl_mode=(count($model->acls));
if ($model->isOverride) {
	$this->title = 'Изменения в периоде дат';
	$this->params['breadcrumbs'][] = ['label' => \app\models\Schedules::$titles, 'url' => ['index']];
	$this->params['breadcrumbs'][] = ['label' => $model->overriding->name, 'url' => ['index']];
} else {

	$this->title = 'Изменить '.mb_strtolower(\app\models\Schedules::$title).': ' . $model->name;
	if (!$acl_mode) {
		$this->params['breadcrumbs'][] = ['label' => \app\models\Schedules::$titles, 'url' => ['index']];
	} else {
		$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$scheduleTitles, 'url' => ['index-acl']];
	}
	$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
	$this->params['breadcrumbs'][] = 'Изменить';
}
?>
<div class="schedules-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render($model->isOverride?'_form_override':'_form', [
        'model' => $model,
    ]) ?>

</div>
