<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $ip app\models\NetIps */
/* @var $i integer */

$addr=long2ip($model->addr+$i);

if ($i==0){ //адрес сети  ?>
	<td class="warning">
		<?= $i ?> (net)
	</td>
	
<?php } elseif ($i==$model->capacity-1) { ?>
	<td class="warning">
		<?= $i ?> (bcast)
	</td>
<?php } elseif ($model->addr+$i==$model->router) { ?>
	<td class="success">
		<?= $i ?> (router)
	</td>
<?php } else { ?>
	<td>
		<?= $i ?>
	</td>
<?php } ?>


<?php
//if (!isset($ip)||empty($ip)) $ip=$model->fetchIp($i);
if (is_object($ip)) {
	?>
	<td>
		<?= $this->render('/net-ips/item',['model'=>$ip]) ?>
	</td>
	<td>
		<?php
		if (is_array($ip->comps)) foreach ($ip->comps as $comp)
			echo $this->render('/comps/item',['model'=>$comp]);
		if (is_array($ip->techs)) foreach ($ip->techs as $tech)
			echo $this->render('/techs/item',['model'=>$tech]);
		echo $ip->name;
		?>
	</td>
	<td>
		<?= Yii::$app->formatter->asNtext($ip->comment) ?>
	</td>
	
<?php } else { ?>
	<td><?= Html::a($addr,['net-ips/create','return'=>'previous','text_addr'=>$addr]) ?></td>
	<td></td>
	<td></td>
<?php }

