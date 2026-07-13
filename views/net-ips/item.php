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
	//IP раскрашивается маркером сегмента своей сети (легаси CSS-класс по коду — fallback);
	//сеть/сегмент - из общих кэшей справочников: item рендерится на каждый IP в списках,
	//цепочка relations означала бы отдельные запросы на каждый
	$network=$model->isRelationPopulated('network')?
		$model->network:
		($model->networks_id?\app\models\Networks::getLoadedItem($model->networks_id,true):null);
	$segment=is_object($network)?(
		$network->isRelationPopulated('segment')?
			$network->segment:
			($network->segments_id?\app\models\Segments::getLoadedItem($network->segments_id,true):null)
	):null;
	$marker=(!$no_class&&is_object($segment))?($segment->marker??false):false;
	if (!$no_class&&is_object($segment)&&!$marker) $class.=' '.$segment->code;
	if (!isset($name)) $name=$model->getSname($rendered_comment); //убираем из имени IP то что уже отрендерено
	if ($icon) $name='<span class="fas fa-network-wired small"></span>'.$name;


	echo ItemObjectWidget::widget([
		'model'=>$model,
		'marker'=>$marker,
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
