<?php

use app\components\IsArchivedObjectWidget;
use app\components\IsHistoryObjectWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\TextFieldWidget;
use app\models\HistoryModel;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */
if (!isset($static_view)) $static_view=false;
if (!isset($no_arm)) $no_arm=false; //спрятать АРМ
if (!isset($no_abbr)) $no_abbr=false; //спрятать АРМ
if (!isset($ips_glue)) $ips_glue=null;
if (is_object($model)) {
$services=$model->services;
$deleteable=!count($services);
$fqdn=mb_strtolower($model->fqdn);


$rcIcon=Html::tag('i','',['class'=>"fas fa-sign-in-alt"]);
$remoteControl=(is_object($model->sandbox)&&!$model->sandbox->network_accessible)?
	Html::tag('span',$rcIcon,[
		'class'=>'text-muted',
		'qtip_ttip'=>'Сетевой доступ к ОС/ВМ отсутствует, т.к. она находится в изолированном окружении/песочнице',
		'qtip_side'=>'bottom',
	]):
	Html::a($rcIcon,'remotecontrol://'.$model->fqdn,[
		'qtip_ttip'=>'Удаленное управление {$model->fqdn}',
		'qtip_side'=>'bottom',
	]);

	if ($model instanceof HistoryModel) {
		echo IsHistoryObjectWidget::widget(['model'=>$model]);
	} else { ?>
		<span class="unit-status <?= $model->updatedRenderClass ?> href" onclick="$('#comp<?= $model->id ?>-updated-info').toggle()"><?= $model->updatedText ?></span>
		<br />
	<?php } ?>
	<?= IsArchivedObjectWidget::widget(['model'=>$model]) ?>

<h1>
	<?= LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$this->render('/domains/hostname',[
				'model'=>$model,
				'hostname'=>$model->name
			]),
			'nameSuffix'=>$remoteControl,
			'hideUndeletable'=>false,
	]) ?>
</h1>

<div>
	<?= $model->os ?><br />
	<span id="comp<?= $model->id ?>-updated-info" class="update-timestamp" style="display: none">Последнее обновление данных <?= $model->updated_at ?> (v. <?= $model->raw_version ?>)</span>
</div>
<div class="mb-3">
	<?= is_object($model->responsible)?'<strong>Ответственный:</strong>'.$this->render('/users/item',['model'=>$model->responsible,'static_view'=>true]).'<br />':'' ?>
	<?php if (count($model->supportTeam)) { ?>
		<strong>Поддержка:</strong>
		<?php
		$support=[];
		foreach ($model->supportTeam as $mate) $support[]= $this->render('/users/item',['model'=>$mate,'static_view'=>true,'short'=>true]);
		echo implode(', ',$support);
		?>
		<br />
	<?php } ?>
	<?php if (count($model->admins)) { ?>
		<strong>Полномочия администратора:</strong>
		<?php
		$admins=[];
		foreach ($model->admins as $mate) $admins[]= $this->render('/users/item',['model'=>$mate,'static_view'=>true,'short'=>true]);
		echo implode(', ',$admins);
		?>
		<br />
	<?php } ?>
	<?= TextFieldWidget::widget(['model'=>$model,'field'=>'comment']) ?>
</div>
<div class="d-flex flex-row flex-wrap mb-3">
	<?php if (is_object($model->platform)) {?>
		<div class="pe-5">
			<h4>Платформа</h4>
			<p>
				<?= $this->render('/services/item',['model'=>$model->platform,'static_view'=>$static_view]) ?>
			</p>
		</div>
	<?php } elseif (!$no_arm) { ?>
		<div class="pe-5">
			<h4>АРМ</h4>
			<p>
				<?php if (is_object($model->arm)) { ?>
					<?= $this->render('/techs/item',['model'=>$model->arm,'static_view'=>$static_view]) ?>
				<?php } else { ?>
					не назначен
				<?php } ?>
			</p>
		</div>
	<?php } ?>
	<?= $this->render('ips_list',['model'=>$model,'static_view'=>$static_view,'glue'=>$ips_glue]) ?>
	<?= $this->render('lics_list',['model'=>$model,'static_view'=>$static_view]) ?>
</div>
	
	<?php if (count($model->services)||count($model->effectiveMaintenanceReqs)||count($model->maintenanceJobs)) { ?>
		<div class="d-flex flex-row flex-wrap">
			<?= ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'services',
				'title'=>'Участвует в работе сервисов:',
				'card_options'=>['cardClass'=>'pe-5 mb-3']
			]) ?>
			<?= ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'effectiveMaintenanceReqs',
				'title'=>'Требует обслуживания:',
				'card_options'=>['cardClass'=>'pe-5 mb-3']
			]) ?>
			<?= ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'maintenanceJobs',
				'title'=>'Обслуживается:',
				'card_options'=>['cardClass'=>'pe-5 mb-3']
			]) ?>
		</div>
	<?php } ?>
	
<?= $this->render('/acls/list',['models'=>$model->acls,'static_view'=>$static_view]) ?>
<?= $this->render('/aces/list',['models'=>$model->aces,'static_view'=>$static_view]) ?>

<div class="login_journal">
	<h4>Журнал входов (3 посл)</h4>
	<?php
	$logons=$model->lastThreeLogins;
	//$logons=$model->logins;
	if (is_array($logons) && count($logons)) {
		$items=[];
		foreach ($logons as $logon) {
			echo $this->render('/login-journal/item-user',['model'=>$logon]).'<br />';
		}
	}?>
</div>
<?php }