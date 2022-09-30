<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;

class UpdateObjectWidget extends Widget
{
	

	/**
	 * Сообщение по умолчанию, если объекты не переданы
	 * @var string
     */
	public $title='Редактировать';

	/**
	 * @var $model ArmsModel
	 */
	public $model=null;
	
	
	public function run()
	{
		return Html::a('<span class="fas fa-pencil-alt">', [
			'update',
			'id' => $this->model->id,
		],[
			'title'=>$this->title,
		]);
	}
}