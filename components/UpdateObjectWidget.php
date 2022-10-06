<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class UpdateObjectWidget extends Widget
{
	

	/**
	 * Сообщение по умолчанию, если объекты не переданы
	 * @var string
     */
	public $updateHint=null;

	/**
	 * @var $model ArmsModel
	 */
	public $model=null;
	
	public function init() {
		if (is_null($this->updateHint))
			$this->updateHint='Редактировать';
	}
	
	public function run()
	{
		return Html::a('<span class="fas fa-pencil-alt">', [
			'/'.Inflector::camel2id(
				StringHelper::basename(
					get_class($this->model)
				)
			).'/update',
			'id' => $this->model->id,
		],[
			'qtip_ttip'=>$this->updateHint,
			'qtip_side'=>'bottom,top,right,left'
		]);
	}
}