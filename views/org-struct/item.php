<?php

use app\components\ItemObjectWidget;
use app\components\widgets\page\ModelWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

if (!isset($static_view)) $static_view=true;
if (!isset($items_glue)) $items_glue=' &rarr; ';


if (!empty($model)) {
	if (isset($chain) && !empty($chain)) {
		$tokens=[];
		$item=$model;
		do {
			$tokens[]=ModelWidget::widget(['model'=>$item,'options'=>['static_view'=>true]]);
			$item=$item->parent;
		} while (is_object($item));
		echo implode($items_glue,array_reverse($tokens));
	} else {
		//все URL переопределяем, т.к. страницам org-struct кроме id нужен org_id
		echo ItemObjectWidget::widget([
			'model'=>$model,
			'name'=>$name??null,
			'url'=>['/org-struct/view','id'=>$model->id,'org_id'=>$model->org_id],
			'ttipUrl'=>Url::to(['/org-struct/ttip','id'=>$model->id,'org_id'=>$model->org_id]),
			'updateUrl'=>Url::to(['/org-struct/update','id'=>$model->id,'org_id'=>$model->org_id]),
			'noDelete'=>true,
			'static'=>$static_view,
		]);
	}
}
