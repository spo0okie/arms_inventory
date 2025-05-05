<?php
namespace app\components\formInputs;

use app\components\yii;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\Html;

class TextAutoResizeWidget extends Widget
{
	

	/**
	 * Количество линий для поля
	 * @var integer
     */
	public $lines=1;
	public $hint=null;
	
	
	/**
	 * Форма в которой создаем поле
	 * @var \yii\widgets\ActiveForm
	 */
	public $form;
	
	/**
	 * модель, для которой собираем форму
	 * @var  Model
	 */
	public $model;
	
	/**
	 * поле которое рендерим
	 * @var  string
	 */
	public $attribute;
	
	public function run()
	{
		$inputId = $this->options['id'] ?? Html::getInputId($this->model, $this->attribute);
		
		return $this->render('TextAutoResize', [
			'form'	=> $this->form,
			'model' => $this->model,
			'field' => $this->attribute,
			'lines' => $this->lines,
			'hint'	=> $this->hint,
		]);
	}
}