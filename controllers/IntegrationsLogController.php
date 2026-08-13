<?php

namespace app\controllers;

use app\models\IntegrationsLog;

/**
 * Просмотр журнала действий интеграций (таблица integrations_log,
 * docs/dev/integrations.md §6). Только чтение: записи ведёт реестр
 * интеграций, руками их не создают и не правят.
 */
class IntegrationsLogController extends ArmsBaseController
{
	public $modelClass = IntegrationsLog::class;

	/**
	 * Read-only журнал: отключаем создание/редактирование/удаление и
	 * «мелкие» рендеры (item/ttip/name-поиск), которые модели не нужны.
	 * Остаются index (грид с фильтрами) и view (карточка записи).
	 * @return array
	 */
	public function disabledActions()
	{
		return ['create', 'copy', 'update', 'delete', 'validate', 'unlink', 'editable',
			'item', 'item-by-name', 'ttip'];
	}
}
