<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=$model->isNewRecord;
$renderer=$this;
$today=Yii::$app->request->get('date')?
	Yii::$app->request->get('date'):	//если явно передали дату, ориентируемся на нее
	strtotime("today");	//иначе на сегодня


if ($model->isOverride) {
	$prefix='Изменение расписания';
	$limits=$model->getPeriodDescription();
	$hidden=$model->matchDate($today);
} else {
	$prefix='Основное расписание';
	$limits=$model->getPeriodDescription(); //полный период действия
	$match=$model->getWeekSchedule($today);
	$hidden=is_object($match)&&$match->id==$model->id;
}



$switchCard=new \app\components\CollapsableCardWidget([
	'title'=>"$prefix $limits<div class='comment'>{$model->description}</div>",
	'buttonClass'=>'schedule-item-preview flex-fill',
	'content'=>$this->render('item-edit',['model'=>$model]),
	'contentClass'=>'schedule-item-edit',
	'initialCollapse'=>!$hidden,
	'saveState'=>true,
	'id'=>'schedule-week-view-'.$model->id,
]);

\yii\widgets\Pjax::begin();?>
<div class="schedule-item-block">
	<div class="d-flex flex-row">
		<?= $switchCard->switcher() ?>
		<?php if ($model->isOverride){ ?>
			<div class="btn-group pull-right align-self-center" role="group">
				<?= Html::a('<span class="fas fa-pencil-alt"></span>',[
					'schedules/update',
					'id'=>$model->id,
					'return'=>'previous'
				],[
					'class'=>'btn btn-primary btn-sm open-in-modal-form',
					'data'=>['modal-pjax-reload'=>'auto',],
				]) ?>
				
				<?= Html::a('<span class="fas fa-trash"/>', [
					'schedules/delete',
					'id' => $model->id
				], [
					'data' => [
						'confirm' => 'Удалить этот период расписания? Действие необратимо',
						'method' => 'post',
					],
					'class'=>'btn btn-danger btn-sm',
				]) ?>
			</div>
		<?php } ?>
	</div>
	<?= $switchCard->card() ?>
</div>
<?php \yii\widgets\Pjax::end();