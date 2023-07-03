<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $ip app\models\NetIps */
/* @var $i integer */
/* @var $showEmpty boolean */


$addr=long2ip($model->addr+$i);
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
} elseif (is_object($model->firstUnusedIp) && $model->addr+$i==$model->firstUnusedIp->addr) {
	$default_comment='Первый свободный адрес';
	$class='class="table-success"';
}

$isEmpty=isset($model->ipsByAddr[$model->addr+$i])||$class;
$ip=$model->ipsByAddr[$model->addr+$i]??null;
?>

<tr class="<?= $isEmpty?'':'empty-item' ?>" <?= ($isEmpty||$showEmpty)?'':'style="display:none"' ?>>

<td <?= $class ?>>
	<?= $i ?>
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
			echo $this->render('/comps/item',['model'=>$comp]);
		if (is_array($ip->techs)) foreach ($ip->techs as $tech)
			echo $this->render('/techs/item',['model'=>$tech]);
		if (is_array($ip->users)) foreach ($ip->users as $user)
			echo $this->render('/users/item',['model'=>$user,'short'=>true]);
		echo $ip->name;
		?>
	</td>
	<td <?= $class ?>>
		<?= Yii::$app->formatter->asNtext(strlen($ip->comment)?$ip->comment:$default_comment) ?>
	</td>
	
<?php } else { ?>
	<td <?= $class ?>><span class="net-ips-item"><?= Html::a($addr,['net-ips/create','return'=>'previous','text_addr'=>$addr]) ?></span></td>
	<td <?= $class ?>></td>
	<td <?= $class ?>><?= Yii::$app->formatter->asNtext($default_comment) ?></td>
<?php } ?>

</tr>
