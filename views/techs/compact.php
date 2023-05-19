<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
$static_view=true;

if (!isset($no_users)) $no_users=false;
if (!isset($no_specs)) $no_specs=false;

if (!isset($show_archived)) $show_archived=Yii::$app->request->get('showArchived',true);

if (is_object($model->state)) {
	$statusName=$model->stateName;
	$statusCode=$model->state->code;
} else {
	$statusName='NULL';
	$statusCode='state_unknown';
}

?>

<div class="arms-card <?= $model->archived?'archived-item':'' ?>" <?= \app\helpers\HtmlHelper::ArchivedDisplay($model,$show_archived) ?>>
	<span class="unit-status <?= $statusCode ?>"><?= $statusName ?></span>
    <h3><?=
		$this->render('/techs/item',[
			'model'=>$model,
			'static_view'=>true,
			'no_ttip'=>true,
		])
	?></h3>

	<div class="row">
		<div class="col-md-<?= strlen($model->comment)?6:12 ?>">
			<?php if (strlen($model->sn)) {?>
				<span class="serial">S/N: <?= $model->sn ?></span><br/>
			<?php } ?>
			<?php if (strlen($model->inv_num)) {?>
				<span class="serial">Инв.№: <?= $model->inv_num ?></span><br/>
			<?php } ?>
			Модель:<?= $this->render('/tech-models/item',['model'=>$model->model,'static_view'=>true]) ?><br />
			Помещение:<?= $this->render('/places/item',['model'=>$model->place,'static_view'=>true,'full'=>true,'items_glue'=>' &gt; ']) ?><br />
		</div>
		<?php if (strlen($model->comment)) { ?>
			<div class="col-md-6">
				<div class="comment-block" >
					<img class="exclamation-sign" src="/web/img/exclamation-mark.svg" /><br/>
					<?= $model->comment ?>
				</div>
			</div>
		<?php } ?>
	</div>

	<span class="divider1"></span>

	<h4><span class="clickable" onclick="$('#arm<?= $model->id ?>-hw').slideToggle()">Оборудование: <?= $this->render('/hwlist/shortlist',['model'=>$model->hwList]) ?></span></h4>
	<div id="arm<?= $model->id ?>-hw" class="data-block" style="display: none">
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
		<?php if (is_array($comps=$model->comps) && count ($comps)) {
			foreach ($model->sortedComps as $comp) { ?>
				<div class="comps-card <?= $comp->archived?'archived-item':'' ?>" <?= \app\helpers\HtmlHelper::ArchivedDisplay($comp,$show_archived) ?>>
					<?= $this->render('/comps/card',[
					'model'=>$comp,
					'static_view'=>$static_view,
					'no_arm'=>true,
					'no_abbr'=>true,
					'ips_glue'=>' \\\\ ',
				]) ?></div>
			<?php } } else { ?>
			отсутствуют
		<?php }?>
	</div>

	<div class="d-flex flex-row-reverse px-3 pb-1 m-0">
		<div class="tree-level-2 network-link-add" onmouseenter="$(this).children().toggle()"  onmouseleave="$(this).children().toggle()">
			<span class="fas fa-network-wired"></span>
			<?= Html::a('<span class="fas fa-plus-circle"></span>',
				[
					'/ports/create',
					'Ports[arms_id]'=>$model->id
				],[
					'class'=>'open-in-modal-form',
					'style'=>'display:none',
					'data-reload-page-on-submit'=>1,
					'qtip_ttip'=>'Добавить соединение порта<br>этого АРМ с другим устройством'
				]) ?>
		</div>
		<?php if (count($model->ports)) { ?>
			<div class="tree-level-2 text-uppercase port-links pe-5">
				<?php foreach ($model->ports as $port) {
					//echo '<span class="fas fa-solid fa-network-wired"></span>'.
					echo '<span class="fas fa-solid fa-square-full"></span> '.
						$this->render('/ports/item',['model'=>$port,'reverse'=>true,'modal'=>true,]).
						//' <span class="fas fa-solid fa-arrows-left-right"></span> '.
						' ❱❱❱ '.
						$this->render('/ports/item',[
							'model'=>$port->linkPort,
							'static_view'=>true,
							'include_tech'=>true,
							'reverse'=>true,
							
						]).' <br />';
				}?>
			</div>
		<?php }  ?>
	</div>

	<?php if (!$no_users) { ?>
		<h4>Сотрудники:</h4>
		Пользователь:<?= is_object($model->user)?$this->render('/users/item',['model'=>$model->user]):'-не назначен-' ?><br/>
		<?= is_object($model->head)?('Руководитель отдела:'.$this->render('/users/item',['model'=>$model->head]).'<br/>'):'' ?>
		<?= is_object($model->responsible)?('Ответственный:'.$this->render('/users/item',['model'=>$model->responsible]).'<br/>'):'' ?>
		<br />
	<?php } ?>

	
    <?php // $this->render('att-techs',['model'=>$model,'static_view'=>$static_view]) ?>
    
	<span class="divider2"></span>
	
	<?php if (strlen($licList=$this->render('/comps/lics_list',['model'=>$model,'static_view'=>$static_view]))) { ?>
		<div class="data-block tree-level-2">
			<?= $licList ?>
		</div>
	<?php } ?>
	
	
    <?= $this->render('arm-history',['model'=>$model,'static_view'=>$static_view]) ?>
	
	<?php if (is_object($model->itStaff)) { ?>
		<span class="it-staff">Сотрудник ИТ: <?= $this->render('/users/item',['model'=>$model->itStaff]) ?></span>
	<?php } ?>
	

</div>