<?php

namespace app\components;

use app\models\Notifications;
use app\models\Users;
use app\models\UsersHistory;
use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Html;

/** Периодический отчёт об изменениях действующих исключений из увольнения. */
class ResignDeferReport
{
	/** @var string постоянный снимок; не должен очищаться вместе с кэшем */
	public $stateFile = '@runtime/notify/resign-defer.json';

	/** @var string[] ошибки последнего прохода, включая ошибки доставки */
	public $errors = [];

	/**
	 * Собирает доступную часть отчёта, ошибки и надёжно передаёт письмо отправителю.
	 * При любой ошибке снимок не продвигается: следующий проход повторяет сравнение.
	 */
	public function run(): int
	{
		$this->errors = [];
		if (empty(Yii::$app->params['notify.enable'])) return 0;
		$recipients = [];
		$body = '';
		$current = $state = $path = null;
		$lock = null;
		$changed = false;
		$key = 'resign-defer:error:' . uniqid('', true);
		try {
			try {
				$path = Yii::getAlias($this->stateFile);
				$recipients = $this->recipients($path);
				FileHelper::createDirectory(dirname($path));
				$lock = fopen($path . '.lock', 'c');
				if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
					throw new \RuntimeException('Не удалось заблокировать снимок отчёта');
				}
				$state = $this->readState($path);
				$users = $this->loadUsers(array_keys($state['users']));
				$current = [];
				foreach ($users as $id => $user) {
					//Ошибка одной карточки не скрывает остальных участников отчёта.
					try {
						if ($user->resignDeferred) {
							$current[$id] = ['name' => $user->Ename, 'until' => (string)$user->resign_defer_until];
						}
					} catch (\Throwable $e) {
						$this->error('Пользователь #' . $id, $e);
						if (isset($state['users'][$id])) $current[$id] = $state['users'][$id];
					}
				}
				ksort($current);
				$changes = self::changes($state['users'], $current);
				$changed = (bool)($changes['active'] || $changes['cancelled']);
				$key = 'resign-defer:' . hash('sha256', $state['revision'] . json_encode($current, JSON_THROW_ON_ERROR));
				if ($changed) $body = $this->render($changes, $users);
			} catch (\Throwable $e) {
				$this->error('Формирование отчёта', $e);
			}
			$queued = 0;
			if ($changed || $this->errors) {
				$queued = $this->deliver($recipients, $body, $key);
			}
			if (!$this->errors && $current !== null && $state !== null) {
				try {
					$this->writeJson($path, ['version' => 1, 'revision' => bin2hex(random_bytes(16)), 'users' => $current]);
				} catch (\Throwable $e) {
					$this->error('Сохранение снимка', $e);
					//Первое письмо уже в очереди: сбой снимка сообщаем отдельным письмом.
					$queued += $this->deliver($recipients, $body, $key . ':snapshot');
				}
			}
			return $queued;
		} finally {
			if (is_resource($lock)) fclose($lock);
		}
	}

	/** Разрешает адресатов по одному; при недоступной БД использует ранее проверенные адреса. */
	protected function recipients(string $path): array
	{
		$cache = [];
		try {
			if (is_file($path . '.recipients')) $cache = $this->readJson($path . '.recipients');
		} catch (\Throwable $e) {
			$this->error('Чтение резервных адресов', $e);
		}
		$recipients = $verified = [];
		$specs = Yii::$app->params['notify.resignDefer.recipients'] ?? [];
		if (!$specs) $this->error('Адресаты', new \RuntimeException('Не настроены notify.resignDefer.recipients'));
		foreach ($specs as $spec) {
			$cacheKey = is_scalar($spec) ? (string)$spec : '';
			try {
				$users = $this->resolveRecipient($spec);
				$user = $users[0] ?? null;
				if (!$user || $user->isArchived || empty($user->Email)) {
					//Известно невалидного адресата из старого кэша не восстанавливаем.
					$this->error('Адресат ' . $cacheKey, new \RuntimeException('Не найден, уволен или не имеет E-Mail'));
					continue;
				}
				$recipients[$user->id] = ['user' => $user, 'email' => $user->Email];
				$verified[$cacheKey] = ['id' => $user->id, 'email' => $user->Email];
			} catch (\Throwable $e) {
				$this->error('Адресат ' . $cacheKey, $e);
				if (isset($cache[$cacheKey]['id'], $cache[$cacheKey]['email'])) {
					$saved = $cache[$cacheKey];
					$recipients[$saved['id']] = ['user' => null, 'email' => $saved['email']];
					$verified[$cacheKey] = $saved;
				}
			}
		}
		if (!$recipients) $this->error('Адресаты', new \RuntimeException('Нет доступных получателей; снимок не будет обновлён'));
		if ($verified) {
			try {
				$this->writeJson($path . '.recipients', $verified);
			} catch (\Throwable $e) {
				$this->error('Сохранение резервных адресов', $e);
			}
		}
		return $recipients;
	}

	/** Разрешает одного адресата штатным механизмом уведомлений. */
	protected function resolveRecipient($spec): array
	{
		return Notifier::findUsers([$spec]);
	}

	/** Читает и проверяет снимок; повреждение не заменяется пустым списком. */
	protected function readState(string $path): array
	{
		if (!is_file($path)) return ['version' => 1, 'revision' => 'initial', 'users' => []];
		$state = $this->readJson($path);
		if (($state['version'] ?? null) !== 1 || !is_string($state['revision'] ?? null) || !is_array($state['users'] ?? null)) {
			throw new \RuntimeException('Некорректный снимок исключений: ' . $path);
		}
		foreach ($state['users'] as $row) {
			if (!is_array($row) || !array_key_exists('name', $row) || !is_string($row['until'] ?? null)) {
				throw new \RuntimeException('Некорректная запись в снимке исключений');
			}
		}
		return $state;
	}

	/** Загружает действующие исключения и прежних участников для определения отмен. */
	protected function loadUsers(array $previousIds): array
	{
		return Users::find()->where(['or', ['resign_defer' => 1], ['id' => $previousIds]])->indexBy('id')->all();
	}

	/** Добавляет ошибку в письмо и журнал, не прерывая обработку оставшихся записей. */
	protected function error(string $context, \Throwable $error): void
	{
		$message = $context . ': ' . get_class($error) . ': ' . $error->getMessage();
		$this->errors[] = $message;
		//Ошибка журнала не должна блокировать само аварийное письмо.
		try { Yii::error($message, 'notify'); } catch (\Throwable $ignored) {}
	}

	/** Дополняет доступную часть отчёта безопасно экранированными ошибками. */
	protected function withErrors(string $body): string
	{
		if (!$this->errors) return $body;
		return $body . '<h2>Ошибки формирования и передачи отчёта</h2><pre>'
			. Html::encode(implode("\n", $this->errors)) . '</pre><p>Снимок не обновлён. Изменения будут проверены повторно.</p>';
	}

	/**
	 * Независимо передаёт письмо каждому адресату: очередь БД, затем резервный файл.
	 * Ошибка одного адресата не мешает остальным. Неудачные проходы не дедуплицируются
	 * с полным отчётом, иначе письмо с ошибками могло бы скрыть исправленный отчёт.
	 */
	protected function deliver(array $recipients, string $body, string $key): int
	{
		$queued = 0;
		foreach ($recipients as $recipient) {
			$transaction = null;
			try {
				if (!$recipient['user']) throw new \RuntimeException('БД адресатов недоступна; используется резервный адрес');
				$eventKey = $this->errors ? $key . ':error:' . substr(hash('sha256', $this->withErrors($body)), 0, 16) : $key;
				$transaction = Yii::$app->db->beginTransaction();
				if (!Notifications::find()->where(['user_id' => $recipient['user']->id, 'event_key' => $eventKey])->exists()) {
					$notifications = Yii::$app->notifier->notifyUsers([$recipient['user']], 'Изменения исключений из увольнения', $this->withErrors($body), $eventKey);
					if (!$notifications) throw new \RuntimeException('Уведомление не поставлено в очередь');
					foreach ($notifications as $notification) {
						if ($notification->isNewRecord || $notification->hasErrors()) throw new \RuntimeException('Не удалось сохранить уведомление');
					}
					$queued += count($notifications);
				}
				$transaction->commit();
			} catch (\Throwable $e) {
				$this->error('Очередь для ' . $recipient['email'], $e);
				if ($transaction && $transaction->isActive) {
					try { $transaction->rollBack(); }
					catch (\Throwable $rollback) { $this->error('Откат очереди', $rollback); }
				}
				try {
					$file = $this->pendingDirectory() . '/' . hash('sha256', $key . $recipient['email'] . $this->withErrors($body)) . '.json';
					$this->writeJson($file, ['email' => $recipient['email'], 'body' => $this->withErrors($body)]);
					$queued++;
				} catch (\Throwable $fallback) {
					$this->error('Резервная очередь', $fallback);
					//Если недоступны оба хранилища, остаётся немедленная попытка SMTP.
					try { self::sendMessage($recipient['email'], $this->withErrors($body)); $queued++; }
					catch (\Throwable $smtp) { $this->error('Аварийная отправка', $smtp); }
				}
			}
		}
		return $queued;
	}

	/** Каталог резервных писем; обслуживается notify/send независимо от БД. */
	protected function pendingDirectory(): string
	{
		return dirname(Yii::getAlias($this->stateFile)) . '/resign-defer-pending';
	}

	/** Читает JSON с явной проверкой файловых ошибок. */
	protected function readJson(string $path): array
	{
		$json = file_get_contents($path);
		if ($json === false) throw new \RuntimeException('Не удалось прочитать ' . $path);
		$value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($value)) throw new \RuntimeException('Ожидался JSON-объект: ' . $path);
		return $value;
	}

	/** Атомарно сохраняет JSON с отдельным временным файлом для каждого писателя. */
	protected function writeJson(string $path, array $value): void
	{
		FileHelper::createDirectory(dirname($path), 0700);
		$json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
		$tmp = tempnam(dirname($path), '.resign-');
		if ($tmp === false) throw new \RuntimeException('Не удалось создать временный файл');
		try {
			if (file_put_contents($tmp, $json) !== strlen($json) || !rename($tmp, $path)) {
				throw new \RuntimeException('Не удалось сохранить ' . $path);
			}
		} finally {
			if (is_file($tmp)) unlink($tmp);
		}
	}

	/**
	 * Отправляет резервные письма без подключения к БД, с неограниченными повторами.
	 * Файл удаляется только после подтверждения SMTP; сбой отдельного письма не
	 * останавливает остальные. Блокировка защищает от параллельных отправителей.
	 */
	public function sendPending(int $limit = 100): array
	{
		$sent = $failed = 0;
		foreach (array_slice(glob($this->pendingDirectory() . '/*.json') ?: [], 0, $limit) as $file) {
			$lock = null;
			try {
				$lock = fopen($file . '.lock', 'c');
				if (!$lock) throw new \RuntimeException('Не удалось открыть блокировку ' . $file);
				if (!flock($lock, LOCK_EX | LOCK_NB)) continue;
				if (!is_file($file)) continue;
				$message = $this->readJson($file);
				self::sendMessage($message['email'], $message['body']);
				if (!unlink($file)) throw new \RuntimeException('Не удалось удалить отправленное письмо ' . $file);
				$sent++;
			} catch (\Throwable $e) {
				$this->error('Отправка резервного письма', $e);
				$failed++;
			} finally {
				if (is_resource($lock)) fclose($lock);
			}
		}
		return ['sent' => $sent, 'failed' => $failed];
	}

	/** Отправляет уже сформированное письмо, считая false от SMTP ошибкой. */
	protected static function sendMessage(string $email, string $body): void
	{
		if (!Yii::$app->mailer->compose()->setTo($email)->setSubject('Изменения исключений из увольнения')->setHtmlBody($body)->send()) {
			throw new \RuntimeException('mailer вернул false');
		}
	}

	/**
	 * Возвращает новые/изменённые действующие исключения и выбывших участников.
	 * Изменение ФИО само по себе письма не создаёт; изменение срока создаёт.
	 */
	public static function changes(array $previous, array $current): array
	{
		$active = [];
		foreach ($current as $id => $row) {
			if (!isset($previous[$id]) || $previous[$id]['until'] !== $row['until']) $active[$id] = $row;
		}
		return ['active' => $active, 'cancelled' => array_diff_key($previous, $current)];
	}

	/** Формирует HTML-сводку с безопасным экранированием имён и авторов. */
	protected function render(array $changes, array $users): string
	{
		$body = '<h2>Действующие исключения</h2><ul>';
		foreach ($changes['active'] as $id => $row) {
			$until = $row['until'] === '' ? 'бессрочно' : 'по ' . $row['until'] . ' включительно';
			$body .= '<li>' . $this->userLink($id, $row['name']) . ' — ' . Html::encode($until)
				. '; включил: ' . Html::encode($this->flagAuthor((int)$id, true)) . '</li>';
		}
		$body .= '</ul><h2>Отменённые исключения</h2><ul>';
		foreach ($changes['cancelled'] as $id => $row) {
			$user = $users[$id] ?? null;
			if (!$user) $reason = 'пользователь удалён';
			elseif (!$user->resign_defer) {
				$reason = 'флаг исключения снят; снял: ' . $this->flagAuthor((int)$id, false);
			} else {
				$reason = 'срок откладывания истёк (' . $user->resign_defer_until . ')';
			}
			$body .= '<li>' . $this->userLink($id, $user ? $user->Ename : $row['name']) . ' — ' . Html::encode($reason) . '</li>';
		}
		return $body . '</ul>';
	}

	/** Ссылка на профиль сотрудника через общий механизм URL уведомлений. */
	protected function userLink(int $id, ?string $name): string
	{
		try {
			$user = new Users();
			$user->id = $id;
			if (empty(Yii::$app->params['web.hostInfo'])) throw new \RuntimeException('Не настроен web.hostInfo: ссылка недоступна');
			$url = Notifier::modelUrl($user);
			if (!$url) throw new \RuntimeException('Не настроен web.hostInfo: ссылка недоступна');
			return Html::a(Html::encode($name ?: '#' . $id), $url);
		} catch (\Throwable $e) {
			$this->error('Ссылка пользователя #' . $id, $e);
			return Html::encode($name ?: '#' . $id);
		}
	}

	/**
	 * Находит автора последнего изменения именно флага, а не всей карточки.
	 * При отсутствии записи в истории честно возвращает «неизвестно».
	 */
	protected function flagAuthor(int $id, bool $enabled): string
	{
		try {
			return $this->lookupFlagAuthor($id, $enabled);
		} catch (\Throwable $e) {
			$this->error('Автор изменения флага пользователя #' . $id, $e);
			return 'неизвестно';
		}
	}

	/** Читает последнее изменение флага из журнала пользователей. */
	protected function lookupFlagAuthor(int $id, bool $enabled): string
	{
		foreach (UsersHistory::find()->where(['master_id' => $id])->orderBy(['id' => SORT_DESC])->each() as $history) {
			if (!in_array('resign_defer', explode(',', (string)$history->changed_attributes), true)) continue;
			//Проверяем последнее событие флага: более старое включение не его автор.
			return (bool)$history->resign_defer === $enabled && strlen((string)$history->updated_by)
				? $history->updated_by : 'неизвестно';
		}
		return 'неизвестно';
	}
}
