<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */

$acl_mode=(is_object($model->master) && (count($model->master->acls)));

if (!$acl_mode)
	$this->params['breadcrumbs'][] = ['label' => \app\models\Schedules::$titles, 'url' => ['/schedules/index']];
else
	$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$scheduleTitles, 'url' => ['/schedules/index-acl']];

if (is_object($model->master) && ($model->date)) {
    $this->title = 'Добавить график '.$model->dayFor;

    $this->params['breadcrumbs'][] = ['label' => $model->master->name, 'url' => ['/schedules/view', 'id'=>$model->schedule_id]];
    $this->params['breadcrumbs'][] = ['label' => 'Добавление '.$model->day];
} elseif (is_object($model->master)) {
	$this->params['breadcrumbs'][] = ['label' => $model->master->name, 'url' => ['/schedules/view', 'id'=>$model->schedule_id]];

	if ($model->is_period) {
		$this->title = 'Добавить период';
		$this->params['breadcrumbs'][] = ['label' => 'Добавление нового периода'];
	} else {
		$this->title = 'Добавить расписание '.$model->master->name.' / Новый день';
		$this->params['breadcrumbs'][] = ['label' => 'Добавление нового дня'];
	}

} else {
    $this->title = 'Добавить день/дату в расписании';
    $this->params['breadcrumbs'][] = $this->title;
}


?>
<div class="schedules-entries-create">

    <h1><?= Html::encode($this->title) ?></h1>
	Для <?= $model->master->name ?>

    <?= $this->render('_form', [
        'model' => $model,
		'acl_mode' => $acl_mode
    ]) ?>

</div>
