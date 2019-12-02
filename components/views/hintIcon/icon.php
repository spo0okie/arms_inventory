<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 05.04.2019
 * Time: 15:07
 * Рисует иконку помощи.
 */

echo \yii\helpers\Html::a(
	'',
	$href,
	[
		'title'=>$hintText,
		'class'=>['glyphicon', 'glyphicon-question-sign', $cssClass]
	]
)
?>