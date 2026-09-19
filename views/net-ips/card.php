<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1 class="text-monospace">
	<?= Html::encode($model->sname) ?>
	<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['net-ips/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['net-ips/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>

<?php
echo empty($model->name)?'':'<h4>'.\app\components\ModelFieldWidget::renderFieldValue($model,'name').'</h4>';
echo empty($model->comment)?'':\app\components\ModelFieldWidget::renderFieldValue($model,'comment');

$objects=[];

if (is_array($model->comps) && count ($model->comps)) {
	foreach ($model->comps as $comp) $objects[]=ModelWidget::widget(['model'=>$comp]);
}

if (is_array($model->techs) && count ($model->techs)) {
	$techs=[];
	foreach ($model->techs as $tech) $objects[]=ModelWidget::widget(['model'=>$tech]);
}

if (is_array($model->users) && count ($model->users)) {
	$users=[];
	foreach ($model->users as $user) $objects[]=ModelWidget::widget(['model'=>$user]);
}

if (count($objects)) echo '<h4>'
	.\app\components\ModelFieldWidget::renderCompositeTitle($model,['comps','techs','users'],'Привязан к','span')
	.': '.implode(', ',$objects).'</h4><br />';

//DNS-имена, указывающие на адрес (помимо hostname узлов выше); кнопка — одна форма
//«выбери заведённое или введи новое»: привязывает либо создаёт с привязкой
$names=[];
if (is_array($model->dnsNames)) foreach ($model->dnsNames as $dnsName)
	$names[]=ModelWidget::widget(['model'=>$dnsName,'options'=>['static_view'=>true]]);
$dnsButton=$static_view?'':Html::a('<i class="fas fa-plus"></i> DNS-имя',
	['/dns-names/attach-ip','ips_id'=>$model->id],
	['class'=>'badge text-bg-success open-in-modal-form','data-reload-page-on-submit'=>1,
		'title'=>'Привязать заведённое DNS-имя или создать новое с этим адресом']);
if (count($names) || $dnsButton) {
	echo '<h4>'.\app\components\ModelFieldWidget::renderFieldTitle($model,'dnsNames',null,'span')
		.': '.implode(', ',$names).' '.$dnsButton.'</h4><br />';
}

?>

<?php /* пробросы: куда ведёт этот адрес как адрес входа и как он сам доступен снаружи */ ?>
<?= $this->render('/aces/forwards',[
	'models'=>$model->forwardsOut,'owner'=>$model,'attribute'=>'forwardsOut','static_view'=>$static_view,
]) ?>
<?= $this->render('/aces/forwards',[
	'models'=>$model->forwardsIn,'owner'=>$model,'attribute'=>'forwardsIn','static_view'=>$static_view,
]) ?>

<?php //на странице адреса доступы — вкладками, сеть — справа в шапке (view.php);
//здесь (тултип) — списками и компактной сводкой сети
if (!($header??false)) { ?>
	<?= $this->render('/acls/list',['models'=>$model->acls,'static_view'=>$static_view]) ?>
	<?= $this->render('/aces/list',['models'=>$model->aces,'static_view'=>$static_view]) ?>
	<?php if (is_object($model->network)) { ?>
		<hr>
		<?= $this->render('network',['model'=>$model]) ?>
	<?php } ?>
<?php }
