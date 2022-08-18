<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

if (!isset($modalParent)) $modalParent=null;


$this->title = 'Ввод поступления материалов';
$this->params['breadcrumbs'][] = ['label' => \app\models\Materials::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="materials-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
