<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Techs */

if (!isset($modalParent)) $modalParent=null;
$this->title = 'Новая позиция';
$this->params['breadcrumbs'][] = ['label' => \app\models\Techs::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="techs-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
