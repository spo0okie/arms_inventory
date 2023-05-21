<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

if (is_object($model)) {
//разбиваем строку телефонов запятыми
	$phones=\yii\helpers\StringHelper::explode($model->Phone,',',true,true);
//проверяем привязанное к пользователю оборудование
	foreach ($model->techs as $tech) {
		//если это телефон с указанным внутренним номером
		if ($tech->isVoipPhone && $tech->comment) {
			//рендерим его
			$phone=$this->render('/techs/item',[
				'model'=>$tech,
				'name'=>$tech->comment,
				'static_view'=>true
			]);
			//ищем его номер в списке телефонов пользователя
			$pos=array_search(trim($tech->comment),$phones);
			if ($pos===false) {
				$phones[]=$phone;
			} else {
				$phones[$pos]=$phone;
			}
		}
	}
	//var_dump($phones);
	echo implode(', ',$phones);
}