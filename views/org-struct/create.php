<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

$this->title = "Новое подразделение";

if (is_object($model->partner)) {
	$this->params['breadcrumbs'][] = ['label' => $model->partner->bname, 'url'=>['partners/view','id'=>$model->org_id]];
	$this->params['breadcrumbs'][] = ['label' => \app\models\OrgStruct::$titles, 'url' => ['index','org_id'=>$model->org_id]];
} else {
	$this->params['breadcrumbs'][] = ['label' => \app\models\OrgStruct::$titles, 'url' => ['index']];
}

foreach ($model->chain as $item) if ($item->id!=$model->id) $this->params['breadcrumbs'][]=[
	'label'=>$item->name,
	'url'=>['org-struct/view','id'=>$item->id],
];

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-struct-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
