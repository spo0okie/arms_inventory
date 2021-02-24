<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$this->title = "Новый ".app\models\NetIps::$title;
$this->params['breadcrumbs'][] = ['label' => app\models\NetIps::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="net-ips-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
