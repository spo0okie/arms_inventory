<?php

namespace app\modules\schedules\models;

/**
 * «Временный доступ» — то же расписание ({@see Schedules}), но в контексте предоставления
 * доступа: расписание, к которому привязаны списки доступа (ACL). Отдельной таблицы нет.
 *
 * Обёртка нужна обвязке страниц ScheduledAccessController (breadcrumbs, иконка «?» справки,
 * инфопанель документации — см. views/layouts/main.php): у страниц временных доступов свои
 * titles/modelDescription и своя MD-страница (docs/help/models/scheduled-access.md),
 * при этом обычные «Расписания» остаются нетронутыми.
 */
class ScheduledAccess extends Schedules
{
	public static $titles = 'Временные доступы';
	public static $title  = 'Временный доступ';

	public static function modelDescription(): string
	{
		return 'Временные доступы: расписания с привязанными списками доступа (ACL) — '
			.'учитывают, кому, куда, на какое время и на каком основании предоставлен доступ.';
	}

	/**
	 * Совместимость форм и POST-запросов со Schedules: в БД и формах это
	 * одна и та же сущность, обёртка меняет только подачу страниц.
	 */
	public function formName()
	{
		return 'Schedules';
	}

	public function init()
	{
		parent::init();
		//журнал изменений общий со Schedules (отдельного ScheduledAccessHistory нет и не нужно)
		$this->historyClass = SchedulesHistory::class;
	}
}
