<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Departments */


?>
<div class="departments-ttip ttip-card">
    <h1>
	    <?= Html::encode($this->title) ?>
    </h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'comment:ntext',
        ],
    ]) ?>

</div>
