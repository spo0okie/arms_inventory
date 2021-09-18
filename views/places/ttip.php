<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

?>
<div class="ttip-card places-ttip">

    <h1>
        <?= Html::encode($model->name) ?>
        <?= Html::a('<span class="fas fa-pencil-alt"></span>', ['update', 'id' => $model->id]) ?>
    </h1>

	<?= ''//Html::a('<span class="fas fa-plus-circle"></span>Добавить новое оборудование', ['update', 'id' => $model->id]) ?>
</div>
