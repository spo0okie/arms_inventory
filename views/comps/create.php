<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Comps */

$this->title = 'Добавление операционной системы';
$this->params['breadcrumbs'][] = ['label' => \app\models\Comps::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="comps-create">

    <h1><?= Html::encode($this->title) ?></h1>
    Внимание! Создание описания операционной системы вручную - исключительная ситуация.

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
