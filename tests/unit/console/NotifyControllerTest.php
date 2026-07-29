<?php

namespace tests\unit\console;

use app\console\commands\NotifyController;
use app\models\Contracts;
use app\models\Notifications;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;
use yii\db\Expression;
use yii\mail\BaseMailer;

/**
 * Тесты консольной части механизма оповещений (plans/notifications.md):
 * notify/send - единственная точка отправки почты (успех/ошибка/ретраи),
 * notify/watch - декларативные правила "залежавшихся" объектов с repeat,
 * notify/cleanup - отложенная чистка отправленного.
 */
class NotifyControllerTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var Users */
	private $user;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		$this->user = Users::find()
			->where(['not', ['Email' => null]])->andWhere(['<>', 'Email', ''])->andWhere(['Uvolen' => 0])
			->one();
		$this->assertNotNull($this->user);
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function controller(): NotifyController
	{
		return new NotifyController('notify', Yii::$app);
	}

	/**
	 * Успешная отправка: письмо уходит получателю, sent_at проставляется,
	 * запись покидает очередь.
	 */
	public function testSendDeliversAndMarksSent()
	{
		$n = Notifications::enqueue($this->user->id, 'тема отправки', '<p>тело</p>', 'test:send-ok');

		$this->controller()->actionSend();

		$n->refresh();
		$this->assertNotNull($n->sent_at, 'после успешной отправки должен стоять sent_at');
		$this->assertEquals(0, Notifications::findPending(5)->count(), 'очередь пуста');

		$email = $this->tester->grabLastSentEmail();
		$this->assertNotNull($email, 'письмо должно быть отправлено');
		$this->assertEquals('тема отправки', $email->getSubject());
		$this->assertArrayHasKey($this->user->Email, $email->getTo());
	}

	/**
	 * Ошибка транспорта: attempts++/last_error, письмо остаётся в очереди;
	 * после maxAttempts очередь его больше не берёт.
	 */
	public function testSendFailureIncrementsAttempts()
	{
		$n = Notifications::enqueue($this->user->id, 's', 'b', 'test:send-fail');

		$broken = new class extends BaseMailer {
			public $messageClass = \yii\symfonymailer\Message::class;
			protected function sendMessage($message): bool
			{
				throw new \RuntimeException('SMTP умер');
			}
		};
		$original = Yii::$app->get('mailer');
		Yii::$app->set('mailer', $broken);
		try {
			$this->controller()->actionSend();
		} finally {
			Yii::$app->set('mailer', $original);
		}

		$n->refresh();
		$this->assertNull($n->sent_at);
		$this->assertEquals(1, $n->attempts);
		$this->assertStringContainsString('SMTP умер', $n->last_error);
		$this->assertEquals(1, Notifications::findPending(5)->count(), 'после 1 ошибки письмо ещё в очереди');
		$this->assertEquals(0, Notifications::findPending(1)->count(), 'после maxAttempts очередь его не берёт');
	}

	/**
	 * Письмо без валидного получателя (у пользователя пропала почта)
	 * закрывается сразу с пояснением, а не ретраится впустую.
	 */
	public function testSendWithoutRecipientClosesImmediately()
	{
		$n = Notifications::enqueue($this->user->id, 's', 'b', 'test:send-nomail');
		Users::updateAll(['Email' => ''], ['id' => $this->user->id]);

		$controller = $this->controller();
		$controller->actionSend();

		$n->refresh();
		$this->assertNull($n->sent_at);
		$this->assertEquals($controller->maxAttempts, $n->attempts);
		$this->assertStringContainsString('нет получателя', $n->last_error);
	}

	/**
	 * watch: правило ставит письмо ответственным залежавшегося документа;
	 * повторный прогон не дублирует; после отправки повтор не раньше repeat.
	 */
	public function testWatchRuleWithRepeatGuard()
	{
		$contract = Contracts::find()->one();
		Yii::$app->db->createCommand()->insert('users_in_contracts', [
			'contracts_id' => $contract->id, 'users_id' => $this->user->id,
		])->execute();

		Yii::$app->params['notifyRules'] = [
			'stale-test' => [
				'class' => Contracts::class,
				'condition' => ['contracts.id' => $contract->id],
				'age' => '1 second', //updated_at старый или NULL - документ "залежался"
				'subject' => 'Завис документ «{name}»',
				'repeat' => '1 day',
			],
		];

		$eventKey = "watch:stale-test:{$contract->id}";
		$this->controller()->actionWatch();
		$n = Notifications::find()->where(['user_id' => $this->user->id, 'event_key' => $eventKey])->one();
		$this->assertNotNull($n, 'правило должно поставить письмо в очередь');
		$this->assertStringContainsString($contract->name, $n->subject);

		//повторный прогон при неотправленном письме дублей не создаёт
		$this->controller()->actionWatch();
		$this->assertEquals(1, Notifications::find()->where(['event_key' => $eventKey])->count());

		//только что отправлено - repeat-заслон молчит
		$n->sent_at = new Expression('CURRENT_TIMESTAMP');
		$this->assertTrue($n->save());
		$this->controller()->actionWatch();
		$this->assertEquals(0,
			Notifications::find()->where(['event_key' => $eventKey, 'sent_at' => null])->count());

		//отправлено давнее repeat - пора напомнить снова
		Notifications::updateAll(['sent_at' => new Expression('DATE_SUB(NOW(), INTERVAL 2 DAY)')], ['id' => $n->id]);
		$this->controller()->actionWatch();
		$this->assertEquals(1,
			Notifications::find()->where(['event_key' => $eventKey, 'sent_at' => null])->count());
	}

	/**
	 * cleanup удаляет только давно отправленные: свежеотправленные - память
	 * для repeat, неотправленные - ещё очередь.
	 */
	public function testCleanupDeletesOnlyOldSent()
	{
		$old = Notifications::enqueue($this->user->id, 's', 'b', 'test:cleanup-old');
		Notifications::updateAll(['sent_at' => new Expression('DATE_SUB(NOW(), INTERVAL 100 DAY)')], ['id' => $old->id]);
		$fresh = Notifications::enqueue($this->user->id, 's', 'b', 'test:cleanup-fresh');
		Notifications::updateAll(['sent_at' => new Expression('CURRENT_TIMESTAMP')], ['id' => $fresh->id]);
		$pending = Notifications::enqueue($this->user->id, 's', 'b', 'test:cleanup-pending');

		$this->controller()->actionCleanup(90);

		$this->assertNull(Notifications::findOne($old->id), 'старое отправленное удаляется');
		$this->assertNotNull(Notifications::findOne($fresh->id), 'свежее отправленное остаётся (память repeat)');
		$this->assertNotNull(Notifications::findOne($pending->id), 'неотправленное остаётся в очереди');
	}
}
