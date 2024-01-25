<?php
/** @var $phone string */
/** @var $static_view boolean */

use app\helpers\ArrayHelper;
use yii\helpers\Html;

if (!isset($static_view)) $static_view=true;

$phones= ArrayHelper::explode(',',$phone);

$rendered=[];
foreach ($phones as $phone) {
	$render=$phone;
	if (!$static_view && Yii::$app->params['sms.enable']) {
		$render.= ' '.Html::a('<i class="fas fa-comment-dots"></i>',['sms/send','SmsForm[phone]'=>$phone],['class'=>'open-in-modal-form']);
	}
	$rendered[]=$render;
}
echo implode(', ',$rendered);

