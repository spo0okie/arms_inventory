<?php
namespace app\components;

use yii\base\Widget;
use yii\helpers\Inflector;

/**
 * Label атрибута с тултипом для grid/search/view/form контекстов.
 * Содержимое тултипа собирает единый сборщик AttributeTooltip
 * (спецификация: ui-sources.md §0.1) — виджет только рендерит.
 */
class AttributeHintWidget extends Widget
{


	/**
	 * @var \app\models\base\ArmsModel
	 */
	public $model;

	public $attribute='name';

	public $label;			//выводимое имя аттрибута

	public $hint;			//подсказка (явное переопределение смыслового блока)

	public $mode='grid';	//режим отображения (grid|search|form|view)


	public function run()
	{
		//если тултип есть - подача единая: иконка «?» рядом с чистым label,
		//тултип и pin-поведение висят на иконке (AttributeTooltip::icon)
		if (!empty($this->hint)) {
			return $this->label.' '
				.AttributeTooltip::icon(['title'=>$this->label,'body'=>$this->hint]);
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
		//сборка тела тултипа по режиму (null - тултип не нужен)
		$tooltip=AttributeTooltip::build(
			$this->model,
			$this->attribute,
			$this->mode,
			$this->label,
			$this->hint?:null
		);
		$this->hint=$tooltip['body']??'';

	}

}
