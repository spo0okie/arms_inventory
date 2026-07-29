<?php

namespace app\models;

/**
 * Модель, у которой есть "ответственные" — получатели e-mail-оповещений
 * (механизм оповещений, plans/notifications.md).
 *
 * Реализуется моделями, на которые вешается AttributeChangeNotifyBehavior
 * или по которым работают правила notify/watch.
 */
interface NotifyRecipientsInterface
{
	/**
	 * Получатели оповещений по этому объекту.
	 * Фильтровать уволенных/без почты не нужно — это делает Notifier.
	 * @return Users[]
	 */
	public function getNotifyRecipients(): array;
}
