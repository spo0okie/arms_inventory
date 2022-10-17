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
	public $modal=false;

	/**
	 * @var $model ArmsModel
	 */
	public $model=null;
	
	public $options=[];
	
	public function init() {
		if (is_null($this->updateHint))
			$this->updateHint='Редактировать';
		
		$this->options['qtip_ttip']=$this->updateHint;
		$this->options['qtip_side']='bottom,top,right,left';
		
		
		if ($this->modal) {
			if (!isset($this->options['class']))
				$this->options['class']='';
			$this->options['class'].=' open-in-modal-form';
			$this->options['data-reload-page-on-submit']=1;
		}
	
	}
	
	public function run()
	{
		return Html::a('<span class="fas fa-pencil-alt update-item-button">', [
			'/'.Inflector::camel2id(
				StringHelper::basename(
					get_class($this->model)
				)
			).'/update',
			'id' => $this->model->id,
		],$this->options);
	}
}