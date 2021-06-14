<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */



if (is_object($model->master) && ($model->date)) {
    $this->title = 'Добавить расписание '.$model->master->name.' / '.$model->day;
    $this->params['breadcrumbs'][] = ['label' => 'Расписания', 'url' => ['/schedules']];
    $this->params['breadcrumbs'][] = ['label' => $model->master->name, 'url' => ['/schedules/view', 'id'=>$model->schedule_id]];
    $this->params['breadcrumbs'][] = ['label' => 'Добавление '.$model->day];
} elseif (is_object($model->master)) {
    $this->title = 'Добавить расписание '.$model->master->name.' / Новый день';
    $this->params['breadcrumbs'][] = ['label' => 'Расписания', 'url' => ['/schedules']];
    $this->params['breadcrumbs'][] = ['label' => $model->master->name, 'url' => ['/schedules/view', 'id'=>$model->schedule_id]];
    $this->params['breadcrumbs'][] = ['label' => 'Добавление нового дня'];
} else {
    $this->title = 'Добавить день/дату в расписании';
    $this->params['breadcrumbs'][] = ['label' => 'Расписания', 'url' => ['/schedules']];
    $this->params['breadcrumbs'][] = $this->title;
}


?>
<div class="schedules-entries-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
