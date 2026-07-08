<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 05.04.2019
 * Time: 13:25
 */

namespace app\components;

use app\helpers\DocsHelper;
use app\helpers\FieldsHelper;
use app\models\base\ArmsModel;
use yii\base\Widget;
use yii\helpers\Url;


class HintIconWidget extends Widget
{
	/*
	 * Иконка помощи по сущности: тултип с коротким описанием (modelDescription),
	 * клик ведет на страницу встроенной документации сущности (docs/model).
	 * Если на странице есть инфоблок документации (DocsPanelWidget с id по
	 * соглашению panelId) - клик раскрывает его, а не уводит со страницы
	 * (progressive enhancement: обработчик вешает сама панель, без панели
	 * ссылка работает как обычно).
	 * Ранее вела на внешнюю wiki (params hintUrl/wikiUrl) - см. plans/help-docs.md.
	 */

	/** @var string имя класса модели (например '\app\models\Arms') */
	public $model;

	public $hintText='Помощь по этой странице';
	public $action='index';
	public $cssClass='';

	public function run()
	{
		$model=$this->model;
		$description=is_subclass_of($model,ArmsModel::class)?
			$model::modelDescription():'';

		$title=property_exists($model,'titles')?$model::$titles:'Справка';

		return $this->render('hintIcon/icon', [
			'hintText' => $this->hintText,
			'href' => Url::to(['/docs/model','class'=>DocsHelper::modelClassId($model)]),
			'cssClass' => $this->cssClass,
			'tooltipOptions' => array_merge(
				FieldsHelper::toolTipOptions(
					$title,
					$description?:$this->hintText
				),
				//если на странице есть инфоблок документации - клик раскроет его
				['data-panel-target'=>'#'.DocsPanelWidget::panelId($model)]
			),
		]);
	}
}
