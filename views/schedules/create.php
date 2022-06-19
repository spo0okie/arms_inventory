<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($acl_mode)) $acl_mode=false;
if ($model->isOverride) {
	$this->title = 'Изменения в периоде дат';
	$this->params['breadcrumbs'][] = ['label' => \app\models\Schedules::$titles, 'url' => ['index']];
	$this->params['breadcrumbs'][] = ['label' => $model->overriding->name, 'url' => ['index']];
} elseif (!$acl_mode) {
	$this->title = 'Новое '.mb_strtolower(\app\models\Schedules::$title);
	$this->params['breadcrumbs'][] = ['label' => \app\models\Schedules::$titles, 'url' => ['index']];
} else {
	$this->title = 'Новый '.mb_strtolower(\app\models\Acls::$scheduleTitle);
	$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$scheduleTitles, 'url' => ['index-acl']];
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="schedules-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render($model->override_id?'_form_override':'_form', [
        'model' => $model,
		'acl_mode'=>$acl_mode,
    ]) ?>

</div>
