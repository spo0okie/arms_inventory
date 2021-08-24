<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($acl_mode)) $acl_mode=false;

if (!$acl_mode) {
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

    <?= $this->render('_form', [
        'model' => $model,
		'acl_mode'=>$acl_mode,
    ]) ?>

</div>
