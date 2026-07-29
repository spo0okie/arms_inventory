<?php

namespace app\components;

use app\helpers\StringHelper;
use app\models\Notifications;
use app\models\NotifyRecipientsInterface;
use app\models\Users;
use Yii;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\helpers\Url;

/**
 * Постановка e-mail-оповещений в outbox (plans/notifications.md, issue #184).
 *
 * Единственная задача компонента — определить получателей и записать письмо
 * в очередь notifications. SMTP здесь не трогается никогда: фактическую
 * отправку делает консольная команда notify/send.
 *
 * Регистрируется как Yii::$app->notifier в web- и console-конфиге.
 */
class Notifier extends Component
{
	/**
	 * Ставит письмо в очередь перечисленным пользователям.
	 * Уволенные и пользователи без e-mail молча пропускаются.
	 *
	 * Мастер-рубильник params['notify.enable'] проверяется именно здесь —
	 * единственная точка входа механизма: выключенная инсталляция не копит
	 * очередь ни от behavior-событий, ни от правил notify/watch.
	 *
	 * @param Users[] $users
	 * @param string|null $eventKey ключ дедупликации (см. Notifications::enqueue())
	 * @return Notifications[] созданные/обновлённые записи очереди
	 */
	public function notifyUsers(array $users, string $subject, string $body, ?string $eventKey = null): array
	{
		if (empty(Yii::$app->params['notify.enable'])) return [];
		$queued = [];
		foreach ($users as $user) {
			if (!$user instanceof Users) continue;
			if ($user->isArchived || empty($user->Email)) continue;
			$queued[] = Notifications::enqueue($user->id, $subject, $body, $eventKey);
		}
		return $queued;
	}

	/**
	 * Ставит письмо в очередь ответственным за объект.
	 *
	 * @param NotifyRecipientsInterface $model объект, чьих ответственных оповещаем
	 * @return Notifications[]
	 */
	public function notifyResponsibles($model, string $subject, string $body, ?string $eventKey = null): array
	{
		if (!$model instanceof NotifyRecipientsInterface)
			throw new InvalidArgumentException(get_class($model) . ' не реализует ' . NotifyRecipientsInterface::class);
		return $this->notifyUsers($model->getNotifyRecipients(), $subject, $body, $eventKey);
	}

	/**
	 * Разрешает список "адресатов из конфига" в объекты Users.
	 * Элемент списка: объект Users, id (число) либо строка - Login или E-Mail.
	 * Среди дублей (например, уволенный и действующий с одним логином)
	 * предпочитается неуволенный. Ненайденные адресаты пишутся в warning-лог
	 * и пропускаются - опечатка в конфиге не должна ронять прогон.
	 *
	 * @param array $specs
	 * @return Users[]
	 */
	public static function findUsers(array $specs): array
	{
		$found = [];
		foreach ($specs as $spec) {
			if ($spec instanceof Users) {
				$found[] = $spec;
				continue;
			}
			$user = is_numeric($spec)
				? Users::findOne((int)$spec)
				: Users::find()
					->where(['or', ['Login' => $spec], ['Email' => $spec]])
					->orderBy(['Uvolen' => SORT_ASC, 'id' => SORT_DESC])
					->one();
			if ($user) $found[] = $user;
			else Yii::warning("Notifier::findUsers: получатель '$spec' не найден", 'notify');
		}
		return $found;
	}

	/**
	 * Абсолютная ссылка на страницу объекта для письма.
	 * @param \yii\db\ActiveRecord $model
	 */
	public static function modelUrl($model): ?string
	{
		return static::absoluteUrl(StringHelper::class2Id(get_class($model)) . '/view', ['id' => $model->id]);
	}

	/**
	 * Абсолютная ссылка на журнал истории объекта, если у модели есть парный
	 * History-класс (ArmsModel::getHistoryClass()); иначе null.
	 * @param \yii\db\ActiveRecord $model
	 */
	public static function modelHistoryUrl($model): ?string
	{
		if (!method_exists($model, 'getHistoryClass') || !($historyClass = $model->getHistoryClass()))
			return null;
		return static::absoluteUrl('history/journal', ['class' => $historyClass, 'id' => $model->id]);
	}

	/**
	 * Стандартный футер письма со ссылками на объект и (при наличии) его историю.
	 * Без базового URL (см. absoluteUrl) — пустая строка: письмо уходит без ссылок.
	 * @param \yii\db\ActiveRecord $model
	 */
	public static function modelLinksFooter($model): string
	{
		$links = [];
		if ($url = static::modelUrl($model))
			$links[] = \yii\helpers\Html::a('Открыть', $url);
		if ($url = static::modelHistoryUrl($model))
			$links[] = \yii\helpers\Html::a('История изменений', $url);
		return count($links) ? '<p>' . implode(' &middot; ', $links) . '</p>' : '';
	}

	/**
	 * Абсолютный URL по маршруту.
	 *
	 * В консоли (notify/watch, notify/send) веб-запроса нет, поэтому базовый
	 * URL берётся из params['web.hostInfo'] (pretty-url вида /route?query);
	 * в веб-запросе fallback — hostInfo запроса через Url::to.
	 * Если базу выяснить не удалось — null.
	 */
	protected static function absoluteUrl(string $route, array $query = []): ?string
	{
		$hostInfo = Yii::$app->params['web.hostInfo'] ?? '';
		if ($hostInfo)
			return rtrim($hostInfo, '/') . '/' . ltrim($route, '/')
				. (count($query) ? '?' . http_build_query($query) : '');
		if (Yii::$app instanceof \yii\web\Application) {
			try {
				return Url::to(array_merge(['//' . ltrim($route, '/')], $query), true);
			} catch (\Throwable $e) {
				return null;
			}
		}
		return null;
	}
}
