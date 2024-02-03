<?php

use app\components\HintIconWidget;
use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsTypes */
?>

<div class="d-flex flex-row">
	<div class="me-5">
		<h1>
			<?= LinkObjectWidget::widget([
				'model'=>$model,
				'confirmMessage'=>'Удалить этот тип материалов?',
				'undeletableMessage'=>'Невозможно сейчас удалить этот тип материалов,<br>т.к. заведены материалы этого типа',
				'links'=>[$model->materials],
			]) ?>
		</h1>
		<p>	<?= $model->comment ?> </p>
	</div>
	<div class="me-5">
		<?= DetailView::widget([
			'model' => $model,
			'attributes' => [
				'code',
				'units',
			],
		]) ?>
	</div>
	<div class="me-5">
		<?php if (is_array($scans=$model->scans)&&count($scans)) foreach ($scans as $scan)
			echo $this->render('/scans/thumb',[
				'model'=>$scan,
				'static_view'=>true,
				'tile_class'=>'mb-3',
				'img_class'=>'d-block',
			]);
		?>
	</div>
	<div class="flex-fill text-end">
		<small class="opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
		<br>
		<?= HintIconWidget::widget(['model' => '\app\models\Materials', 'cssClass' => 'fs-1 me-2 opacity-50']) ?>
		<?= Html::a('<i class="fas fa-images fs-1"></i>',['uploads','id'=>$model->id],[
			'qtip_ttip'=>'Редактировать изображения/фото этой категории материалов',
			'qtip_side'=>'top'
		]) ?>
	</div>
	
	



</div>
