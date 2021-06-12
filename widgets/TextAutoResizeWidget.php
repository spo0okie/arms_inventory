<?php
namespace app\widgets;

use yii\base\Widget;
use yii\widgets\ActiveForm;


class TextAutoResizeWidget extends Widget
{
	

	/**
	 * Количество линий для поля
	 * @var integer
     */
	public $lines=1;
	
	
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
			'form' => $this->form,
			'model' => $this->model,
			'field' => $this->attribute,
			'lines' => $this->lines,
		]);
	}
}