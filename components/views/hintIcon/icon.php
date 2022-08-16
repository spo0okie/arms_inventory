<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 05.04.2019
 * Time: 15:07
 * Рисует иконку помощи.
 */

/* @var $href */
/* @var $hintText */
/* @var $cssClass */

echo \yii\helpers\Html::a(
	'<span class="fas fa-question-circle"></span>',
	$href,
	[
		'title'=>$hintText,
		'class'=>[$cssClass]
	]
)
?>