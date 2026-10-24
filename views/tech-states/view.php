<?php

use app\components\LinkObjectWidget;

use app\components\ModelFieldWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\TechStates */

$this->title = $model->name;
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="tech-states-view">

	<h1>
		<?php // Шапка использует общую проверку возможности удаления. ?>
		<?= LinkObjectWidget::widget([
			'model' => $model,
			'name' => Html::encode($this->title),
			'hideUndeletable' => false,
			'confirmMessage' => 'Удалить эту запись?',
		]) ?>
	</h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            ModelFieldWidget::detailAttribute($model,'id'),
            ModelFieldWidget::detailAttribute($model,'code'),
            ModelFieldWidget::detailAttribute($model,'name'),
            ModelFieldWidget::detailAttribute($model,'archived:boolean'),
            ModelFieldWidget::detailAttribute($model,'operating:boolean'),
            ModelFieldWidget::detailAttribute($model,'descr:ntext'),
        ],
    ]) ?>

</div>
