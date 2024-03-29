<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 05.04.2019
 * Time: 13:25
 */

namespace app\components;

use Codeception\Module\Yii2;
use yii\base\Widget;


class HintIconWidget extends Widget
{
	/*
	 * Иконка, которая при клике показывает/скрывает некий объект (<div> со всякими ништяками)
	 */
	public $model;
	public $hintText='Помощь по этой странице';
	public $action='index';
	public $cssClass='';

	public function run()
	{
		//по умолчанию домашняя
		$search='';
		
		$title='$title';
		$titles='$titles';
		$helptitle='$helptitle';
		$model=$this->model;
		
		if (property_exists($this->model,'title')) $search=$model::$title;
		if (property_exists($this->model,'titles')) $search=$model::$titles;
		if (property_exists($this->model,'helptitle')) $search=$model::$helptitle;
		
		if (isset(\Yii::$app->params['hintUrl'])) $url=\Yii::$app->params['hintUrl'];
		elseif (isset(\Yii::$app->params['wikiUrl'])) $url=\Yii::$app->params['wikiUrl'];
		else $url='https://github.com/spo0okie/arms_inventory/wiki';
		
		if (strlen($search)) $url.=$search;
		return $this->render('hintIcon/icon', [
			'hintText' => $this->hintText,
			'href' => $url,
			'cssClass' => $this->cssClass,
		]);
	}
}