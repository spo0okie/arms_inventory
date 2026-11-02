<?php

namespace tests\unit\components;

use app\components\ResignDeferReport;
use app\models\Notifications;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/** Проверяет переходы исключений и сохранение отчёта в штатной очереди. */
class ResignDeferReportTest extends Unit
{
	private $transaction;
	private $params;
	private $report;
	private $employee;
	private $recipient;

	/** Изолирует письма и исключения транзакцией, снимок — временным файлом. */
	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		$this->params = Yii::$app->params;
		Notifications::deleteAll();
		Users::updateAll(['resign_defer' => 0, 'resign_defer_until' => null]);
		$this->recipient = $this->makeUser(['Email' => 'resign-report@example.test']);
		$this->employee = $this->makeUser(['resign_defer' => 1, 'updated_by' => 'flag-author', 'notepad' => "First <note>\nSecond line"]);
		Yii::$app->params['notify.enable'] = true;
		Yii::$app->params['notify.resignDefer.recipients'] = [$this->recipient->id, $this->recipient->id];
		Yii::$app->params['web.hostInfo'] = 'https://arms.example.test';
		Yii::$app->params['textFields']['Users.notepad'] = 'text';
		$this->report = new ResignDeferReport();
		$this->report->stateFile = tempnam(sys_get_temp_dir(), 'resign-report-');
		unlink($this->report->stateFile);
		mkdir($this->report->stateFile);
		$this->report->stateFile .= '/state.json';
	}

	/** Возвращает БД и конфигурацию; удаляет только собственные временные файлы. */
	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) $this->transaction->rollBack();
		Yii::$app->params = $this->params;
		$directory = dirname($this->report->stateFile);
		if (str_starts_with(basename($directory), 'resign-report-') && dirname($directory) === sys_get_temp_dir()) {
			\yii\helpers\FileHelper::removeDirectory($directory);
		}
	}

	/** Создаёт тестовую кадровую запись с журналированием. */
	private function makeUser(array $attributes): Users
	{
		$user = new Users();
		$user->setAttributes($attributes + ['Ename' => 'Report <User>', 'Uvolen' => 0, 'Persg' => 2], false);
		$this->assertTrue($user->save(), print_r($user->errors, true));
		return $user;
	}

	/** Первый проход отправляет весь активный список, повторный молчит. */
	public function testInitialAndUnchanged()
	{
		$this->assertSame(1, $this->report->run());
		$mail = Notifications::find()->one();
		$this->assertStringContainsString('flag-author', $mail->body);
		$this->assertStringContainsString('https://arms.example.test/users/view?id=' . $this->employee->id, $mail->body);
		$this->assertStringContainsString('&lt;User&gt;', $mail->body);
		$this->assertStringContainsString('бессрочно', $mail->body);
		$this->assertStringContainsString('First &lt;note&gt;<br', $mail->body);
		$this->assertStringContainsString('Second line', $mail->body);
		$this->assertSame(0, $this->report->run());
		$this->assertSame(1, (int)Notifications::find()->count());
	}

	/** Снятие флага указывает автора флага, даже после постороннего редактирования. */
	public function testRemovedFlagAuthor()
	{
		$this->report->run();
		$this->employee->resign_defer = 0;
		$this->employee->notepad = 'Current <comment>';
		$this->employee->updated_by = 'flag-remover';
		$this->assertTrue($this->employee->save());
		$this->employee->Doljnost = 'changed';
		$this->employee->updated_by = 'another-editor';
		$this->assertTrue($this->employee->save());
		$this->assertSame(1, $this->report->run());
		$body = Notifications::find()->orderBy(['id' => SORT_DESC])->one()->body;
		$this->assertStringContainsString('флаг исключения снят; снял: flag-remover', $body);
		$this->assertStringNotContainsString('another-editor', $body);
		$this->assertStringContainsString('Current &lt;comment&gt;', $body);
	}

	/** Дата включительна; истечение обнаруживается и без редактирования карточки. */
	public function testExpirationWithoutEditing()
	{
		$this->employee->resign_defer_until = date('Y-m-d');
		$this->assertTrue($this->employee->save());
		$this->assertSame(1, $this->report->run());
		//Снимок вчерашнего запуска: сегодня пользователь уже вне срока.
		$state = json_decode(file_get_contents($this->report->stateFile), true);
		$yesterday = date('Y-m-d', strtotime('-1 day'));
		$state['users'][$this->employee->id]['until'] = $yesterday;
		file_put_contents($this->report->stateFile, json_encode($state));
		Users::updateAll(['resign_defer_until' => $yesterday], ['id' => $this->employee->id]);
		$this->assertSame(1, $this->report->run());
		$body = Notifications::find()->orderBy(['id' => SORT_DESC])->one()->body;
		$this->assertStringContainsString('срок откладывания истёк', $body);
		$this->assertStringNotContainsString('снял:', $body);
	}

	/** Продление срока создаёт изменение и сохраняет первоначального автора флага. */
	public function testChangedDeadline()
	{
		$this->report->run();
		$this->employee->resign_defer_until = date('Y-m-d', strtotime('+2 days'));
		$this->employee->updated_by = 'deadline-editor';
		$this->assertTrue($this->employee->save());
		$this->assertSame(1, $this->report->run());
		$body = Notifications::find()->orderBy(['id' => SORT_DESC])->one()->body;
		$this->assertStringContainsString('flag-author', $body);
		$this->assertStringContainsString($this->employee->resign_defer_until, $body);
	}

	/** После сбоя сохранения снимка повтор не дублирует уже поставленное письмо. */
	public function testRetryAfterSnapshotFailure()
	{
		$this->report->run();
		//Возвращаем отсутствие первого снимка, как после сбоя его записи.
		unlink($this->report->stateFile);
		$this->assertSame(0, $this->report->run());
		$this->assertSame(1, (int)Notifications::find()->count());
	}

	/** Выключенный механизм не продвигает снимок и не теряет изменения. */
	public function testDisabled()
	{
		Yii::$app->params['notify.enable'] = false;
		$this->assertSame(0, $this->report->run());
		$this->assertFileDoesNotExist($this->report->stateFile);
		Yii::$app->params['notify.enable'] = true;
		$this->assertSame(1, $this->report->run());
	}

	/** Ошибка адресата оставляет снимок нетронутым для следующей попытки. */
	public function testInvalidRecipient()
	{
		Yii::$app->params['notify.resignDefer.recipients'] = ['not-found@example.test'];
		$this->assertSame(0, $this->report->run());
		$this->assertNotEmpty($this->report->errors);
		$this->assertFileDoesNotExist($this->report->stateFile);
		$this->assertSame(0, (int)Notifications::find()->count());
	}

	/** Изначально просроченное исключение не попадает в первоначальную сводку. */
	public function testInitiallyExpiredIsSkipped()
	{
		$this->employee->resign_defer_until = date('Y-m-d', strtotime('-1 day'));
		$this->assertTrue($this->employee->save());
		$this->assertSame(0, $this->report->run());
		$this->assertSame(0, (int)Notifications::find()->count());
	}

	/** Сводка доставляется тем же отправителем, который обслуживает документы. */
	public function testDeliveryThroughExistingSender()
	{
		$this->report->run();
		$controller = new \app\console\commands\NotifyController('notify', Yii::$app);
		$this->assertSame(0, $controller->actionSend());
		$email = $this->tester->grabLastSentEmail();
		$this->assertArrayHasKey($this->recipient->Email, $email->getTo());
		$this->assertStringContainsString('flag-author', $email->getSymfonyEmail()->getHtmlBody());
		$this->assertNotNull(Notifications::find()->one()->sent_at);
	}

	/** Сбой постановки письма откатывает очередь и оставляет снимок прежним. */
	public function testQueueFailureDoesNotAdvanceSnapshot()
	{
		$original = Yii::$app->notifier;
		Yii::$app->set('notifier', new class extends \app\components\Notifier {
			/** Имитирует сбой после записи письма в текущей транзакции. */
			public function notifyUsers(array $users, string $subject, string $body, ?string $eventKey = null): array
			{
				parent::notifyUsers($users, $subject, $body, $eventKey);
				throw new \RuntimeException('test queue failure');
			}
		});
		try {
			$this->assertSame(1, $this->report->run());
			$this->assertNotEmpty($this->report->errors);
			$this->assertFileDoesNotExist($this->report->stateFile);
			$this->assertSame(0, (int)Notifications::find()->count());
			$this->assertSame(['sent' => 1, 'failed' => 0], $this->report->sendPending());
			$email = $this->tester->grabLastSentEmail();
			$this->assertStringContainsString('test queue failure', $email->getSymfonyEmail()->getHtmlBody());
		} finally {
			Yii::$app->set('notifier', $original);
		}
		$this->assertSame(1, $this->report->run());
	}

	/** Повреждённый снимок не заменяется пустым списком и повторной рассылкой. */
	public function testCorruptSnapshot()
	{
		file_put_contents($this->report->stateFile, '{broken');
		$this->assertSame(1, $this->report->run());
		$this->assertSame('{broken', file_get_contents($this->report->stateFile));
		$this->assertStringContainsString('JsonException', Notifications::find()->one()->body);
	}
	/** Ошибка ссылки не скрывает имена, сроки и остальные строки сводки. */
	public function testMissingUrlProducesPartialReport()
	{
		Yii::$app->params['web.hostInfo'] = '';
		$this->assertSame(1, $this->report->run());
		$body = Notifications::find()->one()->body;
		$this->assertStringContainsString('&lt;User&gt;', $body);
		$this->assertStringContainsString('web.hostInfo', $body);
		$this->assertStringContainsString('бессрочно', $body);
		$this->assertFileDoesNotExist($this->report->stateFile);
		Yii::$app->params['web.hostInfo'] = 'https://arms.example.test';
		Yii::$app->params['textFields']['Users.notepad'] = 'text';
		$this->assertSame(1, $this->report->run());
		$this->assertFileExists($this->report->stateFile);
	}

	/** Ошибка чтения БД превращается в письмо, содержащее только ошибки. */
	public function testDatabaseReadFailureProducesErrorOnlyReport()
	{
		$broken = new class extends ResignDeferReport {
			/** Имитирует отказ запроса пользователей. */
			protected function loadUsers(array $previousIds): array
			{
				throw new \RuntimeException('users unavailable <test>');
			}
		};
		$broken->stateFile = $this->report->stateFile;
		$this->assertSame(1, $broken->run());
		$body = Notifications::find()->one()->body;
		$this->assertStringContainsString('users unavailable &lt;test&gt;', $body);
		$this->assertFileDoesNotExist($broken->stateFile);
	}

	/** Ошибка одного адресата не препятствует письму исправному адресату. */
	public function testInvalidRecipientDoesNotBlockOthers()
	{
		Yii::$app->params['notify.resignDefer.recipients'][] = 'missing@example.test';
		$this->assertSame(1, $this->report->run());
		$this->assertStringContainsString('missing@example.test', Notifications::find()->one()->body);
		$this->assertFileDoesNotExist($this->report->stateFile);
	}

	/** Ранее проверенные адреса позволяют отправить ошибку полного отказа БД. */
	public function testCachedRecipientSurvivesDatabaseFailure()
	{
		$this->report->run();
		$broken = new class extends ResignDeferReport {
			/** Имитирует недоступную БД адресатов. */
			protected function resolveRecipient($spec): array
			{
				throw new \RuntimeException('recipient database unavailable');
			}
			/** Имитирует отказ БД кадровых записей. */
			protected function loadUsers(array $previousIds): array
			{
				throw new \RuntimeException('users database unavailable');
			}
		};
		$broken->stateFile = $this->report->stateFile;
		$snapshot = file_get_contents($broken->stateFile);
		$this->assertSame(1, $broken->run());
		$this->assertSame($snapshot, file_get_contents($broken->stateFile));
		$this->assertSame(['sent' => 1, 'failed' => 0], $broken->sendPending());
		$body = $this->tester->grabLastSentEmail()->getSymfonyEmail()->getHtmlBody();
		$this->assertStringContainsString('recipient database unavailable', $body);
		$this->assertStringContainsString('users database unavailable', $body);
	}

	/** Исчерпанный обычный лимит не убирает сводку из дальнейшей отправки. */
	public function testReportRetriesPastAttemptLimit()
	{
		$this->report->run();
		$notification = Notifications::find()->one();
		$notification->attempts = 100;
		$this->assertTrue($notification->save());
		$this->assertSame(1, (int)Notifications::findPending(5)->count());
		$controller = new \app\console\commands\NotifyController('notify', Yii::$app);
		$this->assertSame(0, $controller->actionSend());
		$this->assertSame(0, (int)Notifications::findPending(5)->count());
	}

	/** Резервный файл сохраняется при SMTP-ошибке и удаляется после успеха. */
	public function testFallbackSurvivesSmtpFailure()
	{
		$broken = new class extends ResignDeferReport {
			/** Имитирует недоступность таблицы очереди. */
			protected function deliver(array $recipients, string $body, string $key): int
			{
				foreach ($recipients as &$recipient) $recipient['user'] = null;
				return parent::deliver($recipients, $body, $key);
			}
		};
		$broken->stateFile = $this->report->stateFile;
		$this->assertSame(1, $broken->run());
		$mailer = Yii::$app->mailer;
		Yii::$app->set('mailer', new class extends \yii\mail\BaseMailer {
			public $messageClass = \yii\symfonymailer\Message::class;
			/** Временный отказ SMTP. */
			protected function sendMessage($message): bool { return false; }
		});
		try {
			for ($i = 0; $i < 6; $i++) {
				$this->assertSame(['sent' => 0, 'failed' => 1], $broken->sendPending());
			}
		} finally {
			Yii::$app->set('mailer', $mailer);
		}
		$this->assertSame(['sent' => 1, 'failed' => 0], $broken->sendPending());
		$this->assertSame(['sent' => 0, 'failed' => 0], $broken->sendPending());
	}
	/** При отказе истории сохраняются ФИО, срок и остальные строки сводки. */
	public function testHistoryFailurePreservesReportRows()
	{
		$broken = new class extends ResignDeferReport {
			/** Имитирует отказ чтения журнала. */
			protected function lookupFlagAuthor(int $id, bool $enabled): string
			{
				throw new \RuntimeException('history unavailable');
			}
		};
		$broken->stateFile = $this->report->stateFile;
		$this->assertSame(1, $broken->run());
		$body = Notifications::find()->one()->body;
		$this->assertStringContainsString('&lt;User&gt;', $body);
		$this->assertStringContainsString('бессрочно', $body);
		$this->assertStringContainsString('history unavailable', $body);
		$this->assertStringContainsString('неизвестно', $body);
		$this->assertFileDoesNotExist($broken->stateFile);
	}

	/** notify/send отправляет резервное письмо даже при полностью недоступной БД. */
	public function testSenderDeliversFallbackBeforeDatabaseAccess()
	{
		$broken = new class extends ResignDeferReport {
			/** Имитирует ошибку основной очереди. */
			protected function deliver(array $recipients, string $body, string $key): int
			{
				foreach ($recipients as &$recipient) $recipient['user'] = null;
				return parent::deliver($recipients, $body, $key);
			}
		};
		$originalRuntime = Yii::getAlias('@runtime');
		//Резервный отправитель использует стандартный каталог runtime/notify.
		Yii::setAlias('@runtime', dirname($this->report->stateFile));
		$broken->stateFile = '@runtime/notify/resign-defer.json';
		$db = Yii::$app->db;
		try {
			$this->assertSame(1, $broken->run());
			Yii::$app->set('db', new class extends \yii\db\Connection {
				/** Отказ подключения без обращения к реальному серверу. */
				public function open() { throw new \RuntimeException('database offline'); }
			});
			$controller = new \app\console\commands\NotifyController('notify', Yii::$app);
			$this->assertSame(1, $controller->actionSend());
			$email = $this->tester->grabLastSentEmail();
			$this->assertArrayHasKey($this->recipient->Email, $email->getTo());
			$this->assertStringContainsString('БД адресатов недоступна', $email->getSymfonyEmail()->getHtmlBody());
		} finally {
			Yii::$app->set('db', $db);
			Yii::setAlias('@runtime', $originalRuntime);
		}
	}
	/** Удалённая карточка сохраняет записную книжку из предыдущего снимка. */
	public function testDeletedUserRetainsCommentFromSnapshot()
	{
		$this->report->run();
		Users::deleteAll(['id' => $this->employee->id]);
		$this->assertSame(1, $this->report->run());
		$body = Notifications::find()->orderBy(['id' => SORT_DESC])->one()->body;
		$this->assertStringContainsString('пользователь удалён', $body);
		$this->assertStringContainsString('First &lt;note&gt;', $body);
	}
	/** Markdown форматируется штатным типом и в новых, и в отменённых исключениях. */
	public function testMarkdownCommentInBothSections()
	{
		Yii::$app->params['textFields']['Users.notepad'] = 'markdown';
		$this->employee->notepad = '**Important note**';
		$this->assertTrue($this->employee->save());
		$this->assertSame(1, $this->report->run());
		$this->assertStringContainsString('<strong>Important note</strong>', Notifications::find()->one()->body);
		$this->employee->resign_defer = 0;
		$this->assertTrue($this->employee->save());
		$this->assertSame(1, $this->report->run());
		$this->assertStringContainsString('<strong>Important note</strong>', Notifications::find()->orderBy(['id' => SORT_DESC])->one()->body);
	}

	/** DokuWiki в письме уже отрендерена и не требует выполнения JavaScript. */
	public function testDokuWikiCommentUsesSynchronousRendererAndDefaultFormat()
	{
		unset(Yii::$app->params['textFields']['Users.notepad']);
		Yii::$app->params['textFields']['default'] = 'dokuwiki';
		$this->employee->notepad = '**Wiki note**';
		$this->assertTrue($this->employee->save());
		$report = new class extends ResignDeferReport {
			/** Подменяет внешний сервис DokuWiki готовым результатом рендера. */
			protected function renderWikiComment(string $notepad): string
			{
				if ($notepad !== '**Wiki note**') throw new \RuntimeException('wrong source text');
				return '<p><strong>Wiki note</strong></p>';
			}
		};
		$report->stateFile = $this->report->stateFile;
		$this->assertSame(1, $report->run());
		$body = Notifications::find()->one()->body;
		$this->assertStringContainsString('<strong>Wiki note</strong>', $body);
		$this->assertStringNotContainsString('<script', $body);
		$this->assertEmpty($report->errors);
	}

	/** Отказ DokuWiki сохраняет исходный текст и добавляет ошибку в отправляемое письмо. */
	public function testDokuWikiFailurePreservesRawCommentAndAddsError()
	{
		Yii::$app->params['textFields']['Users.notepad'] = 'dokuwiki';
		$report = new class extends ResignDeferReport {
			/** Имитирует отказ внешнего рендерера. */
			protected function renderWikiComment(string $notepad): string
			{
				throw new \RuntimeException('wiki renderer offline');
			}
		};
		$report->stateFile = $this->report->stateFile;
		$this->assertSame(1, $report->run());
		$body = Notifications::find()->one()->body;
		$this->assertStringContainsString('wiki renderer offline', $body);
		$this->assertStringContainsString('First &lt;note&gt;', $body);
		$this->assertFileDoesNotExist($report->stateFile);
	}
}
