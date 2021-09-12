<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = 'Изменение сотрудника: '.$model->Ename ;
$this->params['breadcrumbs'][] = ['label' => \app\models\Users::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->Ename, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изменить';
?>
<div class="users-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Табельный номер:<?= $model->employee_id ?></h3>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
