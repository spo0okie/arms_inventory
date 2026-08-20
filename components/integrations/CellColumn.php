<?php

namespace app\components\integrations;

use app\components\assets\IntegrationCellsAsset;
use app\models\base\ArmsModel;
use kartik\grid\DataColumn;
use Yii;

/**
 * Колонка интеграции в гриде (списочный режим,
 * docs/dev/integrations.md §5 «Колонки в списках»).
 *
 * Рендер строки НЕ ходит во внешние ИС и не дергает proxy: только
 * построчный файловый кэш ({@see PanelsCache}) — свежий как есть,
 * протухший приглушённо. Протухшие/пустые ячейки помечаются data-
 * атрибутами; скрипт {@see IntegrationCellsAsset} собирает их по
 * (провайдер, колонка, класс) и наполняет ОДНИМ POST /integrations/cells
 * на страницу грида (батч — {@see CellsBatch}).
 *
 * В гриды колонки дописывает {@see \app\components\DynaGridWidget}
 * (generic, вне defaultOrder => скрыты по умолчанию, пользователь
 * включает персонализацией). Скрытая колонка не рендерится вовсе и
 * не стоит ничего, даже чтений кэша.
 */
class CellColumn extends DataColumn
{
	/** @var IntegrationProvider провайдер-владелец колонки */
	public $provider;

	/** @var string id колонки (ключ в gridColumns() провайдера) */
	public $columnId = '';

	public $format = 'raw';
	public $enableSorting = false;

	public function init()
	{
		parent::init();
		IntegrationCellsAsset::register(Yii::$app->view);
	}

	protected function renderDataCellContent($model, $key, $index)
	{
		if (!$model instanceof ArmsModel || $model->isNewRecord) return '';
		return CellsBatch::renderGridCell($this->provider, $this->columnId, $model);
	}
}
