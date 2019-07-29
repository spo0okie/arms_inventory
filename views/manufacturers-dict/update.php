<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ManufacturersDict */

$this->title = 'Изменить написание "'.$model->word.'"';
$this->params['breadcrumbs'][] = ['label' => 'Словарь производетелей', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="manufacturers-dict-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
