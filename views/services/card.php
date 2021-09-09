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
    <?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['services/update','id'=>$model->id])) ?>
    <?php if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['services/delete', 'id' => $model->id], [
	    'data' => [
		    'confirm' => 'Удалить этот сервис? Это действие необратимо!',
		    'method' => 'post',
	    ],
    ]); else { ?>
		<span class="small">
			<span class="glyphicon glyphicon-lock" title="Невозможно в данный момент удалить этот сервис, т.к. присутствуют привязанные объекты: привязанные пользователи, компьютеры или другие сервисы."></span>
		</span>
	<?php } ?>
</h1>
<div class="row">
	<div class="col-md-6">
		<h4>
			(<?php
			echo $model->is_end_user?'Предоставляется пользователям':'Внутренний сервис';
			if (is_object($model->segmentRecursive)) echo " // Сегмент ИТ: ".$this->render('/segments/item',['model'=>$model->segment,'static_view'=>true]);
			?>)
			<?php if (is_object($model->parent))  echo "<br /> Входит в состав: {$this->render('item',['model'=>$model->parent])}"; ?>
		</h4>
		<?php
		$schedules=[];
		if (!empty($model->providingScheduleRecursive))
			$schedules[]='<strong>Предоставляется:</strong> '.$model->providingScheduleRecursive->name;
		
		if (!empty($model->supportScheduleRecursive))
			$schedules[]='<strong>Поддерживается:</strong> '.$model->supportScheduleRecursive->name;
		
		if (count($schedules)) {
			echo implode('; ',$schedules).'<br />';
		}
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
		<?php if (count($children)) { ?>
			<h2>Содержит в составе:</h2>
			<p>
				<?php
				foreach ($children as $service)
					echo $this->render('/services/item',['model'=>$service,'static_view'=>$static_view]).'<br />';
				?>
			</p>
			<br />
		<?php } ?>

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

