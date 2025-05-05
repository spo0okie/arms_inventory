<?php

use app\models\HwIgnore;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\HwIgnore */

$this->title = 'Обновление';
$this->params['breadcrumbs'][] = ['label' => HwIgnore::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->comment, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="hw-ignore-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
