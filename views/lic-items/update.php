<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */

$this->title = 'Редактирование: ' . $model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicGroups::$titles, 'url' => ['lic-groups/index']];
$this->params['breadcrumbs'][] = ['label' => $model->licGroup->descr, 'url' => ['lic-groups/view','id'=>$model->lic_group_id]];
$this->params['breadcrumbs'][] = ['label' => $model->descr, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="lic-items-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
