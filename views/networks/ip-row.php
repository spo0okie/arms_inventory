<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $i integer */

?>

<td>
	<?= $i ?>
</td>

<?php if (is_object($ip=$model->fetchIp($i))) { ?>
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
	<td colspan="3"></td>
<?php }

