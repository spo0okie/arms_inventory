<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

if (!isset($modalParent)) $modalParent=null;


$this->title = 'Ввод поступления материалов';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="materials-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
