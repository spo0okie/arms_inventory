<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

$this->title = "Новое подразделение";

$this->render('breadcrumbs',['partner'=>$model->partner,'model'=>$model->parent]);

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-struct-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
