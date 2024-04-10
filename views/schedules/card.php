<?php

use app\components\HistoryWidget;
use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\components\ListObjectsWidget;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;
?>
<div class="schedules-view">
	<div class="d-flex flex-wrap flex-row-reverse">
		<div class="small opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></div>
		<div class="flex-fill">
			<h1>
				<?= ItemObjectWidget::widget([
					'model'=>$model,
					'link'=> LinkObjectWidget::widget([
						'model'=>$model,
						'static'=>$static_view,
						'hideUndeletable'=>false,
					])
				]) ?>&nbsp;
			</h1>
		</div>
	</div>
	<?= $model->description?('<p>'.$model->description.'</p>'):'' ?>
	
	<div class="row">
		<div class="col-md-6">
			<h3 class="mb-3"><?= $this->render('week-description',['model'=>$model])?></h3>
			<?= is_object($model->parent)?('Родительское расписание :'.$this->render('item',['model'=>$model->parent])):'' ?>
			<?= $this->render('7days',['model'=>$model])?>
			<?= $this->render('services',['model'=>$model])?>
			<?= ListObjectsWidget::widget([
				'models'=>$model->childrenNonOverrides,
				'title'=>$model->getAttributeLabel('children')
			]) ?>
			<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
		</div>
		<div class="col-md-6">
			<?= $this->render('week/list',['model'=>$model])?>
			<?= $this->render('exceptions',['model'=>$model])?>
		</div>
	</div>
	<?php if (strlen($model->history)) { ?>
		<h3>Записная книжка:</h3>
		<p>
			<?= Markdown::convert($model->history) ?>
		</p>
		<br />
	<?php } ?>
</div>
