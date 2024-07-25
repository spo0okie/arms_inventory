<?php

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */


use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

if (!isset($class)) $class='';
if (!isset($static_view)) $static_view=false;
if (!isset($icon)) $icon=false;
if (!isset($no_class)) $no_class=false;
if (!isset($rendered_comment)) $rendered_comment='';
//если IP рисуется рядом с компом, то нам не надо в комментарии IP еще раз показывать имя компа
//(в случае если комментарий повторяет его)
//поэтому мы можем передать что мы уже отрисовали относительно IP чтобы не повторяться

if (!empty($model)) {
	if (!$no_class&&is_object($model->network)) $class.=' '.$model->network->segmentCode;
	if (!isset($name)) $name=$model->getSname($rendered_comment); //убираем из имени IP то что уже отрендерено
	if ($icon) $name='<span class="fas fa-network-wired small"></span>'.$name;
	
	
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'item_class'=>$class,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'noDelete'=>true,
			'static'=>$static_view,
			'name'=>$name,
			'noSpaces'=>true
		]),
	]);
}
