<?php
/**
 * Элемент оборудования
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var Techs $model */
/* @var string $name */

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\Techs;
if (!isset($static_view)) $static_view=false;
if (!isset($show_ips))	$show_ips=false;

if (!empty($model)) {
	
    if (!isset($name)) $name=$model->name;

	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$name,
			'static'=>$static_view,
			'noDelete'=>true,
			'noSpaces'=>true,
		])
	]);
	
	if ($show_ips) {
		echo ItemObjectWidget::widget([
			'link'=>$this->render('/net-ips/model-ips',[
				'model'=>$model,
				'options'=>	isset($ips_options)?$ips_options:[],
				'glue'=>	isset($ips_glue)?$ips_glue:', ',
				'prefix'=>	isset($ips_prefix)?$ips_prefix:': ',
			]),
			'archived'=>$model->archived,
		]);
	}
}