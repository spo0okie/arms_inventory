<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Orgs */

$this->title = 'Новое юр. лицо';
$this->params['breadcrumbs'][] = ['label' => \app\models\Orgs::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="orgs-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
