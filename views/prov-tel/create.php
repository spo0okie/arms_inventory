<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\ProvTel */

$this->title = 'Создание нового оператора связи';
$this->params['breadcrumbs'][] = ['label' => \app\models\ProvTel::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="prov-tel-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
