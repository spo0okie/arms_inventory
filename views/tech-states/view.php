<?php

use app\components\ModelFieldWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\TechStates */

$this->title = $model->name;
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="tech-states-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Изменить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Удалить эту запись?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            ModelFieldWidget::detailAttribute($model,'id'),
            ModelFieldWidget::detailAttribute($model,'code'),
            ModelFieldWidget::detailAttribute($model,'name'),
            ModelFieldWidget::detailAttribute($model,'archived:boolean'),
            ModelFieldWidget::detailAttribute($model,'descr:ntext'),
        ],
    ]) ?>

</div>
