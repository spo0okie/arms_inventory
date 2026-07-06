<?php

namespace tests\unit\models;

use app\models\Sandboxes;
use app\models\SandboxesHistory;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты пользовательского комментария к изменению в журнале (issue #205).
 *
 * Проверяется сквозной механизм: транзиентное поле мастер-модели historyComment
 * (ArmsModel) переносится в updated_comment History-записи, и запись в истории
 * создаётся даже когда полей не меняли, но был оставлен комментарий.
 *
 * Sandboxes выбрана как простая модель с парным журналом SandboxesHistory.
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class HistoryCommentTest extends Unit
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

	private function historyCount(int $masterId): int
	{
		return (int)SandboxesHistory::find()->where(['master_id' => $masterId])->count();
	}

	/**
	 * Комментарий без изменения полей всё равно создаёт запись в истории,
	 * а сам комментарий попадает в updated_comment (issue #205).
	 */
	public function testCommentWithoutFieldChangesCreatesHistory()
	{
		$m = new Sandboxes(['name' => 'sb-' . uniqid()]);
		$this->assertTrue($m->save(false));
		$countAfterInsert = $this->historyCount($m->id);

		//только комментарий, поля не меняем
		$m->historyComment = 'важное пояснение к изменению';
		$this->assertTrue($m->save(false));

		$this->assertEquals(
			$countAfterInsert + 1,
			$this->historyCount($m->id),
			'Комментарий без изменений полей должен создать запись истории'
		);

		$last = SandboxesHistory::find()
			->where(['master_id' => $m->id])
			->orderBy(['id' => SORT_DESC])
			->one();
		$this->assertEquals('важное пояснение к изменению', $last->updated_comment);
	}

	/**
	 * Без изменений и без комментария новая запись истории не создаётся
	 * (прежнее поведение сохраняется).
	 */
	public function testNoHistoryWithoutChangesOrComment()
	{
		$m = new Sandboxes(['name' => 'sb-' . uniqid()]);
		$m->save(false);
		$count = $this->historyCount($m->id);

		$m->historyComment = '';
		$m->save(false);

		$this->assertEquals($count, $this->historyCount($m->id), 'Без изменений и комментария запись не нужна');
	}

	/**
	 * Обычное изменение поля по-прежнему журналируется (регрессия).
	 */
	public function testNormalChangeStillJournaled()
	{
		$m = new Sandboxes(['name' => 'sb-' . uniqid()]);
		$m->save(false);
		$count = $this->historyCount($m->id);

		$m->suffix = 'zz';
		$m->save(false);

		$this->assertEquals($count + 1, $this->historyCount($m->id), 'Обычное изменение должно журналироваться');
	}

	/**
	 * historyComment загружается из данных формы через loadHistoryComment()
	 * (намеренно не safe-атрибут, поэтому забирается отдельно от load()).
	 */
	public function testHistoryCommentIsLoadable()
	{
		$m = new Sandboxes();
		$m->loadHistoryComment(['Sandboxes' => ['historyComment' => 'через форму']]);
		$this->assertEquals('через форму', $m->historyComment);

		//чужие данные не подхватываются
		$other = new Sandboxes();
		$other->loadHistoryComment(['Sandboxes' => ['name' => 'no comment here']]);
		$this->assertNull($other->historyComment);
	}
}
