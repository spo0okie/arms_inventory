<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */
if (!isset($static_view)) $static_view=false;
$services=$model->services;
$deleteable=!count($services);
$fqdn=mb_strtolower($model->fqdn);

if (is_object($model->domain))
	$domain=$model->domain->name;
else
	$domain='- ошибочный домен -';

if (!mb_strlen($domain))
	$domain='- не в домене -';

?>
<h1>
	<abbr title="Операционная система">ОС</abbr>
	<span class="small">
		<?= $domain ?>\
	</span>
	<?= $static_view?Html::a($model->name,['comps/view','id'=>$model->id]):$model->name ?>

	<?= Html::a("<span class=\"glyphicon glyphicon-log-in\" title='Удаленное управление {$model->fqdn}' />",'remotecontrol://'.$model->fqdn) ?>

	<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil" title="Изменить"></span>',['comps/update','id'=>$model->id])) ?>

	<?php if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash" title="Удалить"/>', ['comps/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить эту ОС? Это действие необратимо!',
			'method' => 'post',
		],
	]); else { ?>
		<span class="small">
			<span class="glyphicon glyphicon-lock" title="Невозможно в данный момент удалить эту операционную систему, т.к. присутствуют привязанные сервисы."></span>
		</span>
	<?php } ?>
	&nbsp;
</h1>

<p>
	<?= $model->os ?><br />
	<span class="update-timestamp">Последнее обновление данных <?= $model->updated_at ?></span>
</p>

<h4>АРМ</h4>
<p>
	<?php if (is_object($model->arm)) { ?>
		<?= $this->render('/arms/item',['model'=>$model->arm,'static_view'=>$static_view]) ?>
	<?php } else { ?>
		не назначен
	<?php } ?>
</p>

<?= $this->render('ips_list',['model'=>$model,'static_view'=>$static_view]) ?><br />

<?php if (count($services)) {
	
	$output=[];
	foreach ($services as $service)
		$output[]=$this->render('/services/item',['model'=>$service,'static_view'=>$static_view]);
	echo "<h3>Размещенные сервисы</h3><p>".implode('<br />',$output)."</p>";
 } ?>

<h4>Журнал входов (3 посл)</h4>
<div class="login_journal">
	<?php
	$logons=$model->lastThreeLogins;
	if (is_array($logons) && count($logons)) {
		$items=[];
		foreach ($logons as $logon) {
			echo $this->render('/login-journal/item-user',['model'=>$logon]).'<br />';
		}
	}?>
</div>
