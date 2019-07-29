<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Soft */

$this->title = 'Добавить ПО';
$this->params['breadcrumbs'][] = ['label' => 'ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="soft-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
