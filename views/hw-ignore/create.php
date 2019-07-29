<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\HwIgnore */

$this->title = 'Create Hw Ignore';
$this->params['breadcrumbs'][] = ['label' => 'Hw Ignores', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="hw-ignore-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
