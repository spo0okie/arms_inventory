<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

use app\types\TextType;
use yii\base\Widget;

class TextFieldWidget extends Widget
{
	public $model;
	public $field;
	public $outerClass='';

	public function run()
	{
		//логика выбора рендера (ntext/markdown/dokuwiki) живёт в типе text
		return (new TextType())->renderOutput(
			$this->view,
			$this->model,
			$this->field,
			['outerClass'=>$this->outerClass]
		);
	}
}
