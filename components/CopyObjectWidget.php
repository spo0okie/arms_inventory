<?php
namespace app\components;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Иконка-ссылка «Создать копию» рядом с объектом (по образцу UpdateObjectWidget).
 * Ведёт на {controller}/copy?id={model->id} — форму создания, предзаполненную
 * атрибутами образца (см. ArmsBaseController::actionCopy).
 */
class CopyObjectWidget extends Widget
{

	/**
	 * Подсказка на иконке
	 * @var string
	 */
	public $copyHint=null;

	/**
	 * @var \app\models\base\ArmsModel $model
	 */
	public $model=null;
	public $url=null;
	public $options=[];

	public function init() {
		if (is_null($this->copyHint))
			$this->copyHint='Создать копию';

		$this->options['qtip_ttip']=$this->copyHint;
		$this->options['qtip_side']='right,bottom,top,left';
		if (isset($this->options['qtip_ajxhrf'])) unset($this->options['qtip_ajxhrf']);

		if (is_null($this->url)) $this->url=[
			'/'.Inflector::camel2id(
				StringHelper::basename(
					get_class($this->model)
				)
			).'/copy',
			'id' => $this->model->id,
		];

	}

	public function run()
	{
		return Html::a('<span class="far fa-copy copy-item-button"></span>', $this->url, $this->options);
	}
}
