<?php

use yii\helpers\Html;

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

if ($i==0){ //адрес сети
	$default_comment='Network address';
	$class='class="table-warning"';
} elseif ($i==$model->capacity-1) {
	$default_comment='Broadcast address';
	$class='class="table-warning"';
} elseif ($model->addr+$i==$model->router) {
	$default_comment='Default gateway';
	$class='class="table-success"';
} elseif (is_object($model->firstUnusedIp) && $ip==$model->firstUnusedIp->addr) {
	$default_comment='Первый свободный адрес';
	$class='class="table-success"';
} elseif (array_search($ip,$dhcps)!==false) {
	$default_comment='DHCP server';
	$class='class="table-info"';
}
$rangeName='';
foreach ($model->rangesList as $range) {
	if ($i>=$range[0] && $i<=$range[1]) $rangeName=$range[2];
}

$isEmpty=isset($model->ipsByAddr[$model->addr+$i])||$class;
$ip=$model->ipsByAddr[$model->addr+$i]??null;
?>

<tr class="<?= $isEmpty?'':'empty-item' ?>" <?= ($isEmpty||$showEmpty)?'':'style="display:none"' ?>>

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
		<?= $this->render('/net-ips/item',['model'=>$ip]) ?>
	</td>
	<td <?= $class ?>>
		<?php
		if (is_array($ip->comps)) foreach ($ip->comps as $comp)
			echo $this->render('/comps/item',['model'=>$comp,'static_view'=>false]);
		if (is_array($ip->techs)) foreach ($ip->techs as $tech)
			echo $this->render('/techs/item',['model'=>$tech,'static_view'=>false]);
		if (is_array($ip->users)) foreach ($ip->users as $user)
			echo $this->render('/users/item',['model'=>$user,'short'=>true,'static_view'=>false]);
		echo $ip->name;
		?>
	</td>
	<td <?= $class ?>>
		<?= Yii::$app->formatter->asNtext(strlen($ip->comment)?$ip->comment:$default_comment) ?>
	</td>
	
<?php } else { ?>
	<td <?= $class ?>><span class="net-ips-item"><?= Html::a($addr,['net-ips/create','return'=>'previous','NetIps[text_addr]'=>$addr]) ?></span></td>
	<td <?= $class ?>></td>
	<td <?= $class ?>><?= Yii::$app->formatter->asNtext($default_comment) ?></td>
<?php } ?>

</tr>
