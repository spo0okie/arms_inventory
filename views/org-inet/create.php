<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

$this->title = 'Новое подключение интернет';
$this->params['breadcrumbs'][] = ['label' => \app\models\OrgInet::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-inet-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
