<?php
/**
 * Элемент списка урлов
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:25
 */

/* @var $item string */

use yii\helpers\Html;
use yii\widgets\DetailView;

$tokens=explode(' ',$item);
if (count($tokens)>1) {
	//Есть описание
	$url=$tokens[count($tokens)-1];
	unset($tokens[count($tokens)-1]);
	$descr=implode(' ',$tokens);
} else {
	$url=$item;
	$descr=$item;
}
try {
	echo Html::a($descr,$url);
} catch (Exception $e) {
	echo $descr.' (неверный URL)';
} ?><br/>
