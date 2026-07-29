<?php

namespace tests\unit\components;

use app\models\Contracts;
use app\models\ContractsStates;
use app\models\Notifications;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты AttributeChangeNotifyBehavior на боевом подключении - смена статуса
 * документа (Contracts.state_id, issue #64): оповещение ответственных через
 * outbox, дедупликация повторных смен, игнор нетрекаемых атрибутов и
 * псевдо-смен типа '5' vs 5.
 *
 * Данные оборачиваются в транзакцию и откатываются; откат заодно проверяет,
 * что запись очереди живёт в той же транзакции, что и сохранение модели.
 */
class AttributeChangeNotifyBehaviorTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var Users */
	private $user;

	/** @var Contracts */
	private $contract;

	/** @var int[] */
	private $stateIds;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();

		$this->user = Users::find()
			->where(['not', ['Email' => null]])->andWhere(['<>', 'Email', ''])->andWhere(['Uvolen' => 0])
			->one();
		$this->stateIds = ContractsStates::find()->select('id')->limit(2)->column();
		$contract = Contracts::find()->one();
		$this->assertNotNull($this->user);
		$this->assertNotNull($contract);
		$this->assertCount(2, $this->stateIds, 'нужно минимум два статуса документов');

		//привязываем ответственного напрямую (сохранение через модель дёргало бы behavior)
		Yii::$app->db->createCommand()->insert('users_in_contracts', [
			'contracts_id' => $contract->id,
			'users_id' => $this->user->id,
		])->execute();
		$this->contract = Contracts::findOne($contract->id); //перечитать со свежей связью
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function eventKey(): string
	{
		return "contracts:{$this->contract->id}:state_id";
	}

	private function otherState(): int
	{
		return (int)($this->contract->state_id == $this->stateIds[0] ? $this->stateIds[1] : $this->stateIds[0]);
	}

	/**
	 * Смена статуса ставит письмо ответственному в очередь; в теме и теле -
	 * название нового статуса (renderAttributeToText разрешил ссылку в имя).
	 */
	public function testStateChangeQueuesNotification()
	{
		$newState = $this->otherState();
		$this->contract->state_id = $newState;
		$this->assertTrue($this->contract->save(false));

		$notification = Notifications::find()
			->where(['user_id' => $this->user->id, 'event_key' => $this->eventKey()])
			->one();
		$this->assertNotNull($notification, 'смена статуса должна породить запись в outbox');

		$stateName = ContractsStates::findOne($newState)->name;
		$this->assertStringContainsString($stateName, $notification->subject);
		$this->assertStringContainsString($stateName, $notification->body);
		$this->assertStringContainsString($this->contract->name, $notification->subject);
	}

	/**
	 * При заданном web.hostInfo тело письма содержит абсолютные ссылки
	 * на документ и на его журнал истории (у Contracts есть ContractsHistory).
	 */
	public function testBodyContainsModelAndHistoryLinks()
	{
		Yii::$app->params['web.hostInfo'] = 'http://arms.test';

		$this->contract->state_id = $this->otherState();
		$this->assertTrue($this->contract->save(false));

		$notification = Notifications::find()
			->where(['user_id' => $this->user->id, 'event_key' => $this->eventKey()])
			->one();
		$this->assertNotNull($notification);
		$this->assertStringContainsString(
			"http://arms.test/contracts/view?id={$this->contract->id}",
			$notification->body, 'ссылка на документ');
		$this->assertStringContainsString(
			//&amp; - href в HTML-атрибуте экранирован Html::a
			'http://arms.test/history/journal?class=' . urlencode(\app\models\ContractsHistory::class) . "&amp;id={$this->contract->id}",
			$notification->body, 'ссылка на журнал истории');
	}

	/**
	 * Несколько смен статуса до отправки схлопываются в одно письмо
	 * с последним состоянием.
	 */
	public function testRepeatedChangesCollapse()
	{
		$first = $this->otherState();
		$this->contract->state_id = $first;
		$this->contract->save(false);
		$second = $this->otherState();
		$this->contract->state_id = $second;
		$this->contract->save(false);

		$rows = Notifications::find()
			->where(['user_id' => $this->user->id, 'event_key' => $this->eventKey()])
			->all();
		$this->assertCount(1, $rows, 'дедупликация по event_key');
		$this->assertStringContainsString(ContractsStates::findOne($second)->name, $rows[0]->subject,
			'в письме должно быть последнее состояние');
	}

	/**
	 * Смена нетрекаемого атрибута оповещений не порождает.
	 */
	public function testUntrackedAttributeIsSilent()
	{
		$this->contract->comment = 'обновили комментарий ' . uniqid();
		$this->assertTrue($this->contract->save(false));

		$this->assertEquals(0,
			Notifications::find()->where(['user_id' => $this->user->id])->count(),
			'смена comment не должна оповещать');
	}

	/**
	 * Псевдо-смена '5' vs 5 (строгое dirty-сравнение Yii) не считается сменой.
	 */
	public function testTypeJugglingIsNotAChange()
	{
		//сначала гарантируем непустой статус
		$this->contract->state_id = $this->otherState();
		$this->contract->save(false);
		Notifications::deleteAll(['user_id' => $this->user->id]);

		$this->contract->state_id = (string)$this->contract->state_id;
		$this->assertTrue($this->contract->save(false));

		$this->assertEquals(0,
			Notifications::find()->where(['user_id' => $this->user->id])->count(),
			"смена типа значения ('5' vs 5) - не смена статуса");
	}

	/**
	 * Точечный выключатель notify.docs.state.enable гасит статусные письма,
	 * не трогая остальной механизм (правила notify/watch работают через
	 * Notifier напрямую и этим флагом не ограничены).
	 */
	public function testPerTriggerDisableSilencesStatusOnly()
	{
		Yii::$app->params['notify.docs.state.enable'] = false;

		$this->contract->state_id = $this->otherState();
		$this->assertTrue($this->contract->save(false));
		$this->assertEquals(0,
			Notifications::find()->where(['user_id' => $this->user->id])->count(),
			'статусные письма выключены точечно');

		//остальной механизм жив: постановка через Notifier (путь notify/watch) работает
		$queued = Yii::$app->notifier->notifyResponsibles($this->contract, 'watch-письмо', '<p>b</p>', 'watch:manual');
		$this->assertCount(1, $queued, 'CLI/watch-сценарии продолжают оповещать');
	}

	/**
	 * Документ без ответственных никого не оповещает (и не падает).
	 */
	public function testNoRecipientsNoNotification()
	{
		Yii::$app->db->createCommand()->delete('users_in_contracts', ['contracts_id' => $this->contract->id])->execute();
		$contract = Contracts::findOne($this->contract->id);
		$contract->state_id = $this->otherState();
		$this->assertTrue($contract->save(false));

		$this->assertEquals(0, Notifications::find()->count());
	}
}
