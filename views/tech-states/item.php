<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 03.10.2019
 * Time: 21:55
 */

use app\components\ItemObjectWidget;

/* @var \app\models\TechStates $model */

if (is_object($model)) {
	//маркер из общего кэша справочника (item рендерится на каждую строку списков техники)
	$marker=$model->marker_id?\app\models\Markers::getLoadedItem($model->marker_id,true):null;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		//легаси CSS-класс по коду — fallback пока состоянию не назначен маркер
		'item_class'=>'item_status '.($marker?'':(strlen($model->name)?$model->code:'')),
		//статус архивного состояния должен оставаться видимым в карточках (помечается, но не скрывается)
		'show_archived'=>true,
		'static'=>$static_view??true,
	]);
}
