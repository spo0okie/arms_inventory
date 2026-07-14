<?php

/**
 * Тултип временного доступа. В отличие от тултипа расписания (график по дням)
 * показывает роль объекта: кому, куда, на какой срок предоставлен доступ
 * и действует ли он прямо сейчас.
 */

use app\components\IsHistoryObjectWidget;
use app\components\ModelFieldWidget;
use app\models\Aces;
use app\models\Acls;
use app\modules\schedules\models\Schedules;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model Schedules|app\modules\schedules\models\SchedulesHistory */

//по GET timestamp сюда может прийти архивная версия (SchedulesHistory):
//у неё нет связи acls и вычисления активности — показываем только шапку
$live=$model instanceof Schedules;
$isWorkTime=$live && $model->isWorkTime(date('Y-m-d'),date('H:i:s'));
?>
<div class="scheduled-access-ttip ttip-card">
	<?= IsHistoryObjectWidget::widget(compact('model')) ?>
	<h1>
		<?= Html::encode($model->name) ?>
	</h1>
	<?php if (strlen($model->description??'')) { ?>
		<p class="small mb-1"><?= Html::encode($model->description) ?></p>
	<?php } ?>
	<?php if ($live) { ?>
		<div class="px-3 mb-2 text-center fw-bold <?= $isWorkTime?'bg-green-striped':'bg-red-striped' ?>">
			<?= $isWorkTime?'Сейчас доступ есть':'Сейчас доступа нет' ?>
		</div>

		<?php if (count($periods=$model->findPeriods())) { ?>
			<h3>Периоды доступа</h3>
			<div class="mb-2">
				<?php foreach ($periods as $period) { ?>
					<div>
						<?php /* d-inline-block: паддинг инлайнового span не раздвигает строки,
							и плашки соседних периодов нахлёстываются друг на друга */ ?>
						<span class="text-nowrap p-1 mb-1 d-inline-block <?= $period->is_work?'bg-success':'bg-danger' ?>"
							  title="<?= Html::encode($period->comment) ?>">
							<?= str_replace(' ','&nbsp;',$period->periodSchedule) ?>
						</span>
					</div>
				<?php } ?>
			</div>
		<?php } ?>

		<h3>Доступы</h3>
		<?php
		//relationForGrid: жадная загрузка ACL со связями (ACE, типы доступа, ресурсы),
		//группировка та же, что на странице просмотра: один набор ACE - одна карточка
		$groups=Acls::groupBySignatures($model->relationForGrid('acls'));
		if (!count($groups)) echo '- списков доступа нет -';
		?>
		<?php foreach ($groups as $group) { $acl=reset($group); ?>
			<div class="border-top py-1">
				<?= ModelFieldWidget::widget([
					'models'=>$group,
					'field'=>'resource',
					'title'=>false,
					'card_options'=>['cardClass'=>'m-0 p-0'],
					'lineBr'=>false,
					'item_options'=>['static_view'=>true,'icon'=>true,'no_class'=>true,'short'=>true],
					'glue'=>'<br>',
				]) ?>
				<?php foreach ($acl->aces as $ace) {
					$types=[];
					foreach ($ace->accessTypes as $type) $types[]=$type->name;
					if (!count($types)) $types[]=Aces::$noAccessName;
					?>
					<div class="small ps-3">
						<?= ModelFieldWidget::widget([
							'model'=>$ace,
							'field'=>'subjects',
							'title'=>false,
							'card_options'=>['cardClass'=>'m-0 p-0'],
							'lineBr'=>false,
							'item_options'=>['static_view'=>true,'icon'=>true,'no_class'=>true,'short'=>true],
							'glue'=>', ',
						]) ?>
						— <?= Html::encode(implode(', ',$types)) ?>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	<?php } ?>
</div>
