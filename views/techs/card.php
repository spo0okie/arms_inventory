<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
$model_id=$model->id;
if (!isset($static_view)) $static_view=false;
if (!isset($no_model)) $no_model=false; //не выводить инфу о модели оборудования
$deleteable=!count($model->materialsUsages) && !count($model->contracts_ids);

if (is_object($model->state)) { ?>
	<span class="unit-status <?= $model->state->code ?> "><?= $model->state->name ?></span>
<?php }?>

<h1>
	<?= \app\components\LinkObjectWidget::widget([
		'model'=>$model,
		'confirmMessage' => 'Удалить этот оборудование из базы (необратимо)?',
		'undeletableMessage'=>'Нельзя удалить это оборудование/АРМ, т.к. есть привязанные к нему объекты.<br> Может лучше проставить флажок &quot;архивировано&quot;?',
	]) ?>
</h1>

<div class="d-flex flex-row">
	<div class="pe-5">
		<?php if (!$no_model) { ?>
			Модель: <?= $this->render('/tech-models/item',['model'=>$model->model,'long'=>1]) ?> <br />
			Серийный №: <?= $model->sn ?> <br />
			Бухг. инв. №: <?= $model->inv_num ?> <br />
			<?php if (strlen($model->comment)){
				echo ('<b>'.$model->commentLabel.':</b> '.Yii::$app->formatter->asNtext($model->comment).'<br />');
			} ?>
			
			<?php if ($model->model->individual_specs) { ?>
				<h4>Спецификация:</h4>
				<?= \Yii::$app->formatter->asNtext($model->specs) ?>
				<br />
			<?php } ?>
		
		<?php } else { ?>
			<h4>Идентификаторы:</h4>
			<p>
				Серийный №: <?= $model->sn ?> <br />
				Бухг. инв. №: <?= $model->inv_num ?> <br />
				<?php if (strlen($model->comment)){
					echo ('<b>'.$model->commentLabel.':</b> '.Yii::$app->formatter->asNtext($model->comment).'<br />');
				} ?>
			</p>
		<?php } ?>
	</div>
	<div class="pe-1">
		<h4>Тех. обслуживание:</h4>
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
	</div>
</div>
<?= $this->render('attached/files',['model'=>$model,'static_view'=>$static_view]) ?>


<?php
	//для оборудования не АРМ выводим в список урл ссылку на IP устройства
	$urls=$model->url;
	$ips=$model->isComputer?'':$model->ip;
	if (strlen($urls.$ips)) { ?>
		<h4>Ссылки:</h4>
		<p>
			<?= \app\components\UrlListWidget::Widget(['list'=>$urls,'ips'=>$ips]) ?>
		</p>
<?php }	?>


<?php if ($model->isComputer) echo $this->render('/techs/attached/comps',['model'=>$model]) ?>


<h4>Место установки и сотрудники:</h4>
<p>
	<?= is_object($model->arm)?('АРМ: '.$this->render('/techs/item',['model'=>$model->arm]).'<br />'):'' ?>
	<?= is_object($model->installation)?('Установлено в: '.$this->render('/techs/item',['model'=>$model->installation]).'<br />'):'' ?>
    Помещение: <?= $this->render('/places/item',['model'=>$model->place]) ?> <br />
    Пользователь: <?= $this->render('/users/item',['model'=>$model->user]) ?> <br />
	<?= is_object($model->head)?('Руководитель отдела:'.$this->render('/users/item',['model'=>$model->head]).'<br/>'):'' ?>
	<?= is_object($model->itStaff)?('Сотрудник ИТ:'.$this->render('/users/item',['model'=>$model->itStaff]).'<br/>'):'' ?>
	<?= is_object($model->admResponsible)?($model->getAttributeLabel('responsible_id').':'.$this->render('/users/item',['model'=>$model->admResponsible]).'<br/>'):'' ?>
</p>

<?php if (count($model->services)) { ?>
	<h4>Участвует в работе сервисов:</h4>
	<p>
		<?php foreach ($model->services as $service) { ?>
			<?= $this->render('/services/item',['model'=>$service]) ?><br />
		<?php } ?>
	</p>
<?php } ?>

<div class="d-flex flex-row">
	<div class="pe-4">
		<?= $this->render('ips_list',compact('model')) ?>
	</div>
	<div class="pe-4">
		<h4>MAC адрес(а)</h4>
		<p>
			<?= Yii::$app->formatter->asNtext($model->formattedMac) ?>
		</p>
	</div>
</div>

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

<h4>Использованные материалы:</h4>
<p>
    <?php
	$materialsUsages=$model->materialsUsages;
	\app\helpers\ArrayHelper::multisort($materialsUsages,'date',SORT_DESC);
	foreach($materialsUsages as $materialsUsage) {
        echo $this->render('/materials-usages/item',['model'=>$materialsUsage,'material'=>true,'count'=>true,'cost'=>true,'date'=>true]).'<br />';
    } ?>
</p>

<h4>Заметки:</h4>
<?= Yii::$app->formatter->asNtext($model->history) ?><br />
