<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Scans */

$this->title = 'Редактирование: '. $model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\Scans::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->descr, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="scans-update">

    <h1><?= Html::encode($this->title) ?><?= \app\components\CopyObjectWidget::widget(['model'=>$model]) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
