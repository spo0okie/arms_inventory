<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=$model->isNewRecord;
$renderer=$this;
$today=strtotime("today");


if ($model->isOverride) {
	$prefix='Изменение расписания';
	$limits=$model->getPeriodDescription();
	$hidden=$model->matchDate($today)?'':'style="display:none;"';
} else {
	$prefix='Основное расписание';
	$limits=$model->getPeriodDescription(); //полный период действия
	$todayLimits=\app\models\Schedules::generatePeriodDescription($model->findPeriodLimits($today)); //с ограничениями справа и слева от сегодня
	if ($limits!=$todayLimits) {//если изменения в расписании вносят правки в период действия расписания
		if (strlen($limits)) //если у расписания есть явный период действия то дополняем его
			$limits.="\n(с учетом изменений в расписании $todayLimits)";
		else //иначе заменяем
			$limits=$todayLimits;
	}
	$match=$model->findEffectiveWeekSchedule($today);
	$hidden=is_object($match)&&$match->id==$model->id?'':'style="display:none;"';
	
}


?>

<div class="schedule-item-block">
	<div class="d-flex flex-row">
		<div class="schedule-item-preview flex-fill" onclick="$(this).parents('div.schedule-item-block').children('div.schedule-item-edit').fadeToggle()">
			<?= $prefix.' '.$limits ?>
		</div>
		<?php if ($model->isOverride){ ?>
			<div class="btn-group pull-right" role="group">
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
