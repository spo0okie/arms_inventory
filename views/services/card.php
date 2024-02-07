<?php

use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ListObjectsWidget;
use app\components\ModelFieldWidget;use app\components\ShowArchivedWidget;
use app\components\StripedAlertWidget;
use app\components\UrlListWidget;
use app\models\Services;
use yii\helpers\Html;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;
If (!is_object($model)) {
	echo "Сервис/услуга не найдены";
	return;
}
$comps=$model->comps;
$dependants=$model->dependants;
$support=$model->support;
$children=$model->children;
$contracts=$model->contracts;

if(!$static_view) { ?>
<span class="float-end text-end">
	<small class="float-end opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
	<br />
	<?= ShowArchivedWidget::widget(['reload'=>false]) ?>
</span>

<?php } ?>


<h1>
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'static'=>$static_view,
		//'confirm' => 'Удалить этот сервис? Это действие необратимо!',
		'hideUndeletable'=>false
	]) ?>
</h1>
<div class="row">
	<div class="col-md-6">
		<h4>
			<?= $this->render('icon',compact('model')) ?>
			<?php
			
			if ($model->is_service) {
				echo $model->is_end_user? Services::$user_service_title: Services::$tech_service_title;
			} else {
				echo $model->is_end_user? Services::$user_job_title: Services::$tech_job_title;
			} ?>
			
			<?=	(is_object($model->segmentRecursive))?" // Сегмент ИТ: ".$this->render('/segments/item',['model'=>$model->segmentRecursive,'static_view'=>true]):'' ?>
			<?php if (is_object($model->parentService))  echo "<br /> Входит в состав: {$this->render('item',['model'=>$model->parentService])}"; ?>
		</h4>
		<div class="mb-3">
		<?php if ($model->sumTotals) { ?>
		<strong>Стоимость:</strong> <span class="badge bg-success"><?= number_format($model->sumTotals,0,'',' ').' '.$model->currency->symbol ?></span>
			<?php if ($model->sumCharge){ ?>
				(в т.ч. НДС: <?= $model->sumCharge.''.$model->currency->symbol ?>)
			<?php } ?> / мес.<br/>
		<?php }
		//var_dump(\app\models\ContractsStates::fetchUnpaidIds());
		//var_dump($model->docs[0]->allChildren);
		if (count($model->totalUnpaid)) {
			$debt=[];
			foreach ($model->totalUnpaid as $currency=>$total)
				$debt[]=$total.''.$currency;
			echo 'Долг (с '.$model->firstUnpaid.'): '.implode(', ',$debt).'<br />';
		} ?>

	
		<?php
		$schedules=[];
		if (!empty($model->providingScheduleRecursive)) {
			echo '<strong>Время предоставления: </strong>'
				.$this->render('/schedules/item',[
					'model'=>$model->providingScheduleRecursive,
					'name'=>$model->providingScheduleRecursive->usageWorkTimeDescription
				]);
			if (!$static_view && count($model->providingScheduleRecursive->getServicesArr())>1) { ?>
				<span onmouseenter="$('#private_schedule').show()" onmouseleave="$('#private_schedule').hide()">
					<span qtip_ttip="Это расписание используется более чем для одного сервиса. <br> Невозможно добавлять периоды недоступности сервиса">
						<span class="fas fa-exclamation-triangle" ></span>
			    		<?= Html::a('Создать индивидуальное расписание',[
							'schedules/create',
							'attach_service'=>$model->id,
							'Schedules[parent_id]'=>$model->providingScheduleRecursive->id
						],[
							'id'=>'private_schedule',
							'style'=>'display:none'
						]); ?>
					</span>
				</span>
			<?php }
		} elseif (!$static_view) echo Html::a('Создать расписание предоставления сервиса/услуги',[
			'schedules/create',
			'attach_service'=>$model->id,
		],[
			'id'=>'private_schedule',
			//'style'=>'display:none'
		]);

		
		if (!empty($model->supportScheduleRecursive)) {
			if (!empty($model->providingScheduleRecursive)) echo '<br />';
			echo '<strong>Время поддержки:</strong> '.$this->render('/schedules/item',['model'=>$model->supportScheduleRecursive]);
		} ?>
			<?php
			if (
				$model->is_service //не услуга (а сервис)
				&& (
					count($model->comps)	//не чисто организационный узел (только для дочерних сервисов)
					|| 						//а содержит реальные серверы или оборудование
					count($model->techs)	//т.е. есть что-то что крутится на серверах/железе
				)
				&&
				!count($model->backupReqs)	//и никто не хочет это бэкапить
				&&
				Yii::$app->params['services.no_backup.warn']	//и можно ругаться
			) echo StripedAlertWidget::widget(['title'=>'Отсутствуют требования к резервному копированию!']); ?>
		</div>
		
		<div class="row">
			<div class="col-6">
				<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'backupReqs']) ?>
				<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'otherReqs']) ?>
			</div>
			<div class="col-6">
				<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'maintenanceJobs']) ?>
			</div>
		</div>
		
		<div class="mb-3">
			<?= Yii::$app->formatter->asNtext($model->description) ?>
		</div>
		<?= UrlListWidget::Widget(['list'=>$model->links]) ?>
		<br />

		<?= $this->render('card-support',['model'=>$model,'static_view'=>$static_view]) ?>
		
		<?= $this->render('/acls/list',['models'=>$model->acls,'static_view'=>$static_view]) ?>
		
		<?php if (is_array($model->orgInets) && count($model->orgInets)) { ?>
			<h4>Предоставляет ввод(ы) интернет:</h4>
			<p>
				<?php foreach ($model->orgInets as $inet)
					echo $this->render('/org-inet/card',['model'=>$inet,'static_view'=>$static_view]);
				?>
			</p>
			<br />
		<?php }
		
		if (is_array($model->orgPhones) && count($model->orgPhones)) { ?>
			<h4>Предоставляет телефонию:</h4>
			<div class="d-flex flex-row flex-wrap p-0">
				<?php foreach ($model->orgPhones as $phone) { ?>
				
						<?= $this->render('/org-phones/card',['model'=>$phone,'static_view'=>$static_view,'href'=>true]) ?>
				
				<?php } ?>
			</div>
			<br />
		<?php } ?>


	</div>
	<div class="col-md-6">
		
			<h2>Содержит в составе:</h2>
			<p>
				<?php if (count($children)) { ?>
					<?= $this->render('/services/tree-list',['model'=>$model]); ?>
				<?php } else {?>
					Нет суб-сервисов
				<?php } ?>
			</p>
			<?= $static_view?'':Html::a('Добавить суб-сервис',[
					'create','Services'=>['parent_id'=>$model->id]
				],[
					'class'=>'btn btn-success'
				]).'<br />'?>
			<br />
		
		<?= ListObjectsWidget::widget([
			'models'=>$comps,
			'title'=>'Выполняется на компьютерах:',
			'item_options'=>['static_view'=>$static_view,'fqdn'=>true],
			'card_options'=>['cardClass'=>'mb-3'],
		]) ?>
		
		<?= ListObjectsWidget::widget([
			'models'=>$model->techs,
			'title'=>'Выполняется на оборудовании:',
			'item_options'=>['static_view'=>$static_view,],
			'card_options'=>['cardClass'=>'mb-3'],
		]) ?>
		
		<?= ListObjectsWidget::widget([
			'models'=>$model->depends,
			'title'=>'Зависит от сервисов:',
			'item_options'=>['static_view'=>$static_view,],
			'card_options'=>['cardClass'=>'mb-3'],
		]) ?>
		
		<?= ListObjectsWidget::widget([
			'models'=>$dependants,
			'title'=>'Зависимые сервисы:',
			'item_options'=>['static_view'=>$static_view,],
			'card_options'=>['cardClass'=>'mb-3'],
		]) ?>

		<?php if (is_object($model->partner)) { ?>
			<hr/>
			<h2>Контрагент: <?= $this->render('/partners/item',['model'=>$model->partner]) ?></h2>
			
			<?= $this->render('/partners/support',['model'=>$model->partner]) ?>
		<?php } ?>
		
		<?php if (count($contracts)) { ?>
			<h4>Карта связей документов</h4>
			<p>
				<?php foreach($contracts as $contract)
					echo $this->render('/contracts/tree-map',['model'=>$contract,'show_payment'=>true])
				?>
			</p>
			<br/>
		<?php } ?>

		<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>

	</div>
</div>

<?php if (!$static_view && strlen($model->notebook)) { ?>
    <h4>Записная книжка:</h4>
    <p>
		<?= Markdown::convert($model->notebook) ?>
    </p>
    <br />
<?php } ?>

