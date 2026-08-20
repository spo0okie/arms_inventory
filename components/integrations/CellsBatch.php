<?php

namespace app\components\integrations;

use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Наполнение ячеек колонки интеграции для пачки моделей — серверная
 * половина списочного режима (docs/dev/integrations.md §5 «Колонки в списках»).
 *
 * Поток на страницу грида: по строкам дёшево (appliesTo/binding/кэш),
 * по всем протухшим строкам — ОДИН вызов renderCells() провайдера
 * (батч до транспорта), построчная запись в кэш. Кэш построчный
 * (ключ — binding, {@see PanelsCache}), а не постраничный: состав
 * страницы зависит от фильтров/пагинации и как ключ бесполезен.
 *
 * Вызывается из proxy ({@see \app\controllers\IntegrationsController::actionCells()});
 * RBAC проверяет вызывающий.
 */
class CellsBatch
{
	/**
	 * Слот кэша ячеек колонки (роль panelId в {@see PanelsCache}):
	 * ячейка — третий вид рендера после full/compact, кэш у неё свой
	 */
	public static function cacheSlot(string $columnId): string
	{
		return 'cell-'.$columnId;
	}

	/**
	 * Ячейка при рендере страницы грида (зовёт {@see CellColumn}):
	 * ТОЛЬКО построчный кэш, без внешних вызовов — свежий как есть,
	 * протухший приглушённо. Протухшая/пустая ячейка помечается
	 * data-атрибутами: батч-скрипт (IntegrationCellsAsset) собирает их
	 * по (провайдер, колонка, класс) и наполняет одним POST
	 * /integrations/cells → {@see render()}.
	 */
	public static function renderGridCell(IntegrationProvider $provider, string $columnId,
		ArmsModel $model): string
	{
		if (!$provider->appliesTo($model)) return '';

		$binding = $provider->binding($model);
		if (is_null($binding)) return $provider->renderUnboundCell($columnId, $model);

		$cached = PanelsCache::fetch($provider->id, static::cacheSlot($columnId), $binding);
		if ($cached && $cached['age'] <= $provider->cellTtl($columnId, get_class($model)))
			return $cached['html'];

		$body = $cached ? $cached['html']
			: '<span class="spinner-border spinner-border-sm text-secondary" role="status">'
				.'<span class="visually-hidden">Loading...</span></span>';

		return Html::tag('span', $body, [
			'class' => 'integration-cell integration-cell-stale',
			'style' => $cached ? 'opacity:.5' : null,
			'data' => [
				'provider' => $provider->id,
				'column' => $columnId,
				'class' => StringHelper::class2Id(get_class($model)),
				'id' => $model->id,
				'url' => Url::to(['/integrations/cells']),
			],
		]);
	}

	/**
	 * HTML ячеек колонки для пачки моделей одного класса.
	 *
	 * Неприменимые строки — пустая ячейка; применимые без привязки —
	 * renderUnboundCell(); свежий кэш отдаётся как есть; остальные —
	 * одним renderCells() с записью в кэш. Ошибка/таймаут внешней ИС —
	 * штатный исход: заглушка в протухшие ячейки, кэш не перетирается
	 * (контракт §3.1).
	 *
	 * @param ArmsModel[] $models модели одного класса
	 * @return array [model_id => html]
	 */
	public static function render(IntegrationProvider $provider, string $columnId,
		string $modelClass, array $models): array
	{
		$slot = static::cacheSlot($columnId);
		$ttl = $provider->cellTtl($columnId, $modelClass);

		$result = [];
		$pending = [];  //[id => model] строки, требующие похода во внешнюю ИС
		$bindings = []; //[id => binding] чтобы не звать binding() повторно при записи кэша
		foreach ($models as $model) {
			if (!$provider->appliesTo($model)) {
				$result[$model->id] = '';
				continue;
			}
			$binding = $provider->binding($model);
			if (is_null($binding)) {
				$result[$model->id] = $provider->renderUnboundCell($columnId, $model);
				continue;
			}
			$cached = PanelsCache::fetch($provider->id, $slot, $binding);
			if ($cached && $cached['age'] <= $ttl) {
				$result[$model->id] = $cached['html'];
				continue;
			}
			$pending[$model->id] = $model;
			$bindings[$model->id] = $binding;
		}

		if (!count($pending)) return $result;

		try {
			$cells = $provider->renderCells($columnId, array_values($pending));
		} catch (\Throwable $e) {
			Yii::warning("Integration cells {$provider->id}/$columnId failed: ".$e->getMessage(), __METHOD__);
			//в debug-режиме причина видна прямо в ячейке (помощь при
			//настройке); на проде — нейтральная заглушка, детали в логе
			$stub = '<span class="text-secondary opacity-75 integration-cell-error">'
				.(YII_DEBUG ? Html::encode($e->getMessage()) : 'недоступно')
				.'</span>';
			foreach (array_keys($pending) as $id) $result[$id] = $stub;
			return $result;
		}

		foreach ($pending as $id => $model) {
			if (!isset($cells[$id])) {
				//провайдер не вернул строку — пустая ячейка, без кэша
				$result[$id] = '';
				continue;
			}
			$result[$id] = $cells[$id];
			PanelsCache::store($provider->id, $slot, $bindings[$id], $cells[$id]);
		}
		return $result;
	}
}
