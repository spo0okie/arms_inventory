<?php

use app\components\HistoryWidget;
use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\components\ListObjectsWidget;
use app\models\Acls;
use app\models\Schedules;
use kartik\markdown\Markdown;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

$acl_mode=(count($model->acls));
if (!isset($static_view)) $static_view=false;

$this->title = $model->name;
if (!$acl_mode) {
	$this->params['breadcrumbs'][] = ['label' => Schedules::$titles, 'url' => ['index']];
} else {
	$this->params['breadcrumbs'][] = ['label' => Acls::$scheduleTitles, 'url' => ['index-acl']];
}
$this->params['breadcrumbs'][] = $this->title;
Url::remember();

$providingServices=$model->providingServices;
$supportServices=$model->supportServices;
$acls=$model->acls;

$deleteable=!count($providingServices) && !count($supportServices) ;

$schedule_id=$model->id;
YiiAsset::register($this);
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
	<p><?= $model->description ?></p>
	
	
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('week-description',['model'=>$model])?>
			<?= is_object($model->parent)?('Родительское расписание :'.$this->render('item',['model'=>$model->parent])):'' ?>
			<?= $this->render('7days',['model'=>$model])?>
			<?= $this->render('services',['model'=>$model])?>
			<?= ListObjectsWidget::widget([
				'models'=>$model->children,
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
