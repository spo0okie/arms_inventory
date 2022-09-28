<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsTypes */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\MaterialsTypes::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="materials-types-view">
	<div class="row">
		<div class="col-md-8">
			<h1>
				<?= Html::encode($this->title) ?>
				<?= Html::a('<span class="fas fa-pencil-alt"/>', ['update', 'id' => $model->id]) ?>
				<?= \app\components\DeleteObjectWidget::widget([
					'confirm'=>'Удалить этот тип материалов?',
					'undeletable'=>'Невозможно сейчас удалить этот тип материалов,<br>т.к. заведены материалы этого типа',
					'links'=>[$model->materials],
					'model'=>$model,
				]) ?>
			</h1>
			<p>	<?= $model->comment ?> </p>
		</div>
		<div class="col-md-2">
			<?= DetailView::widget([
				'model' => $model,
				'attributes' => [
					'code',
					'units',
				],
			]) ?>
		</div>
	</div>
	



</div>
