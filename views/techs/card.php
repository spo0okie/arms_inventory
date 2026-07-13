<?php

use app\components\IsHistoryObjectWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\UrlListWidget;
use app\helpers\ArrayHelper;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Techs */
$model_id=$model->id;
if (!isset($static_view)) $static_view=false;
if (!isset($no_model)) $no_model=false; //не выводить инфу о модели оборудования

$name=$model->hostname?$this->render('/domains/hostname',[
	'model'=>$model,
	'hostname'=>$model->hostname,
]):$model->num;


if (is_object($model->state)) { ?>
	<span class="unit-status <?= $model->state->markerClass($model->state->code) ?> " style="<?= $model->state->markerStyle() ?>"><?= $model->state->name ?></span>

<?php }?>

<?= IsHistoryObjectWidget::widget(['model' => $model,'class'=>'me-2']) ?>



<h1>
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'confirmMessage' => 'Удалить этот оборудование из базы (необратимо)?',
		'undeletableMessage'=>'Нельзя удалить это оборудование/АРМ, т.к. есть привязанные к нему объекты.<br> Может лучше проставить флажок &quot;архивировано&quot;?',
	]) ?>
</h1>

<div class="d-flex flex-row">
	<div class="pe-5">
		<?php if (!$no_model) { ?>
			<?= implode('<br />',array_filter([
				ModelFieldWidget::renderFieldRow($model,'model_id',['item_options'=>['long'=>1]]),
				ModelFieldWidget::renderFieldRow($model,'sn'),
				ModelFieldWidget::renderFieldRow($model,'inv_num'),
				//label динамический (commentLabel), значение - по правилу
				strlen((string)$model->comment)?
					('<b>'.$model->commentLabel.':</b> '.ModelFieldWidget::renderFieldValue($model,'comment')):'',
			])) ?><br />

			<?php if ($model->model->individual_specs) { ?>
				<?= ModelFieldWidget::renderFieldTitle($model,'specs') ?>
				<?= ModelFieldWidget::renderFieldValue($model,'specs') ?>
				<br />
			<?php } ?>

		<?php } else { ?>
			<h4>Идентификаторы:</h4>
			<p>
				<?= implode('<br />',array_filter([
					$model->hostname?ModelFieldWidget::renderFieldRow($model,'num'):'',
					ModelFieldWidget::renderFieldRow($model,'sn'),
					ModelFieldWidget::renderFieldRow($model,'inv_num'),
					strlen((string)$model->comment)?
						('<b>'.$model->commentLabel.':</b> '.ModelFieldWidget::renderFieldValue($model,'comment')):'',
				])) ?>
			</p>
		<?php } ?>
	</div>
	<div class="pe-1">
		<h4>Тех. обслуживание:</h4>
		<?= implode('<br />',array_filter([
			ModelFieldWidget::renderFieldRow($model,'responsible',['item_options'=>['static_view'=>true]],'strong'),
			ModelFieldWidget::renderFieldRow($model,'supportTeam',[
				'item_options'=>['static_view'=>true,'short'=>true],
				'glue'=>', ',
				'lineBr'=>false,
			],'strong'),
		])) ?>
	</div>
</div>


<div class="d-flex flex-row mb-3">
	<div class="pe-5">
		<h4>Место установки и сотрудники:</h4>
		<p>
			<?= implode('<br />',array_filter([
				ModelFieldWidget::renderFieldRow($model,'arms_id'),
				ModelFieldWidget::renderFieldRow($model,'installed_id'),
				ModelFieldWidget::renderFieldRow($model,'places_id'),
				ModelFieldWidget::renderFieldRow($model,'user_id'),
				ModelFieldWidget::renderFieldRow($model,'head'),
				ModelFieldWidget::renderFieldRow($model,'responsible_id'),
			])) ?>
		</p>
	</div>

	<?php if ($model->isComputer) echo '<div class="pe-4">'.$this->render('/techs/attached/comps',['model'=>$model]).'</div>' ?>

</div>


<div class="d-flex flex-row flex-wrap">
	<div class="pe-4 mb-3">
		<?= $this->render('ips_list',compact('model')) ?>
	</div>
	<div class="pe-5 mb-3">
		<?= ModelFieldWidget::renderFieldTitle($model,'mac') ?>
		<p class="mb-0">
			<?= ModelFieldWidget::renderFieldValue($model,'mac') ?>
		</p>
	</div>

	<?php
	//для оборудования не АРМ выводим в список урл ссылку на IP устройства
	//композиция двух атрибутов (url+ip) - не атрибут, законное исключение из правила ModelFieldWidget
	$urls=$model->url;
	$ips=$model->isComputer?'':$model->ip;
	if (strlen($urls.$ips)) { ?>
	<div class="pe-5 mb-3">
		<h4>Ссылки:</h4>
			<?= UrlListWidget::Widget(['list'=>$urls,'ips'=>$ips]) ?>
	</div>
	<?php }	?>
	<div class="pe-5 mb-3">
		<?= $this->render('attached/files',['model'=>$model,'static_view'=>$static_view]) ?>
	</div>
</div>

<?php if (count($model->services)||count($model->effectiveMaintenanceReqs)||count($model->maintenanceJobs)) { ?>
	<div class="d-flex flex-row flex-wrap">
		<?= ModelFieldWidget::widget([
			'model'=>$model,
			'field'=>'services',
			'label'=>'Участвует в работе сервисов:',
			'card_options'=>['cardClass'=>'pe-5 mb-3']
		]) ?>
		<?= ModelFieldWidget::widget([
			'model'=>$model,
			'field'=>'effectiveMaintenanceReqs',
			'label'=>'Требует обслуживания:',
			'card_options'=>['cardClass'=>'pe-5 mb-3']
		]) ?>
		<?= ModelFieldWidget::widget([
			'model'=>$model,
			'field'=>'maintenanceJobs',
			'label'=>'Обслуживается:',
			'card_options'=>['cardClass'=>'pe-5 mb-3']
		]) ?>
	</div>
<?php } ?>

<?= $this->render('/techs/attached/contracts',['model'=>$model,'static_view'=>$static_view]) ?>

<?php if (count($model->licItems) || count($model->licGroups) || count($model->licKeys)) {
	echo $this->render('/techs/attached/lics',['model'=>$model]);
} ?>


<?php if (count($model->armTechs)) {
	echo $this->render('/techs/attached/techs',['model'=>$model]);
} ?>


<?php if (count($model->installedTechs)) {
	echo $this->render('/techs/attached/installed',['model'=>$model]);
} ?>

<?= $this->render('/acls/list',['models'=>$model->acls,'static_view'=>$static_view]) ?>

<?= ModelFieldWidget::renderFieldTitle($model,'materialsUsages') ?>
<p>
    <?php
	$materialsUsages=$model->materialsUsages;
	ArrayHelper::multisort($materialsUsages,'date',SORT_DESC);
	foreach($materialsUsages as $materialsUsage) {
        echo ModelWidget::widget(['model'=>$materialsUsage,'options'=>['material'=>true,'count'=>true,'cost'=>true,'date'=>true]]).'<br />';
    } ?>
</p>

<?= ModelFieldWidget::renderFieldTitle($model,'history') ?>
<?= ModelFieldWidget::renderFieldValue($model,'history') ?><br />
