<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

if (!isset($modalParent)) $modalParent=null;

$this->title = "Новый ".app\models\Segments::$title;
$this->params['breadcrumbs'][] = ['label' => app\models\Segments::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="segments-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
