<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

$this->title = 'Правка: ' . $model->name;

$this->render('breadcrumbs',['partner'=>$model->partner,'model'=>$model]);

$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="org-struct-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
