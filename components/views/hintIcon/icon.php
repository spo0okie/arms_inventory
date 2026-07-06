<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 05.04.2019
 * Time: 15:07
 * Рисует иконку помощи.
 */

/* @var $href string */
/* @var $hintText string */
/* @var $cssClass string */
/* @var $tooltipOptions array qtip-атрибуты тултипа с описанием сущности (может быть пустым) */

echo \yii\helpers\Html::a(
	'<span class="fas fa-question-circle"></span>',
	$href,
	array_merge(
		[
			'class'=>[$cssClass],
		],
		//если qtip-тултип есть - обычный title не вешаем, чтобы не дублировались
		count($tooltipOptions)?$tooltipOptions:['title'=>$hintText]
	)
)
?>
