<?php

/** @var yii\web\View $this */
/** @var app\models\Comps $model */

use app\components\HistoryWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\helpers\FieldsHelper;
use app\models\Comps;
use app\models\HwListItem;
use app\models\Manufacturers;
use yii\helpers\Html;
use yii\helpers\Url;
use app\components\widgets\page\ModelWidget;

$domain = is_object($model->domain)?$model->domain->name:'- не в домене - ';

$this->title = 'ОС '.$domain.'\\'.strtolower($model->name);
//крошки собираются автоматически в layout (views/layouts/main.php)
Url::remember();
$manufacturers= Manufacturers::fetchNames();
$model->swList->sortByName();

$skipMonitors=is_object($model->arm)?($model->arm->monitorsCount):false;

$soft=[
	'free'=>[],
	'ignored'=>[],
	'agreed'=>[],
	'other'=>[]
];
foreach ($model->swList->items as $item) {
	if ($item['ignored']) {
		$soft['ignored'][]=$item;
		continue;
	}

	if ($item['free']) {
		$soft['free'][]=$item;
		continue;
	}

	if ($item['agreed']) {
		$soft['agreed'][]=$item;
		continue;
	}

	$soft['other'][]=$item;
}

	$absorbTitle='Связать (поглотить) клона с этой ОС: недостающие свойства, привязки '
		.'(связанные объекты) и журнал входов клона перейдут к этой ОС. Сам клон будет удалён';
	$archWidget=ShowArchivedWidget::widget(['reload'=>false]);

	//qtip-подсказки категорий согласования софта (см. docs/help/models/comps/raw_soft.md
	//и docs/help/guides/arm-passport.md)
	$softHints=[
		'ignored'=>'Продукты из служебного списка ПО «Игнорируемое» (справочник «Списки ПО»): '
			.'системные компоненты, драйверы и прочий шум. Лицензионный учёт не нужен.<br>'
			.'Раздел свёрнут — клик по заголовку раскрывает/скрывает список.',
		'free'=>'Продукты из служебного списка ПО «Бесплатное»: лицензионный учёт не нужен.<br>'
			.'Раздел свёрнут — клик по заголовку раскрывает/скрывает список.',
		'agreed'=>'Продукты из реестра согласованного ПО — разрешённые к использованию.',
		'other'=>'Распознанные продукты, не входящие ни в один из служебных списков ПО. '
			.'Это рабочая очередь: софт нужно либо согласовать (внести в реестр согласованного ПО), '
			.'либо запретить/удалить с машины.',
		'unrecognized'=>'Строки отпечатка софта, которые не удалось сопоставить ни с одним '
			.'программным продуктом. Кнопка «Создать продукт из этого элемента» заводит новый '
			.'продукт прямо из строки; после появления продукта строка при следующем рескане '
			.'попадёт в одну из категорий выше.',
	];
?>

<div class="comps-view row">
	<div class="col-md-6">
		<?= $this->render('card',['model'=>$model,'ips_glue'=>'<br/>']) ?>

		<div class="hardware_settings">
			<?= ModelFieldWidget::renderCompositeTitle($model,['raw_hw','exclude_hw'],'Железо') ?>
			<table><?php
				foreach ($model->getHardArray() as $item) if (!$item->globIgnored()){
					$classes=[];
					$style='';
					if ($item->type== HwListItem::$TYPE_MONITOR && $skipMonitors) {
						$classes[]='archived-item';
						$style="style='".ShowArchivedWidget::archivedDisplay(true)."'";
					}
					echo $this->render('/hwlist/item',
						compact('model','item', 'manufacturers','classes','style')
					);
				} ?>
			</table>
		</div>
		<div class="dupes">
			<?php if (count($model->dupes)) { ?>
				<?= Html::tag('h3','Подозрение на дубликаты в БД',FieldsHelper::toolTipOptions(
					'Подозрение на дубликаты в БД',
					'Записи ОС с таким же именем хоста в этом же окружении/песочнице — '
						.'вероятно, клоны одной и той же машины.'
				)) ?>
				<?php foreach ($model->dupes as $comp) { ?>
 					<?= ModelWidget::widget(['model'=>$comp]) ?>
					<?= Html::a('<span class="fas fa-link"></span>',
						['/comps/absorb','id'=>$model->id,'absorb_id'=>$comp->id],
						FieldsHelper::toolTipOptions('Поглотить клона',$absorbTitle)
					) ?>
					<br />
				<?php } ?>
			<?php } ?>
		</div>

	</div>
	<div class="col-md-6">
		<div class="text-end">
			<small class="float-end opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
			<br />
			<?= $archWidget ?>
		</div>
		<div class="software_settings">
			<?= ModelFieldWidget::renderCompositeTitle($model,['raw_soft','soft_ids'],'Софт','h3') ?>
			<?php
			echo ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'softRescans',
				'card_options'=>['cardClass'=>'alert-striped mb-3'],
			]);
			?>
			<table>
			<tr id="ignored_toggle">
				<td colspan="4">
					<?= Html::tag('h4','<span class="text-muted">&#9656;</span> Игнорируемый',array_merge(
						['class'=>'href','onclick'=>"$('tr.ignored, span.ignored_hint').toggle()"],
						FieldsHelper::toolTipOptions('Игнорируемый софт',$softHints['ignored'])
					)) ?>
					<?php if (count($soft['ignored'])) { ?><span class="ignored_hint"><?= count($soft['ignored']) ?> элементов скрыто</span> <?php } ?>
				</td>
			</tr>
				<?php foreach ($soft['ignored'] as $item) echo $this->render('/swlist/item', ['item'=>$item, 'model'=>$model,'classes'=>['ignored'],'style'=>'display:none']); ?>
			<tr id="ignored_toggle">
				<td colspan="4">
					<?= Html::tag('h4','<span class="text-muted">&#9656;</span> Бесплатный',array_merge(
						['class'=>'href','onclick'=>"$('tr.free, span.free_hint').toggle()"],
						FieldsHelper::toolTipOptions('Бесплатный софт',$softHints['free'])
					)) ?>
					<?php if (count($soft['free'])) { ?><span class="free_hint"><?= count($soft['free']) ?> элементов скрыто</span> <?php } ?>
				</td>
			</tr>
				<?php foreach ($soft['free'] as $item) echo $this->render('/swlist/item', ['item'=>$item, 'model'=>$model,'classes'=>['free'],'style'=>'display:none']); ?>
			<tr id="ignored_toggle"><td><?= Html::tag('h4','Согласованный',
				FieldsHelper::toolTipOptions('Согласованный софт',$softHints['agreed'])) ?></td></tr>
				<?php foreach ($soft['agreed'] as $item) echo $this->render('/swlist/item', compact('item', 'model')); ?>
			<tr id="ignored_toggle"><td><?= Html::tag('h4','Требующий согласования',
				FieldsHelper::toolTipOptions('Софт, требующий согласования',$softHints['other'])) ?></td></tr>
				<?php foreach ($soft['other'] as $item)	echo $this->render('/swlist/item', compact('item', 'model')); ?>
			<tr id="ignored_toggle"><td><?= Html::tag('h4','Не распознанный',
				FieldsHelper::toolTipOptions('Не распознанный софт',$softHints['unrecognized'])) ?></td></tr>
				<?php if (is_array($model->swList->data)) foreach ($model->swList->data as $item) { ?>
					<?= $this->render('/swlist/item_unrecognized', compact('model','item')) ?>
				<?php } ?>
			</table>
		</div>

	</div>
</div>
