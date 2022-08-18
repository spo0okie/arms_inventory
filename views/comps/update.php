<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */
if (!isset($modalParent)) $modalParent=null;
$domain = is_object($model->domain)?$model->domain->name:'- не в домене - ';
$name=$domain.'\\'.mb_strtolower($model->name);
$this->title = 'Правка: '.$name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Comps::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="comps-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
	]) ?>

</div>
