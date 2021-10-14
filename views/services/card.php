<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;
$comps=$model->comps;
$techs=$model->techs;
$services=$model->depends;
$dependants=$model->dependants;
$support=$model->support;
$children=$model->children;
$deleteable=!count($comps)&&!count($services)&&!count($dependants)&&!count($support)&&!count($techs)&&!count($children);
?>

<h1>
    <?= Html::encode($model->name) ?>
    <?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['services/update','id'=>$model->id])) ?>
    <?php if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['services/delete', 'id' => $model->id], [
	    'data' => [
		    'confirm' => 'Удалить этот сервис? Это действие необратимо!',
		    'method' => 'post',
	    ],
    ]); else { ?>
		<span class="small">
			<span class="fas fa-lock" title="Невозможно в данный момент удалить этот сервис, т.к. присутствуют привязанные объекты: привязанные пользователи, компьютеры или другие сервисы."></span>
		</span>
	<?php } ?>
</h1>
<div class="row">
	<div class="col-md-6">
		<h4>
			<?php
			if ($model->is_service) {
				echo $model->is_end_user?
					'<span class="fas fa-user service-icon"></span>'.\app\models\Services::$user_service_title:
					'<span class="fas fa-cog service-icon"></span>'.\app\models\Services::$tech_service_title;
			} else {
				echo $model->is_end_user?
					'<span class="fas fa-broom service-icon"></span>'.\app\models\Services::$user_job_title:
					'<span class="fas fa-screwdriver service-icon"></span>'.\app\models\Services::$tech_job_title;
			}
			
			if (is_object($model->segmentRecursive)) echo " // Сегмент ИТ: ".$this->render('/segments/item',['model'=>$model->segmentRecursive,'static_view'=>true]);
			?>
			<?php if (is_object($model->parent))  echo "<br /> Входит в состав: {$this->render('item',['model'=>$model->parent])}"; ?>
		</h4>
		<?php if ($model->cost) { ?>
			<h4>
				Стоимость: <?= $model->cost.''.$model->currency->symbol ?>
				<?php if ($model->charge){ ?>
					(в т.ч. НДС: <?= $model->charge.''.$model->currency->symbol ?>)
				<?php } ?> / мес.
			</h4>
		<?php } ?>

		<?php if (is_object($partner=$model->partner)) { ?>
			<strong>Контрагент:</strong> <?= $this->render('/partners/item',['model'=>$model->partner]) ?>
			<br />
		<?php } ?>
		
		<?php
		$schedules=[];
		if (!empty($model->providingScheduleRecursive)) {
			echo '<strong>Предоставляется:</strong> '.$this->render('/schedules/item',['model'=>$model->providingScheduleRecursive]);
			if (count($model->providingScheduleRecursive->getServicesArr())>1) { ?>
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
						]) ?>
					</span>
				</span>
			<?php }
			echo '<br />';
		}
		
		if (!empty($model->supportScheduleRecursive))
			echo '<strong>Поддерживается:</strong> '.$this->render('/schedules/item',['model'=>$model->supportScheduleRecursive]).'<br />';
		
		?>
		
		<?php if(!$static_view&&!$deleteable) { ?>
			<p>

			</p>
		<?php } ?>
		<br />
		<p>
			<?= Yii::$app->formatter->asNtext($model->description) ?>
		</p>
		<?= \app\components\UrlListWidget::Widget(['list'=>$model->links]) ?>
		<br />

		<?= $this->render('card-support',['model'=>$model,'static_view'=>$static_view]) ?>
		
		<?= $this->render('/acls/list',['models'=>$model->acls,'static_view'=>$static_view]) ?>
		

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
		
		<?php if (count($services)) { ?>
			<h4>Зависит от сервисов:</h4>
			<p>
				<?php
				foreach ($services as $service)
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
	</div>
</div>

<?php if (!$static_view && strlen($model->notebook)) { ?>
    <h4>Записная книжка:</h4>
    <p>
		<?= Markdown::convert($model->notebook) ?>
    </p>
    <br />
<?php } ?>

