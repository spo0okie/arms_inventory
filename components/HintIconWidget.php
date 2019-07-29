<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 05.04.2019
 * Time: 13:25
 */

namespace app\components;

use yii\base\Widget;


class HintIconWidget extends Widget
{
	/*
	 * Иконка, которая при клике показывает/скрывает некий объект (<div> со всякими ништяками)
	 */
	public $object_id;
	public $hintText='Показать/скрыть подсказку';
	public $cssClass='';

	public function run()
	{
		return $this->render('hintIcon/icon', [
			'hintText' => $this->hintText,
			'object_id' => $this->object_id,
			'cssClass' => $this->cssClass,
		]);
	}
}