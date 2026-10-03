<?php

namespace app\components\integrations;

use app\components\assets\IntegrationCellsAsset;
use app\models\base\ArmsModel;
use yii\base\Widget;

/**
 * Бейджи интеграций у элемента объекта вне гридов (docs/dev/integrations.md
 * §5 «Бейджи у элементов»). Пример: иконка статуса VPN рядом с IP-адресом
 * в карточке ОС или сотрудника.
 *
 * Виджет generic: спрашивает у реестра itemBadges() всех провайдеров —
 * конкретных интеграций ни в ядре, ни во view-файлах нет. Страница никогда
 * не ждёт внешнюю ИС: рисуется только кэш ячейки ({@see CellsBatch::renderItemBadge()}),
 * протухшие бейджи наполняет тот же скрипт, что и колонки гридов, —
 * одним POST /integrations/cells на (провайдер × бейдж × класс) со всей
 * страницы.
 */
class ItemBadgesWidget extends Widget
{
	/** @var ArmsModel|null объект, рядом с элементом которого рисуются бейджи */
	public ?ArmsModel $model = null;

	public function run()
	{
		if (!is_object($this->model) || $this->model->isNewRecord) return '';
		$model = $this->model;

		$html = '';
		foreach (IntegrationsRegistry::providers() as $provider) {
			$badges = $provider->itemBadges(get_class($model));
			if (!$badges) continue;
			if (!IntegrationsRegistry::userCanView($provider)) continue;
			foreach (array_keys($badges) as $badgeId) {
				$badge = CellsBatch::renderItemBadge($provider, $badgeId, $model);
				if ($badge !== '') $html .= '<span class="integration-badge ms-1m me-3">'.$badge.'</span>';
			}
		}

		if ($html !== '') IntegrationCellsAsset::register($this->view);
		return $html;
	}
}
