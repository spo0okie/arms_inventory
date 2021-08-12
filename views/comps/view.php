<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

$domain = is_object($model->domain)?$model->domain->name:'- не в домене - ';

$this->title = 'ОС '.$domain.'\\'.strtolower($model->name);
$this->params['breadcrumbs'][] = ['label' => 'ОС', 'url' => ['index']];
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
					<?= yii\helpers\Html::a('<span class="glyphicon glyphicon-link" title="'.$absorbTitle.'"></span>',
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
			<h4 id="ignored_toggle">Игнорируемый</h4>
			<table>
				<?php foreach ($soft['ignored'] as $item) echo $this->render('/swlist/item', compact('item', 'model')); ?>
			</table>

			<h4 id="ignored_toggle">Бесплатный</h4>
			<table>
				<?php foreach ($soft['free'] as $item) echo $this->render('/swlist/item', compact('item', 'model')); ?>
			</table>

			<h4>Согласованный</h4>
			<table>
				<?php foreach ($soft['agreed'] as $item) echo $this->render('/swlist/item', compact('item', 'model')); ?>
			</table>

			<h4>Требующий согласования</h4>
			<table>
				<?php foreach ($soft['other'] as $item)	echo $this->render('/swlist/item', compact('item', 'model')); ?>
			</table>

			<h4>Не распознанный:</h4>
			<table>
				<?php if (is_array($model->swList->data)) foreach ($model->swList->data as $item) { ?>
					<?= $this->render('soft_item_unrecognized', compact('model','item')) ?>
				<?php } ?>
			</table>
		</div>

	</div>
</div>