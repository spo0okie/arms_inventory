<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

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
$responsible=$model->responsible;

if (is_object($model->domain))
	$domain=$model->domain->name;
else
	$domain='- ошибочный домен -';

if (!mb_strlen($domain))
	$domain='- не в домене -';

?>

<span class="unit-status <?= $model->updatedRenderClass ?> href" onclick="$('#comp<?= $model->id ?>-updated-info').toggle()"><?= $model->updatedText ?></span>
<br />
<h1>
	<span class="small"><?= $domain ?>\</span><?= $static_view?Html::a($model->renderName(),['comps/view','id'=>$model->id]):$model->name ?>

	<?= Html::a("<i class=\"fas fa-sign-in-alt\" title='Удаленное управление {$model->fqdn}' ></i>",'remotecontrol://'.$model->fqdn) ?>

	<?= $static_view?'':(Html::a('<i class="fas fa-pencil-alt" title="Изменить"></i>',['comps/update','id'=>$model->id])) ?>

	<?php if(!$static_view) if($deleteable) echo Html::a('<i class="fas fa-trash" title="Удалить"></i>', ['comps/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить эту ОС? Это действие необратимо!',
			'method' => 'post',
		],
	]); else { ?>
		<span class="small">
			<span class="fas fa-lock" title="Невозможно в данный момент удалить эту операционную систему, т.к. присутствуют привязанные сервисы."></span>
		</span>
	<?php } ?>
	&nbsp;
</h1>

<div>
	<?= $model->os ?><br />
	<span id="comp<?= $model->id ?>-updated-info" class="update-timestamp" style="display: none">Последнее обновление данных <?= $model->updated_at ?> (v. <?= $model->raw_version ?>)</span>
</div>
<div>
	<?= is_object($responsible)?'<strong>Ответственный:</strong>'.$this->render('/users/item',['model'=>$responsible,'static_view'=>$static_view]).'<br />':'' ?>
	<?= $model->comment ?>
</div>
<br />
<?php if(!$no_arm) { ?>
	<h4>АРМ</h4>
	<p>
		<?php if (is_object($model->arm)) { ?>
			<?= $this->render('/arms/item',['model'=>$model->arm,'static_view'=>$static_view]) ?>
		<?php } else { ?>
			не назначен
		<?php } ?>
	</p>
<?php } ?>

<div>
	<?= $this->render('ips_list',['model'=>$model,'static_view'=>$static_view,'glue'=>$ips_glue]) ?>
	<?= $this->render('lics_list',['model'=>$model,'static_view'=>$static_view]) ?>
</div>

<?php if (count($services)) {
	
	$output=[];
	foreach ($services as $service)
		$output[]=$this->render('/services/item',['model'=>$service,'static_view'=>$static_view]);
	echo "<h3>Размещенные сервисы</h3><p>".implode('<br />',$output)."</p>";
 } ?>
	
	
	
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