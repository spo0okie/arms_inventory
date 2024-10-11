<?php
/**
 * Элемент пользователей
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var Users $model */

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\Users;

if (!isset($icon)) $icon=false;
if (!isset($static_view)) $static_view=true;
if (!isset($noDelete)) $noDelete=false;
if (!isset($show_phone)) $show_phone=false;
if (!isset($show_ips))	$show_ips=false;

if (is_object($model)) {
	if (!isset($name)) {
		if (isset($short))
			$name=$model->shortName;
		else
			$name=$model->Ename;
	}
	
	if ($show_phone && strlen($model->Phone)) {
		$name.=' ('.$model->Phone.')';
	}
	
	if ($icon) $name='<span class="fas fa-user small"></span>'.$name;
	
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'uvolen',
		'archivedProperty'=>'Uvolen',
		'link'=> LinkObjectWidget::widget(['model'=>$model,'name'=>$name,'static'=>$static_view,'noDelete'=>$noDelete])
	]);
	
	if ($show_ips) {
		echo ItemObjectWidget::widget([
			'link'=>$this->render('/net-ips/model-ips',[
				'model'=>$model,
				'options'=>	isset($ips_options)?$ips_options:[],
				'glue'=>	isset($ips_glue)?$ips_glue:', ',
				'prefix'=>	isset($ips_prefix)?$ips_prefix:': ',
			]),
			'archived'=>false,//$model->archived,
		]);
	}
} else echo "Отсутствует";