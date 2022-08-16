<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

$domain = is_object($model->domain)?$model->domain->name:'- не в домене - ';

$this->title = 'ОС '.$domain.'\\'.strtolower($model->name);
$this->params['breadcrumbs'][] = ['label' => \app\models\Comps::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\helpers\Url::remember();
$manufacturers=\app\models\Manufacturers::fetchNames();
$model->swList->sortByName();
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

	$absorbTitle="Связать(поглотить) клона с этой ОС: недостающие свойства и связанные объекты клона перейдут к этой ОС. Сам клон будет удален";
?>
<div class="comps-view row">
	<div class="col-md-6">
		<?= $this->render('card',['model'=>$model]) ?>

		<div class="hardware_settings">
			<h4>Железо</h4>
			<table><?php
				foreach ($model->getHardArray() as $item) if (!$item->globIgnored()){
					echo $this->render('/hwlist/item',
						compact('model','item', 'manufacturers')
					);
				} ?>
			</table>
		</div>
		<div class="dupes">
			<?php if (count($model->dupes)) { ?>
				<h3>Подозрение на дубликаты в БД</h3>
				<?php foreach ($model->dupes as $comp) { ?>
					<?= $this->render('item',['model'=>$comp]) ?>
					<?= yii\helpers\Html::a('<span class="fas fa-link" title="'.$absorbTitle.'"></span>',
						['/comps/absorb','id'=>$model->id,'absorb_id'=>$comp->id]
					) ?>
					<br />
				<?php } ?>
			<?php } ?>
		</div>

	</div>
	<div class="col-md-6">
		<div class="software_settings">
			<h3>Софт</h3>
			<?php // echo '<pre>'; var_dump($model->swList->items); echo '</pre>'; ?>
			<table>
			<tr id="ignored_toggle">
				<td colspan="4">
					<h4 class="href" onclick="$('tr.ignored, span.ignored_hint').toggle()">Игнорируемый</h4>
					<?php if (count($soft['ignored'])) { ?><span class="ignored_hint"><?= count($soft['ignored']) ?> элементов скрыто</span> <?php } ?>
				</td>
			</tr>
				<?php foreach ($soft['ignored'] as $item) echo $this->render('/swlist/item', ['item'=>$item, 'model'=>$model,'classes'=>['ignored'],'style'=>'display:none']); ?>
			<tr id="ignored_toggle">
				<td colspan="4">
					<h4 class="href" onclick="$('tr.free, span.free_hint').toggle()">Бесплатный</h4>
					<?php if (count($soft['free'])) { ?><span class="free_hint"><?= count($soft['free']) ?> элементов скрыто</span> <?php } ?>
				</td>
			</tr>
				<?php foreach ($soft['free'] as $item) echo $this->render('/swlist/item', ['item'=>$item, 'model'=>$model,'classes'=>['free'],'style'=>'display:none']); ?>
			<tr id="ignored_toggle"><td><h4>Согласованный</h4></td></tr>
				<?php foreach ($soft['agreed'] as $item) echo $this->render('/swlist/item', compact('item', 'model')); ?>
			<tr id="ignored_toggle"><td><h4>Требующий согласования</h4></td></tr>
				<?php foreach ($soft['other'] as $item)	echo $this->render('/swlist/item', compact('item', 'model')); ?>
			<tr id="ignored_toggle"><td><h4>Не распознанный</h4></td></tr>
				<?php if (is_array($model->swList->data)) foreach ($model->swList->data as $item) { ?>
					<?= $this->render('/swlist/item_unrecognized', compact('model','item')) ?>
				<?php } ?>
			</table>
		</div>

	</div>
</div>