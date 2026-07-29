<?php

namespace tests\unit\components;

use app\models\Notifications;
use app\models\Sandboxes;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;
use yii\base\InvalidArgumentException;

/**
 * Тесты компонента Notifier (plans/notifications.md): фильтрация получателей
 * (уволенные и без e-mail молча пропускаются) и контракт notifyResponsibles
 * (модель обязана реализовать NotifyRecipientsInterface).
 */
class NotifierTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	/**
	 * Уволенные и пользователи без e-mail не получают писем,
	 * валидный получатель - получает.
	 */
	public function testNotifyUsersFiltersRecipients()
	{
		$valid = new Users(['Ename' => 'Тест Валидный', 'Email' => 'valid@test.local', 'Uvolen' => 0]);
		$fired = new Users(['Ename' => 'Тест Уволенный', 'Email' => 'fired@test.local', 'Uvolen' => 1]);
		$noMail = new Users(['Ename' => 'Тест Безпочты', 'Email' => '', 'Uvolen' => 0]);
		foreach ([$valid, $fired, $noMail] as $user) $this->assertTrue($user->save(false));

		$queued = Yii::$app->notifier->notifyUsers([$valid, $fired, $noMail, null], 'subj', 'body', 'test:filter');

		$this->assertCount(1, $queued);
		$this->assertEquals($valid->id, $queued[0]->user_id);
		$this->assertEquals(1, Notifications::find()->where(['event_key' => 'test:filter'])->count());
	}

	/**
	 * Мастер-рубильник notify.enable (боевой дефолт - false): выключенная
	 * инсталляция не копит очередь - notifyUsers ничего не ставит.
	 */
	public function testDisabledInstallationQueuesNothing()
	{
		Yii::$app->params['notify.enable'] = false;

		$user = new Users(['Ename' => 'Тест Выключенный', 'Email' => 'off@test.local', 'Uvolen' => 0]);
		$this->assertTrue($user->save(false));

		$queued = Yii::$app->notifier->notifyUsers([$user], 'subj', 'body', 'test:disabled');

		$this->assertCount(0, $queued);
		$this->assertEquals(0, Notifications::find()->where(['event_key' => 'test:disabled'])->count());
	}

	/**
	 * notifyResponsibles требует NotifyRecipientsInterface у модели.
	 */
	public function testNotifyResponsiblesRequiresInterface()
	{
		$this->expectException(InvalidArgumentException::class);
		Yii::$app->notifier->notifyResponsibles(new Sandboxes(), 's', 'b');
	}
}
