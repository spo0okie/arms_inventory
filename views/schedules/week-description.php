<?php
/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

use app\helpers\DateTimeHelper;

//разбиваем описание на секции по дням недели, чтобы описание разбивалось по секциям
$description=$model->getWorkTimeDescription();
$tokens=explode(', ',$description);
foreach ($tokens as $i=>$token) {
	$tokens[$i]='<span class="text-nowrap">'.$token.'</span>';
}

?>

<span class="word-wrap">
	<?= implode(', ',$tokens) ?>
	<?php if (count($model->overrides)) { ?>
		<small qtip_ttip="На неделе с <?=
			Yii::$app->formatter->asDate(DateTimeHelper::weekMonday())
			. ' по '
			. Yii::$app->formatter->asDate(DateTimeHelper::weekSunday())
		?><br>В другие недели могут быть изменения<br>(Имеются периоды-исключения)">
			<i class="fa fa-info-circle" ></i>
		</small>
	<?php } ?>
</span>