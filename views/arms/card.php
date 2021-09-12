<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */
$static_view=true;

if (!isset($no_users)) $no_users=false;
if (!isset($no_specs)) $no_specs=false;

if (is_object($model->state)) {
	$statusName=$model->stateName;
	$statusCode=$model->state->code;
} else {
	$statusName='NULL';
	$statusCode='state_unknown';
}

?>

<div class="arms-card">
	<span class="unit-status <?= $statusCode ?>"><?= $statusName ?></span>
    <h3><?=
		$this->render('/arms/item',[
			'model'=>$model,
			'static_view'=>true,
			'no_ttip'=>true,
		])
	?></h3>

	<div>
		<?php if (strlen($model->sn)) {?>
			<span class="serial">S/N: <?= $model->sn ?></span><br/>
		<?php } ?>
		<?php if (strlen($model->inv_num)) {?>
			<span class="serial">Инв.№: <?= $model->inv_num ?></span><br/>
		<?php } ?>
		Модель:<?= $this->render('/tech-models/item',['model'=>$model->techModel,'static_view'=>true]) ?><br />
		Помещение:<?= $this->render('/places/item',['model'=>$model->place,'static_view'=>true,'full'=>true,'items_glue'=>' &gt; ']) ?><br />
	</div>

	<span class="divider1"></span>

	<h4><span class="clickable" onclick="$('#arm-hw').slideToggle()">Оборудование: <?= $this->render('/hwlist/shortlist',['model'=>$model->hwList]) ?></span></h4>
	<div id="arm-hw" class="data-block" style="display: none">
		<?= $this->render('hw',['model'=>$model,'static_view'=>$static_view]) ?>
	</div>

	
	<?php if (!$no_specs && is_object($model->techModel) && $model->techModel->individual_specs) { ?>
	<h4>Спецификация:</h4>
		<?= \Yii::$app->formatter->asNtext($model->specs) ?>
		<br />
	<?php } ?>

	<br />

	<h4>Привязанные ОС:</h4>
	<div class="data-block tree-level-2">
		<?php if (is_array($model->comps) && count ($model->comps)) {
			foreach ($model->comps as $comp) { ?>
				<div class="comps-card"> <?= $this->render('/comps/card',[
					'model'=>$comp,
					'static_view'=>$static_view,
					'no_arm'=>true,
					'no_abbr'=>true,
					'ips_glue'=>' ',
				]) ?></div><br/>
			<?php } } else { ?>
			отсутствуют
		<?php }?>
	</div>

	<br />

	<?php if (!$no_users) { ?>
		<h4>Сотрудники:</h4>
		Пользователь:<?= is_object($model->user)?$this->render('/users/item',['model'=>$model->user]):'-не назначен-' ?><br/>
		<?= is_object($model->head)?('Руководитель отдела:'.$this->render('/users/item',['model'=>$model->head]).'<br/>'):'' ?>
		<?= is_object($model->responsible)?('Ответственный:'.$this->render('/users/item',['model'=>$model->responsible]).'<br/>'):'' ?>
		<br />
	<?php } ?>

	
    <?php // $this->render('att-techs',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

	<span class="divider2"></span>
	
    <?= $this->render('arm-history',['model'=>$model,'static_view'=>$static_view]) ?>
	
	<?php if (is_object($model->itStaff)) { ?>
		<span class="it-staff">Сотрудник ИТ: <?= $this->render('/users/item',['model'=>$model->itStaff]) ?></span>
	<?php } ?>
	

</div>