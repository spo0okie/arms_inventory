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
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'item_class'=>'item_status '.(strlen($model->name)?$model->code:''),
		//статус архивного состояния должен оставаться видимым в карточках (помечается, но не скрывается)
		'show_archived'=>true,
		'static'=>$static_view??true,
	]);
}
