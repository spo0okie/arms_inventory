<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Sandboxes */

if (!isset($modalParent)) $modalParent=null;

$this->title = "Новая ".mb_strtolower(app\models\Sandboxes::$title);
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="sandboxes-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
