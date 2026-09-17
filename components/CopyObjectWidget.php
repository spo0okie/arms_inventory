<?php
namespace app\components;

use app\controllers\ArmsBaseController;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Иконка-ссылка «Создать копию» в заголовке формы редактирования объекта.
 * Ведёт на {controller}/copy?id={model->id} — форму создания, предзаполненную
 * атрибутами образца (см. ArmsBaseController::actionCopy).
 *
 * Живёт в форме редактирования, а не в просмотре (issue #221, как клонирование в Zabbix):
 * нужна реже остальных иконок и не должна загромождать H1 страницы объекта.
 *
 * Сам решает, рисоваться ли: только для сохранённой модели и только если текущий
 * контроллер (тот, что отрисовал форму) поддерживает копирование
 * ({@see ArmsBaseController::copySupported()}). Поэтому вьюхи просто ставят виджет в H1.
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

	/**
	 * @var array|string|null URL копирования; null — действие copy текущего контроллера
	 */
	public $url=null;
	public $options=[];

	public function init() {
		if (is_null($this->copyHint))
			$this->copyHint='Создать копию';

		$this->options['qtip_ttip']=$this->copyHint;
		$this->options['qtip_side']='right,bottom,top,left';
		$this->options['data']['pjax']=0;
		if (isset($this->options['qtip_ajxhrf'])) unset($this->options['qtip_ajxhrf']);
	}

	/**
	 * Показывать ли иконку
	 * @return bool
	 */
	protected function visible(): bool
	{
		if (!is_object($this->model) || $this->model->isNewRecord || empty($this->model->id)) return false;
		if (!is_null($this->url)) return true;
		$controller=Yii::$app->controller;
		return $controller instanceof ArmsBaseController && $controller->copySupported();
	}

	public function run()
	{
		if (!$this->visible()) return '';
		$url=$this->url ?? Url::to(['copy','id'=>$this->model->id]);
		return ' '.Html::a('<span class="far fa-copy copy-item-button"></span>', $url, $this->options);
	}
}
