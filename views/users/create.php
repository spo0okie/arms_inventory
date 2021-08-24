<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = 'Заведение сотрудника';
$this->params['breadcrumbs'][] = ['label' => \app\models\Users::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-create">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        Обратите внимание, что заводить сотрудника вручную нужно лишь в том случае, если он по какой-то причине отстутствует
        в БД управления персоналом, откуда импортируются все сотрудники. В таком случае ему нужно выдать "табельный" номер не из
        пула, откуда выдаются табельные номера в БД HR, чтобы избежать коллизий.
    </p>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
