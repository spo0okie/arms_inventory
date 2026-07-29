<?php

namespace app\console\commands;

use app\components\Notifier;
use app\models\Notifications;
use app\models\NotifyRecipientsInterface;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Expression;
use yii\helpers\Html;

/**
 * Механизм оповещений: отправка outbox и сторож "залежавшихся" объектов
 * (plans/notifications.md, issue #184).
 *
 * Единственная точка фактической отправки почты в приложении — notify/send;
 * всё остальное (behavior смены атрибута, notify/watch) лишь ставит письма
 * в очередь notifications.
 *
 * Cron:
 *   yii notify/send     — каждые ~5 минут
 *   yii notify/watch    — раз в час
 *   yii notify/cleanup  — раз в сутки
 */
class NotifyController extends Controller
{
	/** @var int сколько писем отправлять за один прогон send */
	public $limit = 100;

	/** @var int после скольких неудачных попыток оставить письмо в покое (остаётся в таблице для разбора) */
	public $maxAttempts = 5;

	/** {@inheritdoc} */
	public function options($actionID)
	{
		return array_merge(parent::options($actionID), $actionID === 'send' ? ['limit', 'maxAttempts'] : []);
	}

	/**
	 * Отправляет накопившиеся уведомления из outbox.
	 *
	 * Неудачная отправка не останавливает прогон: attempts++/last_error, письмо
	 * останется в очереди до maxAttempts. Письмо без валидного получателя
	 * (пользователь удалён/без почты) закрывается сразу.
	 */
	public function actionSend()
	{
		$sent = $failed = 0;
		foreach (Notifications::findPending($this->maxAttempts)->limit($this->limit)->all() as $notification) {
			/** @var Notifications $notification */
			$email = $notification->user->Email ?? '';
			if (empty($email)) {
				$notification->attempts = $this->maxAttempts;
				$notification->last_error = 'нет получателя (пользователь удалён или без e-mail)';
				$notification->save();
				$failed++;
				continue;
			}
			try {
				$ok = Yii::$app->mailer->compose()
					->setTo($email)
					->setSubject($notification->subject)
					->setHtmlBody((string)$notification->body)
					->send();
				if (!$ok) throw new \RuntimeException('mailer вернул false');
				$notification->sent_at = new Expression('CURRENT_TIMESTAMP');
				$notification->last_error = null;
				$notification->save();
				$sent++;
			} catch (\Throwable $e) {
				$notification->attempts++;
				$notification->last_error = mb_substr($e->getMessage(), 0, 255);
				$notification->save();
				$failed++;
				Yii::warning("notify/send #{$notification->id} -> {$email}: {$e->getMessage()}", 'notify');
			}
		}
		$this->stdout("отправлено: $sent, ошибок: $failed\n");
		return $failed ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
	}

