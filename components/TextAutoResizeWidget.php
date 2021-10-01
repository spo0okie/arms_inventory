<?php
namespace app\components;

use yii\base\Widget;

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
	 * @var yii\widgets\ActiveForm
	 */
	public $form;
	
	/**
	 * модель для которой собираем форму
	 * @var  yii\db\ActiveRecord
	 */
	public $model;
	
	/**
	 * поле которое рендерим
	 * @var  string
	 */
	public $attribute;
	
	public function run()
	{
		
		return $this->render('TextAutoResize', [
			'form'	=> $this->form,
			'model' => $this->model,
			'field' => $this->attribute,
			'lines' => $this->lines,
			'hint'	=> $this->hint,
		]);
	}
}