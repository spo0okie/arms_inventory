<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$this->title = "Новый ".\app\models\Aces::$title;


if ($model->acl->schedules_id) {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$scheduleTitles, 'url' => ['schedules/index-acl']];
	$this->params['breadcrumbs'][] = ['label' => $model->acl->schedule->name, 'url' => ['schedules/view','id'=>$model->acl->schedules_id]];
} else {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$titles, 'url' => ['index']];
}
$this->params['breadcrumbs'][] = ['label'=>$model->acl->sname,'url' => ['acls/view','id'=>$model->acls_id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aces-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
