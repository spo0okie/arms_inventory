<?php
/*
 * Этот файл должен отрендерить IP адреса суффиксом к comps/item или techs/item или users/item
 */


/* @var $this yii\web\View */
/* @var $model app\models\Comps */


if (!isset($options)) $options=[];
if (!isset($glue)) $glue=', ';
if (!isset($prefix)) $prefix=': ';
$items=[];
foreach ($model->netIps as $ip) {
	$items[]=$ip->renderItem($this,$options);
}
if (count($items))
	echo $prefix.implode($glue,$items);