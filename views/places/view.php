<?php

use app\components\LinkObjectWidget;
use app\components\widgets\page\CornerWidget;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $models app\models\Places[] */
if (!isset($show_archived)) $show_archived=true;
if (!isset($static_view)) $static_view=false;

Url::remember();
$this->title = $model->name;
include 'breadcrumbs.php';

?>
<div class="places-view">
	<div class="d-flex flex-row-reverse flex-wrap">
		<?php CornerWidget::begin(['model'=>$model]) ?>
			<h1>
				<?= Html::a('<i class="fas fa-images"></i>',['uploads','id'=>$model->id],[
					'qtip_ttip'=>'Редактировать изображения/фото этого помещения',
					'qtip_side'=>'top'
				])?>
			</h1>
		<?php CornerWidget::end() ?>
		<div class="flex-fill flex-row flex-wrap d-flex justify-content-end">
			<?php if (is_array($scans=$model->scans)&&count($scans))
				foreach ($scans as $scan)
					if ($scan->id != $model->map_id)
						echo $this->render('/scans/thumb',['model'=>$scan,'contracts_id'=>$model->id,'static_view'=>true]);
			?>
		</div>
		<div>
			<h1>
				<?= LinkObjectWidget::widget([
					'model'=>$model,
					'ttipUrl'=>false,
					'hideUndeletable'=>false
				]) ?>
			</h1>
			<?= $this->render('hdr_create_obj',['places_id'=>$model->id]) ?>
		</div>
	</div>
	<?php if ($model->map_id) { ?>
		<div class="d-flex flex-row justify-content-center my-3">
			<?= $this->render('map/map',['model'=>$model]) ?>
		</div>
	<?php } ?>

	<?= $this->render('container',['model'=>$model,'models'=>$models,'depth'=>0,'show_archived'=>$show_archived]) ?>
	<br />
	<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
</div>
