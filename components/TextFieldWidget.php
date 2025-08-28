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
use yii\helpers\Html;

class TextFieldWidget extends Widget
{
	public $model;
	public $field;
	public $outerClass='';
	
	public function run()
	{
		switch (ActiveField::textFieldType(get_class($this->model),$this->field)) {
			case 'markdown':
				return Markdown::convert($this->model->{$this->field});
			case 'dokuwiki':
				return WikiTextWidget::widget([
					'model'=>$this->model,
					'field'=>$this->field,
					'outerClass'=>$this->outerClass
				]);
			default:
				return $this->outerClass?
					Html::tag(
						'div',
						Yii::$app->formatter->asNtext($this->model->{$this->field}),
						['class'=>$this->outerClass]
					):
					Yii::$app->formatter->asNtext($this->model->{$this->field});
		}
	}
}