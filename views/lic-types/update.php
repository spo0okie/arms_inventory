<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

$this->title = 'Редактирование: '.$model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicTypes::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lic-types-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
