<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */

$this->title = "Новый ".\app\models\AccessTypes::$title;
$this->params['breadcrumbs'][] = ['label' => \app\models\AccessTypes::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="access-types-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
