<?php

use app\components\HistoryWidget;
use app\components\IsArchivedObjectWidget;
use app\components\ShowArchivedWidget;
use app\models\Ports;
use app\models\Techs;
use yii\helpers\Html;
use yii\helpers\Url;
use app\components\widgets\page\ModelWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */

Url::remember();

$this->title = $model->num;
$this->params['breadcrumbs'][] = ['label' => Techs::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$archWidget=ShowArchivedWidget::widget(['reload'=>false]);

?>
<div class="techs-view">
	<?= IsArchivedObjectWidget::widget(['model'=>$model,'title'=>'Это оборудование перенесено в архив']) ?>
	<div class="row">
		<div class="col-md-6">
			<?= ModelWidget::widget(['model'=>$model, 'view'=>'card', 'options'=>['static_view'=>false, 'no_model'=>true]]) ?>
		</div>
		<div class="col-md-6">
			<div class="float-end text-end">
				<div class="text-end">
					<small class="float-end opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
				</div>
				<h1 class="text-end">
					<?php if ($model->isComputer) foreach (Yii::$app->params['arms.docs'] as $doc=>$params) if (is_array($params)) {
						echo Html::a($params['icon']??'<i class="fas fa-file"></i>',['docs','id'=>$model->id,'doc'=>$doc],[
							'qtip_ttip'=>$params[0]??'Документ АРМ',
							'qtip_side'=>'top',
							'class'=>'ms-2',
						]);
					}?>
					<?php if (!$model->isComputer) foreach (Yii::$app->params['techs.docs'] as $doc=>$params) if (is_array($params)) {
						echo Html::a($params['icon']??'<i class="fas fa-file"></i>',['docs','id'=>$model->id,'doc'=>$doc],[
							'qtip_ttip'=>$params[0]??'Документ оборудования',
							'qtip_side'=>'top',
							'class'=>'ms-2',
						]);
					}?>
					<?= Html::a('<i class="fas fa-images"></i>',['uploads','id'=>$model->id],[
						'qtip_ttip'=>'Редактировать изображения/фото этого оборудования',
						'qtip_side'=>'top'
					]) ?>
					
				</h1>
				<div class="text-end">
					<?= $archWidget ?>
				</div>
			</div>
			<div>
				<?php if (is_array($scans=$model->scans)&&count($scans)) foreach ($scans as $scan)
					echo ModelWidget::widget(['model'=>$scan, 'view'=>'/scans/thumb', 'options'=>['static_view'=>true]]);
				?>
			</div>
			<?= ModelWidget::widget(['model'=>$model, 'view'=>'model', 'options'=>[]]) ?>
			<h4><?= Ports::$titles ?></h4>
			<?= ModelWidget::widget(['model'=>$model, 'view'=>'ports', 'options'=>['static_view'=>false]]) ?>
		</div>
	</div>
</div>
