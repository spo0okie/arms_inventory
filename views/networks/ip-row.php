<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $ip app\models\NetIps */
/* @var $i integer */

$addr=long2ip($model->addr+$i);
$default_comment='';
$class='';
if ($i==0){ //адрес сети
	$default_comment='Network address';
	$class='class="warning"';
} elseif ($i==$model->capacity-1) {
	$default_comment='Broadcast address';
	$class='class="warning"';
} elseif ($model->addr+$i==$model->router) {
	$default_comment='Default gateway';
	$class='class="success"';
}?>

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
		echo $ip->name;
		?>
	</td>
	<td <?= $class ?>>
		<?= Yii::$app->formatter->asNtext(strlen($ip->comment)?$ip->comment:$default_comment) ?>
	</td>
	
<?php } else { ?>
	<td <?= $class ?>><?= Html::a($addr,['net-ips/create','return'=>'previous','text_addr'=>$addr]) ?></td>
	<td <?= $class ?>></td>
	<td <?= $class ?>><?= Yii::$app->formatter->asNtext($default_comment) ?></td>
<?php }

