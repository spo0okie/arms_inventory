<?php

use yii\helpers\Html;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $ip app\models\NetIps */
/* @var $i integer */
/* @var $showEmpty boolean */

$ip=$model->addr+$i;
$addr=long2ip($ip);
$dhcps=$model->dhcpList;
$default_comment='';
$class='';
$rowHint=''; //пояснение цветовой маркировки строки (тултип на строке)

if ($i==0){ //адрес сети
	$default_comment='Адрес сети';
	$class='class="table-warning"';
	$rowHint='Адрес сети — служебный адрес, узлам не назначается (жёлтая подсветка)';
} elseif ($i==$model->capacity-1) {
	$default_comment='Широковещательный адрес';
	$class='class="table-warning"';
	$rowHint='Широковещательный адрес — служебный адрес, узлам не назначается (жёлтая подсветка)';
} elseif ($model->addr+$i==$model->router) {
	$default_comment='Шлюз по умолчанию';
	$class='class="table-success"';
	$rowHint='Этот адрес указан шлюзом по умолчанию в этой сети (зелёная подсветка)';
} elseif (is_object($model->firstUnusedIp) && $ip==$model->firstUnusedIp->addr) {
	$default_comment='Первый свободный адрес';
	$class='class="table-success"';
	$rowHint='Первый не занятый адрес сети — его удобно выдать следующему узлу (зелёная подсветка)';
} elseif (array_search($ip,$dhcps)!==false) {
	$default_comment='DHCP сервер';
	$class='class="table-info"';
	$rowHint='Этот адрес указан DHCP сервером этой сети (синяя подсветка)';
}
$rangeName='';
foreach ($model->rangesList as $range) {
	if ($i>=$range[0] && $i<=$range[1]) $rangeName=$range[2];
}

$isEmpty=isset($model->ipsByAddr[$model->addr+$i])||$class;
$ip=$model->ipsByAddr[$model->addr+$i]??null;
?>

<tr class="<?= $isEmpty?'':'empty-item' ?>" <?= ($isEmpty||$showEmpty)?'':'style="display:none"' ?> <?= $rowHint?'qtip_ttip="'.Html::encode($rowHint).'"':'' ?>>

<td <?= $class ?>>
	<span class="net-ips-item">
		<?php
			for ($j=0; $j<(4-strlen($i)); $j++) echo '&nbsp;';
			echo $i;
		?>
	</span>
	<span class="text-muted"><?= $rangeName ?></span>
</td>

<?php
//if (!isset($ip)||empty($ip)) $ip=$model->fetchIp($i);
if (is_object($ip)) {
	?>
	<td <?= $class ?>>
		<?= ModelWidget::widget(['model'=>$ip]) ?>
	</td>
	<td <?= $class ?>>
		<?php
		if (is_array($ip->comps)) foreach ($ip->comps as $comp)
			echo ModelWidget::widget(['model'=>$comp,'options'=>['static_view'=>false]]);
		if (is_array($ip->techs)) foreach ($ip->techs as $tech)
			echo ModelWidget::widget(['model'=>$tech,'options'=>['static_view'=>false]]);
		if (is_array($ip->users)) foreach ($ip->users as $user)
			echo ModelWidget::widget(['model'=>$user,'options'=>['short'=>true,'static_view'=>false,'noDelete'=>true]]);
		echo $ip->name;
		?>
	</td>
	<td <?= $class ?>>
		<?= Yii::$app->formatter->asNtext(strlen($ip->comment??'')?$ip->comment:$default_comment) ?>
	</td>
	
<?php } else { ?>
	<td <?= $class ?>><span class="net-ips-item"><?= Html::a($addr,['net-ips/create','return'=>'previous','NetIps[text_addr]'=>$addr]) ?></span></td>
	<td <?= $class ?>></td>
	<td <?= $class ?>><?= Yii::$app->formatter->asNtext($default_comment) ?></td>
<?php } ?>

</tr>


