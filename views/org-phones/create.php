<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

$this->title = 'Добавление услуги телефонии';
$this->params['breadcrumbs'][] = ['label' => \app\models\OrgPhones::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-phones-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
