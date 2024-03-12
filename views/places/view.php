<?php

use app\components\LinkObjectWidget;
use app\components\ShowArchivedWidget;
use app\models\Places;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $models app\models\Places[] */
if (!isset($show_archived)) $show_archived=true;
if (!isset($static_view)) $static_view=false;

Url::remember();
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Places::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="places-view">
	<div class="d-flex flex-row-reverse flex-wrap">
		<div class="float-end text-end">
			<h1>
				<?= Html::a('<i class="fas fa-images"></i>',['uploads','id'=>$model->id],[
					'class'=>'float-end',
					'qtip_ttip'=>'Редактировать изображения/фото этого помещения',
					'qtip_side'=>'top'
				])?>
			</h1>
			<span class="float-end p-2"><?= ShowArchivedWidget::widget(['reload' => false]) ?></span>
		</div>
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
	<div class="d-flex flex-row justify-content-center">
		<?= $this->render('map/map',['model'=>$model]) ?>
	</div>

	<?= $this->render('container',['model'=>$model,'models'=>$models,'depth'=>0,'show_archived'=>$show_archived]) ?>
	<br />
	<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
</div>
