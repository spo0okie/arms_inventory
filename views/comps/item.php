<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

if (!isset($static_view)) $static_view=true;
if (!isset($fqdn))	$fqdn=false;
if (!isset($icon))	$icon=false;
if (!isset($rc)) 	$rc=false;
if (!isset($show_ips))	$show_ips=false;

if (is_object($model)) {
	if (!isset($name)) $name=$model->renderName($fqdn);
	if ($icon) {
		if ($model->isWindows) $name='<span class="fab fa-windows"></span>'.$name;
		elseif ($model->isLinux) $name='<span class="fab fa-linux"></span>'.$name;
		//else $name='<span class="far fa-meh-blank"></span>'.$name;
	}
	
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'modal'=>true,
			'noDelete'=>true,
			'static'=>$static_view,
			'name'=>$name,
			'nameSuffix'=>$rc?Html::a("<i class=\"fas fa-sign-in-alt\" title='Удаленное управление {$model->fqdn}' ></i>",'remotecontrol://'.$model->fqdn):'',
			'noSpaces'=>true
		]),
	]);
	
	if ($show_ips) {
		if (!isset($ips_options)) $ips_options=[];
		if (!isset($ips_glue)) $ips_glue=', ';
		if (!isset($ips_prefix)) $ips_prefix=': ';
		$items=[];
		foreach ($model->netIps as $ip) {
			$items[]=$ip->renderItem($this,$ips_options);
		}
		echo $ips_prefix.implode($ips_glue,$items);
	}
} else echo "Отсутствует";