	/**
	 * Прогоняет правила оповещений о "залежавшихся" объектах из
	 * params['notifyRules'] и ставит письма в очередь ответственным.
	 *
	 * Формат правила (ключ массива = имя правила, входит в ключ дедупликации):
	 * ```php
	 * 'notifyRules' => [
	 *     'contract-stale-new' => [
	 *         'class' => \app\models\Contracts::class,
	 *         //условие выборки: массив для andWhere() либо callable(ActiveQuery):ActiveQuery
	 *         'condition' => ['state_id' => 1],
	 *         'age' => '1 day',      //не менялся дольше (по updated_at); null = без ограничения
	 *         //PHP-фильтр после SQL-выборки: для ВЫЧИСЛЯЕМЫХ признаков (deliveryState
	 *         //и т.п.), которые не выразить в condition; callable($model): bool
	 *         'filter' => null,
	 *         //дополнительные получатели ПОВЕРХ автосписка ответственных:
	 *         //массив из id/Login/E-Mail (см. Notifier::findUsers) либо callable($model):Users[]
	 *         'extraRecipients' => null,
	 *         'subject' => 'Документ «{name}» завис в статусе «Новый»',
	 *         'body' => null,        //null = subject + ссылка; строка с {name}/{id}; callable($model):string
	 *         'repeat' => '3 days',  //повторное письмо не чаще; null = однократно
	 *     ],
	 * ],
	 * ```
	 */
	public function actionWatch()
	{
		$rules = Yii::$app->params['notifyRules'] ?? [];
		$queued = 0;
		foreach ($rules as $ruleKey => $rule) {
			/** @var \app\models\base\ArmsModel|string $class */
			$class = $rule['class'] ?? null;
			if (!$class || !class_exists($class)) {
				$this->stderr("правило $ruleKey: класс не найден\n");
				continue;
			}
			$query = $class::find();
			$condition = $rule['condition'] ?? null;
			if (is_callable($condition)) $query = call_user_func($condition, $query);
			elseif (is_array($condition)) $query->andWhere($condition);

			if (!empty($rule['age'])) {
				$seconds = static::interval2seconds($rule['age']);
				$column = $class::tableName() . '.updated_at';
				//NULL updated_at = запись никогда не менялась - для "залежалось дольше N" это тоже попадание
				$query->andWhere(['or',
					[$column => null],
					['<=', $column, new Expression('DATE_SUB(NOW(), INTERVAL :sec SECOND)', [':sec' => $seconds])],
				]);
			}

			$repeatSeconds = empty($rule['repeat']) ? null : static::interval2seconds($rule['repeat']);

			//статичный список доп. получателей резолвим один раз на правило,
			//callable-вариант вычисляется на каждый объект (внутри цикла)
			$extra = $rule['extraRecipients'] ?? null;
			$extraStatic = is_array($extra) ? Notifier::findUsers($extra) : [];

			foreach ($query->all() as $model) {
				if (!$model instanceof NotifyRecipientsInterface) {
					$this->stderr("правило $ruleKey: $class не реализует NotifyRecipientsInterface\n");
					break;
				}
				//вычисляемые признаки проверяются в PHP - после SQL-выборки
				if (isset($rule['filter']) && !call_user_func($rule['filter'], $model)) continue;
				$eventKey = "watch:$ruleKey:{$model->id}";
				//получатели = ответственные объекта + дополнительные из правила (без дублей)
				$recipients = array_merge(
					$model->getNotifyRecipients(),
					$extraStatic,
					is_callable($extra) ? (array)call_user_func($extra, $model) : []
				);
				$unique = [];
				foreach ($recipients as $user)
					if ($user instanceof \app\models\Users) $unique[$user->id] = $user;
				//письмо уже уходило (и не пришло время повтора) - молчим;
				//неотправленные записи не в счёт: enqueue их просто освежит
				$users = array_filter($unique,
					fn($user) => !Notifications::wasSent($user->id, $eventKey, $repeatSeconds));
				if (!count($users)) continue;

				$subject = $this->renderTemplate($rule['subject'] ?? "{$class::$title} «{name}»: требует внимания", $model);
				$body = isset($rule['body'])
					? $this->renderTemplate($rule['body'], $model)
					: $this->defaultWatchBody($subject, $model);
				$queued += count(Yii::$app->notifier->notifyUsers($users, $subject, $body, $eventKey));
			}
		}
		$this->stdout("поставлено в очередь: $queued\n");
		return ExitCode::OK;
	}

	/**
	 * Удаляет отправленные уведомления старше $days суток.
	 * Свежие отправленные не трогаем — по ним watch отсчитывает repeat.
	 */
	public function actionCleanup($days = 90)
	{
		$deleted = Notifications::deleteAll(['and',
			['not', ['sent_at' => null]],
			['<', 'sent_at', new Expression('DATE_SUB(NOW(), INTERVAL :days DAY)', [':days' => (int)$days])],
		]);
		$this->stdout("удалено: $deleted\n");
		return ExitCode::OK;
	}

	/**
	 * '1 day'/'2 hours'/'30 minutes' (форматы strtotime) -> секунды
	 */
	public static function interval2seconds(string $interval): int
	{
		$seconds = strtotime($interval, 0);
		if ($seconds === false || $seconds <= 0)
			throw new \InvalidArgumentException("не удалось разобрать интервал '$interval'");
		return $seconds;
	}

	/**
	 * Шаблон правила: callable($model):string либо строка с {name}/{id}
	 * @param string|callable $template
	 */
	protected function renderTemplate($template, $model): string
	{
		if (is_callable($template)) return (string)call_user_func($template, $model);
		$name = ($model->hasAttribute('name') && strlen((string)$model->name)) ? $model->name : '#' . $model->id;
		return strtr((string)$template, ['{name}' => $name, '{id}' => (string)$model->id]);
	}

	protected function defaultWatchBody(string $subject, $model): string
	{
		return '<p>' . Html::encode($subject) . '</p>' . Notifier::modelLinksFooter($model);
	}
}
