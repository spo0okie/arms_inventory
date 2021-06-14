<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SchedulesDays */

if (is_object($model->master)) {
    $this->title = 'Изменить расписание '.$model->master->name.' / '.$model->day;
    $this->params['breadcrumbs'][] = ['label' => 'Расписания', 'url' => ['/schedules']];
    $this->params['breadcrumbs'][] = ['label' => $model->master->name, 'url' => ['/schedules/view', 'id'=>$model->schedule_id]];
    $this->params['breadcrumbs'][] = ['label' => $model->day, 'url' => ['update', 'id' => $model->id]];
} else {
    $this->title = 'Изменить день/дату в расписании ';
    $this->params['breadcrumbs'][] = ['label' => 'Schedules Days', 'url' => ['index']];
    $this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
    $this->params['breadcrumbs'][] = 'Update';
}
?>
<div class="schedules-days-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
