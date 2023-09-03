<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = 'Изменение пароля: '.$model->Ename ;
$this->params['breadcrumbs'][] = ['label' => \app\models\Users::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->Ename, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Пароль';
?>
<div class="users-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_passwd', [
        'model' => $model,
    ]) ?>

</div>
