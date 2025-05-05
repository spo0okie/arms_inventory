<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

use app\assets\DokuWikiAsset;
use app\components\Forms\ActiveField;
use app\helpers\StringHelper;
use app\helpers\WikiHelper;
use kartik\markdown\Markdown;
use Yii;
use yii\base\Widget;

class TextFieldWidget extends Widget
{
	public $model;
	public $field;
	
	public function run()
	{
		switch (ActiveField::textFieldType(get_class($this->model),$this->field)) {
			case 'markdown':
				return Markdown::convert($this->model->{$this->field});
			case 'dokuwiki':
				return WikiTextWidget::widget(['model'=>$this->model,'field'=>$this->field]);
			default:
				return Yii::$app->formatter->asNtext($this->model->{$this->field});
		}
	}
}