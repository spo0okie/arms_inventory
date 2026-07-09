<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\OldArms */

if (!isset($modalParent)) $modalParent=null;
$this->title = 'Добавление АРМ';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="arms-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
