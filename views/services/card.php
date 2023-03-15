<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;
If (!is_object($model)) {
	echo "Сервис/услуга не найдены";
	return;
}
$comps=$model->comps;
$techs=$model->techs;
$dependants=$model->dependants;
$support=$model->support;
$children=$model->children;
$contracts=$model->contracts;

if(!$static_view) { ?>
<span class="float-end">
	<?= \app\components\ShowArchivedWidget::widget(['reload'=>false]) ?>
</span>

<?php } ?>


<h1>
	<?= \app\components\LinkObjectWidget::widget([
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
				echo $model->is_end_user?\app\models\Services::$user_service_title:\app\models\Services::$tech_service_title;
			} else {
				echo $model->is_end_user?\app\models\Services::$user_job_title:\app\models\Services::$tech_job_title;
			} ?>
			
			<?=	(is_object($model->segmentRecursive))?" // Сегмент ИТ: ".$this->render('/segments/item',['model'=>$model->segmentRecursive,'static_view'=>true]):'' ?>
			<?php if (is_object($model->parentService))  echo "<br /> Входит в состав: {$this->render('item',['model'=>$model->parentService])}"; ?>
		</h4>
		<?php if ($model->sumTotals) { ?>
		<strong>Стоимость:</strong> <span class="badge bg-success"><?= $model->sumTotals.''.$model->currency->symbol ?></span>
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
					'name'=>$model->providingScheduleRecursive->weekWorkTimeDescription
				]);
			if (!$static_view && count($model->providingScheduleRecursive->getServicesArr())>1) { ?>
				<span onmouseenter="$('#private_schedule').show()" onmouseleave="$('#private_schedule').hide()">
					<span qtip_ttip="Это расписание используется более чем для одного сервиса. <br> Невозможно добавлять периоды недоступности сервиса">
						<span class="fas fa-exclamation-triangle" ></span>
			    		<?= Html::a('Создать индивидуальное расписание',[
							'schedules/create',
							'attach_service'=>$model->id,
							'parent_id'=>$model->providingScheduleRecursive->id
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

		echo '<br />';
		
		if (!empty($model->supportScheduleRecursive))
			echo '<strong>Время поддержки:</strong> '.$this->render('/schedules/item',['model'=>$model->supportScheduleRecursive]).'<br />';
		
		
		?>
		
		<br />
		<p>
			<?= Yii::$app->formatter->asNtext($model->description) ?>
		</p>
		<?= \app\components\UrlListWidget::Widget(['list'=>$model->links]) ?>
		<br />

		<?= $this->render('card-support',['model'=>$model,'static_view'=>$static_view]) ?>
		
		<?= $this->render('/acls/list',['models'=>$model->acls,'static_view'=>$static_view]) ?>
		
		<br />
		
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
			<?= $static_view?'':Html::a('Добавить суб-сервис',['create','parent_id'=>$model->id],['class'=>'btn btn-success']).'<br />'?>
			<br />

		<?php if (count($comps)) { ?>
			<h4>Выполняется на компьютерах:</h4>
			<p>
				<?php
				foreach ($comps as $comp)
					echo $this->render('/comps/item',['model'=>$comp,'static_view'=>$static_view,'fqdn'=>true]).'<br />';
				?>
			</p>
			<br />
		<?php } ?>
		
		<?php if (count($techs)) { ?>
			<h4>Выполняется на оборудовании:</h4>
			<p>
				<?php
				foreach ($techs as $tech)
					echo $this->render('/techs/item',['model'=>$tech,'static_view'=>$static_view]).'<br />';
				?>
			</p>
			<br />
		<?php } ?>
		
		<?php if (count($depends=$model->depends)) { ?>
			<h4>Зависит от сервисов:</h4>
			<p>
				<?php
				foreach ($depends as $service)
					echo $this->render('/services/item',['model'=>$service,'static_view'=>$static_view]).'<br />';
				?>
			</p>
			<br />
		<?php } ?>
		
		<?php if (count($dependants)) { ?>
			<h4>Зависимые сервисы:</h4>
			<p>
				<?php
				foreach ($dependants as $service)
					echo $this->render('/services/item',['model'=>$service,'static_view'=>$static_view]).'<br />';
				?>
			</p>
			<br />
		<?php } ?>


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
	</div>
</div>

<?php if (!$static_view && strlen($model->notebook)) { ?>
    <h4>Записная книжка:</h4>
    <p>
		<?= Markdown::convert($model->notebook) ?>
    </p>
    <br />
<?php } ?>

