<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */

$this->title = 'Новый '.\app\models\LicGroups::$title;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicGroups::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lic-groups-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
