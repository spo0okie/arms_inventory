<?php
namespace app\components\formInputs;

use app\components\yii;
use app\models\ArmsModel;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\Html;
use yii\widgets\InputWidget;

class TextAutoResizeWidget extends InputWidget
{
	
	public $rows=4;
	
	public function run()
	{
		
		$rows=$this->options['rows']??$this->rows;
		
		$inputId=$this->options['id']??Html::getInputId($this->model, $this->attribute);
		
		//количество строк используем в авторесайзе как минимальное, меньше которого не ресайзить
		$this->getView()->registerJs("jQuery('#$inputId')"
			.".autoResize({extraSpace:14,minLines:$rows})"
			.".trigger('change.dynSiz');"
		);
		
		//пересчитываем количество строк для первоначального отображения textarea в нужную высоту
		//(после первоначального авторесайза этот атрибут вообще будет убран, он далее используется в minLines)
		$this->options['rows'] = max($rows, count(explode("\n", $this->model->{$this->attribute})));
		
		//подгружаем плейсхолдер
		$placeholder = '';
		if ($this->model->hasMethod('getAttributePlaceholder')) {
			$placeholder=$this->model->getAttributePlaceholder($this->attribute);
		}
		
		return Html::activeTextarea(
			$this->model,
			$this->attribute,
			(array_merge([
				'placeholder'=>$placeholder,
			],$this->options))
		);
	}
}