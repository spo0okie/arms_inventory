<?php

use app\components\HistoryWidget;
use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\Acls;
use kartik\markdown\Markdown;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Acls::$scheduleTitles, 'url' => ['index']];
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
		<div class="small opacity-75 me-3"><?= HistoryWidget::widget(['model'=>$model]) ?></div>
		<div class="flex-fill">
			<h1>
				<?= ItemObjectWidget::widget([
					'model'=>$model,
					'link'=> LinkObjectWidget::widget([
						'model'=>$model,
						'static'=>$static_view,
						'hideUndeletable'=>false,
						'controller'=>'scheduled-access', //кастомный контроллер для расписаний доступа
					])
				]) ?>&nbsp;
			</h1>
		</div>
	</div>
	
	
	<p><?= $model->description ?></p>

	<div class="row">
		<div class="col-md-6">
			<?= $this->render('acl',['model'=>$model]) ?>
		</div>
		<div class="col-md-6">
			<?= $this->render('exceptions',['model'=>$model]) ?>
			<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
			<?php if (strlen($model->history)) { ?>
				<br /><br />
				<h3>Записная книжка:</h3>
				<p>
					<?= Markdown::convert($model->history) ?>
				</p>
			<?php } ?>
		</div>
	</div>
</div>
