<?php

namespace tests\unit\models;

use app\models\Notifications;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;
use yii\db\Expression;

/**
 * Тесты outbox-модели механизма оповещений (plans/notifications.md):
 * дедупликация enqueue() по (user_id, event_key) среди неотправленных
 * и семантика wasSent() для повторных напоминаний notify/watch.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class NotificationsTest extends Unit
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
			->where(['not', ['Email' => null]])
			->andWhere(['<>', 'Email', ''])
			->andWhere(['Uvolen' => 0])
			->one();
		$this->assertNotNull($this->user, 'в тестовой БД должен быть сотрудник с e-mail');
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	/**
	 * Повторный enqueue с тем же ключом не плодит записи, а обновляет
	 * неотправленную: несколько событий до отправки = одно письмо
	 * с последним состоянием. Счётчик попыток при этом сбрасывается.
	 */
	public function testEnqueueDeduplicates()
	{
		$first = Notifications::enqueue($this->user->id, 'v1', '<p>v1</p>', 'test:dedup');
		$first->attempts = 3;
		$first->last_error = 'старая ошибка';
		$this->assertTrue($first->save());

		$second = Notifications::enqueue($this->user->id, 'v2', '<p>v2</p>', 'test:dedup');

		$this->assertEquals($first->id, $second->id, 'неотправленная запись должна переиспользоваться');
		$this->assertEquals(1, Notifications::find()->where(['event_key' => 'test:dedup'])->count());
		$this->assertEquals('v2', $second->subject);
		$this->assertEquals('<p>v2</p>', $second->body);
		$this->assertEquals(0, $second->attempts, 'счётчик попыток сбрасывается свежим содержимым');
		$this->assertNull($second->last_error);
	}

	/**
	 * Отправленная запись не переиспользуется — то же событие после отправки
	 * даёт новую запись в очереди.
	 */
	public function testEnqueueAfterSentCreatesNewRow()
	{
		$sent = Notifications::enqueue($this->user->id, 'v1', 'b', 'test:resend');
		$sent->sent_at = new Expression('CURRENT_TIMESTAMP');
		$this->assertTrue($sent->save());

		$next = Notifications::enqueue($this->user->id, 'v2', 'b', 'test:resend');
		$this->assertNotEquals($sent->id, $next->id);
		$this->assertEquals(2, Notifications::find()->where(['event_key' => 'test:resend'])->count());
	}

	/**
	 * Без ключа дедупликации каждый enqueue - отдельное письмо.
	 */
	public function testEnqueueWithoutKeyAlwaysCreates()
	{
		$a = Notifications::enqueue($this->user->id, 's', 'b', null);
		$b = Notifications::enqueue($this->user->id, 's', 'b', null);
		$this->assertNotEquals($a->id, $b->id);
	}

	/**
	 * wasSent: неотправленная запись не считается; окно withinSeconds
	 * отсчитывается по sent_at на стороне БД; null = "когда-либо".
	 */
	public function testWasSentSemantics()
	{
		$n = Notifications::enqueue($this->user->id, 's', 'b', 'test:was-sent');
		$this->assertFalse(Notifications::wasSent($this->user->id, 'test:was-sent'),
			'неотправленная запись не считается отправленной');

		//отправлено 100 секунд назад
		Notifications::updateAll(
			['sent_at' => new Expression('DATE_SUB(NOW(), INTERVAL 100 SECOND)')],
			['id' => $n->id]
		);

		$this->assertTrue(Notifications::wasSent($this->user->id, 'test:was-sent'), 'когда-либо - да');
		$this->assertTrue(Notifications::wasSent($this->user->id, 'test:was-sent', 200), 'в окне 200с - да');
		$this->assertFalse(Notifications::wasSent($this->user->id, 'test:was-sent', 50), 'в окне 50с - уже нет');
		$this->assertFalse(Notifications::wasSent($this->user->id, 'test:other-key'), 'другой ключ не считается');
	}
}
