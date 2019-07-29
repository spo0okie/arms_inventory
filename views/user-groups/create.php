<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */

$this->title = 'Новая группа пользователей';
$this->params['breadcrumbs'][] = ['label' => \app\models\UserGroups::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-groups-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
