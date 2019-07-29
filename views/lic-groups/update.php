<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */

$this->title = 'Редактирование лицензий: '.$model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicGroups::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->descr, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="lic-groups-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
