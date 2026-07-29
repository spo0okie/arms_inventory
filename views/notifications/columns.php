<?php

/**
 * Определение колонок для Grid таблицы очереди уведомлений (index).
 *
 * @var yii\web\View $this
 * @var app\models\NotificationsSearch $searchModel
 */

return [
	'user_id',
	//тема играет роль name (колонки name у очереди нет): ItemColumn рендерит
	//кликабельную ссылку на просмотр уведомления, как именная колонка в других гридах
	'subject' => ['class' => \app\components\gridColumns\ItemColumn::class],
	'event_key',
	'created_at',
	'sent_at',
	'attempts',
	'last_error',
];
