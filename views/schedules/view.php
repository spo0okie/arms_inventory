<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

$acl_mode=(count($model->acls));
if (!isset($static_view)) $static_view=false;

$this->title = $model->name;
if (!$acl_mode) {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Schedules::$titles, 'url' => ['index']];
} else {
	$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$scheduleTitles, 'url' => ['index-acl']];
}
$this->params['breadcrumbs'][] = $this->title;

$providingServices=$model->providingServices;
$supportServices=$model->supportServices;
$acls=$model->acls;

$deleteable=!count($providingServices) && !count($supportServices) && !count($acls);

$schedule_id=$model->id;
\yii\web\YiiAsset::register($this);
?>
<div class="schedules-view">
	<h1>
		<?= $static_view?Html::a($model->name,['comps/view','id'=>$model->id]):$model->name ?>
		
		<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil" title="Изменить"></span>',['schedules/update','id'=>$model->id])) ?>
		
		<?php if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash" title="Удалить"/>', ['schedules/delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить это расписание? Это действие необратимо!',
				'method' => 'post',
			],
		]); else { ?>
			<span class="small">
			<span class="glyphicon glyphicon-lock" title="Невозможно в данный момент удалить это расписание, удалить можно только пустое расписание не привязанное ни к каким объектам."></span>
		</span>
		<?php } ?>&nbsp;
	</h1>
	<p><?= $model->description ?></p>

	<?php if (!$acl_mode) { ?>
		<div class="row">
			<div class="col-md-6">
				<?= $this->render('week',['model'=>$model])?>
				<?= $this->render('7days',['model'=>$model])?>
			</div>
			<div class="col-md-6">
				<?= $this->render('week-edit',['model'=>$model])?>
				<?= $this->render('exceptions',['model'=>$model])?>
			</div>
		</div>
	<?php } else { ?>

		<div class="row">
			<div class="col-md-6">
				<?= $this->render('acl',['model'=>$model]) ?>
			</div>
			<div class="col-md-6">
				<?= $this->render('exceptions',['model'=>$model]) ?>
			</div>
		</div>
		
	<?php if (strlen($model->history)) { ?>
		<h3>Записная книжка:</h3>
		<p>
			<?= Markdown::convert($model->history) ?>
		</p>
		<br />
	<?php }
	} ?>
</div>
