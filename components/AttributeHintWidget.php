<?php
namespace app\components;

use app\helpers\ArrayHelper;
use app\helpers\FieldsHelper;
use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;

class AttributeHintWidget extends Widget
{
	


	/**
	 * @var ArmsModel
	 */
	public $model;
	
	public $attribute='name';
	
	public $label;			//выводимое имя аттрибута

	public $hint;			//подсказка
	
	public $mode='grid';	//режим отображения (grid|search|form|view)
	
	
	public function run()
	{
		//если у нас есть хинт, то возвращаем его с тултипом
		if (!empty($this->hint)) {
			return Html::tag(
				'span',
				$this->label,
				FieldsHelper::toolTipOptions($this->label,$this->hint)
			);
		}
		
		return $this->label;
	}
	
	
	public function prepareLabel():string {
		//если метка уже есть, то и хорошо
		if (!empty($this->label)) return $this->label;
		
		//пытаемся получить метку из модели
		if (is_object($this->model)) {
			switch ($this->mode) {
				case 'search':
				case 'grid':
					return $this->firstMethod(
						['getAttributeIndexLabel','getAttributeLabel'],
						Inflector::camel2words($this->attribute, true)
					);
				
				case 'form':
					return $this->firstMethod(
						['getAttributeLabel'],
						Inflector::camel2words($this->attribute, true)
					);
				case 'view':
					return $this->firstMethod(
						['getAttributeViewLabel','getAttributeLabel'],
						Inflector::camel2words($this->attribute, true)
					);
			}
		}

		//если не получилось, то просто из имени аттрибута
		return Inflector::camel2words($this->attribute, true);
	}
	
	public function prepareHint():string {
		//если подсказка уже есть, то и хорошо
		if (!empty($this->hint)) return $this->hint;
		
		//пытаемся получить метку из модели
		if (is_object($this->model)) {
			switch ($this->mode) {
				case 'search':
					$hint=(string)$this->firstMethod(
						['getAttributeIndexHint','getAttributeHint'],''
					);
					$searchHint=(string)$this->firstMethod(
						['getAttributeSearchHint'],''
					);
					return ArrayHelper::implode('<hr/>',[$hint,$searchHint]);
					
				case 'grid':
					return (string)$this->firstMethod(
						['getAttributeIndexHint','getAttributeHint'],
						Inflector::camel2words($this->attribute, true)
					);
				
				case 'form':
					return (string)$this->firstMethod(
						['getAttributeHint'],
						Inflector::camel2words($this->attribute, true)
					);
					
				case 'view':
					return (string)$this->firstMethod(
						['getAttributeViewHint','getAttributeHint'],
						Inflector::camel2words($this->attribute, true)
					);
			}
		}
		return '';
	}

	private function firstMethod($methods,$default)
	{
		foreach ($methods as $method) {
			if (method_exists($this->model, $method)) {
				return $this->model->$method($this->attribute);
			}
		}
		return $default;
	}
	
	
	public function init() {
		
		$this->label=$this->prepareLabel();
		$this->hint=$this->prepareHint();

	}
	
}