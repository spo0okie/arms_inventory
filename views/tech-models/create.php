<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
if (!isset($modalParent)) $modalParent=null;

$this->title = 'Новая модель оборудования';
if ($model->type_id){
    $this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index']];
	$this->params['breadcrumbs'][] = ['label' => $model->type->name, 'url' => ['/tech-types/view','id'=>$model->type_id]];
} else {
	$this->params['breadcrumbs'][] = ['label' => \app\models\TechModels::$title, 'url' => ['/tech-types/index']];
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tech-models-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent
    ]) ?>

</div>
