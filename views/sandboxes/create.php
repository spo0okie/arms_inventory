<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Sandboxes */

if (!isset($modalParent)) $modalParent=null;

$this->title = "Новая ".mb_strtolower(app\models\Sandboxes::$title);
$this->params['breadcrumbs'][] = ['label' => app\models\Sandboxes::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sandboxes-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
