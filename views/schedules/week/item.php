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
	$hidden=$model->matchDate($today)?'':'style="display:none;"';
} else {
	$prefix='Основное расписание';
	$limits=$model->getPeriodDescription(); //полный период действия
	$match=$model->getWeekSchedule($today);
	$hidden=is_object($match)&&$match->id==$model->id?'':'style="display:none;"';
	
}


?>

<div class="schedule-item-block">
	<div class="d-flex flex-row">
		<div class="schedule-item-preview flex-fill" onclick="$(this).parents('div.schedule-item-block').children('div.schedule-item-edit').fadeToggle()">
			<?= $prefix.' '.$limits ?>
			<div class="comment"><?= $model->description ?></div>
		</div>
		<?php if ($model->isOverride){ ?>
			<div class="btn-group pull-right align-self-center" role="group">
				<?= Html::a('<span class="fas fa-pencil-alt"></span>',[
					'schedules/update',
					'id'=>$model->id,
					'return'=>'previous'
				],[
					'class'=>'btn btn-primary btn-sm open-in-modal-form'
				]) ?>
				
				<?= Html::a('<span class="fas fa-trash"/>', [
					'schedules/delete',
					'id' => $model->id
				], [
					'data' => [
						'confirm' => 'Удалить этот период расписания? Действие необратимо',
						'method' => 'post',
					],
					'class'=>'btn btn-danger btn-sm'
				]) ?>
			</div>
		<?php } ?>
	</div>
	<div class="schedule-item-edit" <?= $hidden ?> >
		<?= $this->render('item-edit',['model'=>$model]) ?>
	</div>
</div>
