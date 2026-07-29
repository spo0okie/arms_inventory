<?php

namespace app\controllers;

use app\models\Notifications;

/**
 * NotificationsController реализует CRUD операции для модели Notifications —
 * админский контроль очереди e-mail-оповещений (plans/notifications.md):
 * посмотреть очередь и отправленное, поправить застрявшее письмо
 * (сбросить attempts), удалить неактуальное.
 */
class NotificationsController extends ArmsBaseController
{
	/**
	 * @var string Класс модели для CRUD операций
	 */
	public $modelClass = Notifications::class;

	/**
	 * item-by-name отключён: у уведомлений нет колонки name (подписью служит
	 * тема письма), а findByName() ищет строго по столбцу name — на этой
	 * модели это SQL-ошибка.
	 * @return array
	 */
	public function disabledActions()
	{
		return ['item-by-name'];
	}
}
