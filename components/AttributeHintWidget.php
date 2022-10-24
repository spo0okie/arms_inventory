<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;

class AttributeHintWidget extends Widget
{
	


	/**
	 * @var ArmsModel
	 */
	public $model=null;
	
	public $attribute='name';
	
	public $label=null;			//выводимое имя аттрибута

	public $hint=null;			//подсказка
	
	public $index=true;			//метка не для формы, а для вывода в шапке таблицы
	
	public function run()
	{
		
		if (!is_null($this->hint) && strlen($this->hint)) {
			$fieldFullName=
				is_object($this->model)
					?
					$this->model->getAttributeLabel($this->attribute)
					:
					$this->label;
			
			return \yii\helpers\Html::tag(
				'span',
				$this->label,
				\app\helpers\FieldsHelper::toolTipOptions($fieldFullName,$this->hint)
			);
		} else return $this->label;
	}
	
	
	public function init() {
		
		$model=$this->model;
		
		if (
			is_null($this->label)
		) {
			if (is_object($model)) {
				$this->label=($this->index&&method_exists($model,'getAttributeIndexLabel'))
				?
				$model->getAttributeIndexLabel($this->attribute)
				:
				$model->getAttributeLabel($this->attribute);
			} else {
				$this->label=Inflector::camel2words($this->attribute, true);
			}
		}
		
		$this->label=Html::encode($this->label);
		
		$hintMethod=$this->index?'getAttributeIndexHint':'getAttributeHint';
		
		if (
			!is_null($this->label) &&
			is_object($model) &&
			method_exists($model,$hintMethod)
		) {
			$this->hint=$model->$hintMethod($this->attribute);
		}

	}
	
}