<?php

//Эта штука должна переопределить поведение базового хелпера УРЛ,
//задача в том что при CORS запросах мы должны отдавать абсолютный урл
//т.к. относительный будет относительно origin что ломает путь
//схема обхода уперта тут: https://stackoverflow.com/questions/48299856/yii2-make-absolutes-url-default-on-application

namespace yii\helpers;

class Url extends \yii\helpers\BaseUrl {
	
	// Set the $scheme to be true by default.
	public static function to($url = '', $scheme = false) {
		return parent::to($url, $scheme || (!empty(\Yii::$app->request->origin)));
	}
	
	// Set the $scheme to be true by default.
	public static function toRoute($route, $scheme = false) {
		return parent::toRoute($route, $scheme || (!empty(\Yii::$app->request->origin)));
	}
}
