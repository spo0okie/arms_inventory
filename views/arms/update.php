<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */

if (!isset($modalParent)) $modalParent=null;
$this->title = 'Изменение АРМ: '.$model->num;
$this->params['breadcrumbs'][] = ['label' => 'АРМы', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->num, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изменение';
?>
<div class="arms-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
