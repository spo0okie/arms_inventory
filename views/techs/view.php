<?php

use app\components\HistoryWidget;
use app\models\Ports;
use app\models\Techs;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */

Url::remember();

$this->title = $model->num;
$this->params['breadcrumbs'][] = ['label' => Techs::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="techs-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model,'static_view'=>false,'no_model'=>true]) ?>
		</div>
		<div class="col-md-6">
			<div class="float-end text-end">
				<small class="float-end opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
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
			</div>
			<div>
				<?php if (is_array($scans=$model->scans)&&count($scans)) foreach ($scans as $scan)
					echo $this->render('/scans/thumb',['model'=>$scan,'contracts_id'=>$model->id,'static_view'=>true]);
				?>
			</div>
			<?= $this->render('model',['model'=>$model]) ?>
			<h4><?= Ports::$titles ?></h4>
			<?= $this->render('ports',['model'=>$model,'static_view'=>false]) ?>
		</div>
	</div>
</div>
