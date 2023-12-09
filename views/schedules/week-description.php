<?php
/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

use app\helpers\DateTimeHelper;

?>

<h3>
	<?= $model->getWorkTimeDescription() ?>
	<small qtip_ttip="На неделе с <?=
	Yii::$app->formatter->asDate(DateTimeHelper::weekMonday())
	. ' по '
	. Yii::$app->formatter->asDate(DateTimeHelper::weekSunday())
	?><br>В другие недели могут быть изменения">
		<i class="fa fa-info-circle" ></i>
	</small>
</h3>