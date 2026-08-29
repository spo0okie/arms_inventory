<?php

use app\components\IsArchivedObjectWidget;
use app\components\IsHistoryObjectWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\TextFieldWidget;
use app\components\widgets\page\ModelWidget;
use app\models\HistoryModel;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Comps $model */
if (!isset($static_view)) $static_view=false;
if (!isset($no_arm)) $no_arm=false; //спрятать АРМ
if (!isset($no_abbr)) $no_abbr=false; //спрятать АРМ
if (!isset($ips_glue)) $ips_glue=null;
if (is_object($model)) {
$services=$model->services;
$deleteable=!count($services);
$fqdn=mb_strtolower($model->fqdn);


$rcIcon=Html::tag('i','',['class'=>"fas fa-sign-in-alt"]);
//data-doc-anchor: цели подсветки из документации (docs/help/models/comps.md)
$remoteControl=(is_object($model->sandbox)&&!$model->sandbox->network_accessible)?
	Html::tag('span',$rcIcon,[
		'class'=>'text-muted',
		'data-doc-anchor'=>'remote-control',
		'qtip_ttip'=>'Сетевой доступ к ОС/ВМ отсутствует, т.к. она находится в изолированном окружении/песочнице',
		'qtip_side'=>'bottom',
	]):
	Html::a($rcIcon,'remotecontrol://'.$model->fqdn,[
		'data-doc-anchor'=>'remote-control',
		'qtip_ttip'=>"Удаленное управление {$model->fqdn}<br>"
			.'<a href="'.\yii\helpers\Url::to(['/docs/page','path'=>'admin/integrations/remote-control.md']).'">Как настроить обработчик протокола</a>',
		'qtip_side'=>'bottom',
	]);

	if ($model instanceof HistoryModel) {
		echo IsHistoryObjectWidget::widget(['model'=>$model]);
	} else { ?>
		<span class="unit-status <?= $model->updatedRenderClass ?> href" data-doc-anchor="updated-age" onclick="$('#comp<?= $model->id ?>-updated-info').toggle()" qtip_ttip="Давность последних данных от скрипта инвентаризации.<br>Цвет: до часа — ярко-голубой, до суток — бледно-голубой, до недели — зелёный, до месяца — жёлтый, свыше — красный.<br>Клик — дата обновления и версия скрипта."><?= $model->updatedText ?></span>
		<br />
	<?php } ?>
	<?= IsArchivedObjectWidget::widget(['model'=>$model]) ?>

<h1>
	<?= LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$this->render('/domains/hostname',[
				'model'=>$model,
				'hostname'=>$model->renderName()
			]),
			'nameSuffix'=>$remoteControl,
			'hideUndeletable'=>false,
	]) ?>
</h1>

<div>
	<?= \app\components\ModelFieldWidget::renderFieldValue($model,'os') ?><br />
	<span id="comp<?= $model->id ?>-updated-info" class="update-timestamp" style="display: none">Последнее обновление данных <?= $model->updated_at ?> (v. <?= $model->raw_version ?>)</span>
</div>
<div class="mb-3">
	<?php $rows=implode('<br />',array_filter([
		ModelFieldWidget::renderFieldRow($model,'responsible',['item_options'=>['static_view'=>true]],'strong'),
		ModelFieldWidget::renderFieldRow($model,'supportTeam',[
			'item_options'=>['static_view'=>true,'short'=>true],
			'glue'=>', ',
			'lineBr'=>false,
		],'strong'),
		ModelFieldWidget::renderFieldRow($model,'admins_ids',[
			'item_options'=>['static_view'=>true,'short'=>true],
			'glue'=>', ',
			'lineBr'=>false,
		],'strong'),
	]));
	echo $rows?($rows.'<br />'):''; ?>
	<?= ModelFieldWidget::renderFieldValue($model,'comment') ?>
</div>

<div class="d-flex flex-row flex-wrap" data-doc-anchor="bindings">
	<?php if (is_object($model->platform)) {?>
		<div class="pe-5">
			<?= ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'platform_id',
				'item_options'=>['static_view'=>$static_view],
			]) ?>
		</div>
	<?php } elseif (!$no_arm) { ?>
		<div class="pe-5">
			<?= ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'arm_id',
				'item_options'=>['static_view'=>$static_view],
				'show_empty'=>true,
				'message_on_empty'=>'не назначен',
			]) ?>
			<?php /* АРМ не назначен, но MAC есть - предложим найденное по нему
			       оборудование (issue #218) */ ?>
			<?= $this->render('arm-suggestion',['model'=>$model,'static_view'=>$static_view]) ?>
		</div>
	<?php } ?>
	<?= $this->render('ips_list',['model'=>$model,'static_view'=>$static_view,'glue'=>$ips_glue]) ?>
	<?= $this->render('lics_list',['model'=>$model,'static_view'=>$static_view]) ?>
</div>

	<?php if (count($model->services)||count($model->effectiveMaintenanceReqs)||count($model->maintenanceJobs)) { ?>
		<div class="d-flex flex-row flex-wrap" data-doc-anchor="services-maintenance">
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

<div data-doc-anchor="access">
	<?= $this->render('/acls/list',['models'=>$model->acls,'static_view'=>$static_view]) ?>
	<?= $this->render('/aces/list',['models'=>$model->aces,'static_view'=>$static_view]) ?>
</div>

<?php if (is_array($lastLogins=$model->lastThreeLogins) && count($lastLogins)) { ?>
	<div data-doc-anchor="login-journal">
		<?= ModelFieldWidget::renderFieldTitle($model,'lastThreeLogins') ?>
		<p class="mb-3">
		 <?php foreach ($lastLogins as $logon)
			echo $this->render('/login-journal/item-user',['model'=>$logon,'suffix'=>' <br />']); ?>
		</p>
	</div>
<?php }	?>

<?php }
