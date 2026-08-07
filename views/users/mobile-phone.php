<?php
/**
 * Рендер телефонного атрибута (значений может быть несколько через
 * запятую). Рядом с каждым номером — инлайн-действия интеграций
 * (AttributeActionsWidget, например отправка SMS), если передана модель
 * и рендер не статический.
 */

/** @var $phone string */
/** @var $static_view boolean */
/** @var $model \app\models\Users|null модель для действий интеграций */
/** @var $attribute string|null имя атрибута модели */

use app\components\integrations\AttributeActionsWidget;
use app\helpers\ArrayHelper;

if (!isset($static_view)) $static_view=true;
if (!isset($model)) $model=null;
if (!isset($attribute)) $attribute=null;

$phones= $phone?ArrayHelper::explode(',',$phone):[];

$rendered=[];
foreach ($phones as $phone) {
	$render=$phone;
	if (!$static_view && is_object($model) && $attribute) {
		$render.=AttributeActionsWidget::widget([
			'model'=>$model,
			'attribute'=>$attribute,
			'value'=>$phone,
		]);
	}
	$rendered[]=$render;
}
echo implode(', ',$rendered);
