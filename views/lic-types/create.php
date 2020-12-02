<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

$this->title = 'Добавление схемы лицензирования';
$this->params['breadcrumbs'][] = ['label' => \app\models\LicTypes::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lic-types-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
