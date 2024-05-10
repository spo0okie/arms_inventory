<?php

use app\models\Services;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($modalParent)) $modalParent=null;
$this->title = 'Редактирование: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Services::$titles, 'url' => ['index']];
$model->recursiveBreadcrumbs($this,'parentService','nameWithoutParent');
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="services-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
