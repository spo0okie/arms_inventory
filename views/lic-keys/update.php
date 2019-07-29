<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */

$this->title = 'Редактирование лиц. ключа: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicKeys::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Обновление';
?>
<div class="lic-keys-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
