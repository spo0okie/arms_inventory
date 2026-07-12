<?php

namespace app\modules\schedules\models;

/**
 * Search-класс для ScheduledAccess: нужен унаследованным действиям
 * ArmsBaseController (async-grid и т.п.), которые ищут `{ModelClass}Search`.
 * Поведение — как у SchedulesSearch (так работал контроллер до появления
 * обёртки ScheduledAccess); index использует свой SchedulesAclSearch.
 */
class ScheduledAccessSearch extends SchedulesSearch
{
	/**
	 * Совместимость параметров фильтрации со SchedulesSearch.
	 */
	public function formName()
	{
		return 'SchedulesSearch';
	}
}
