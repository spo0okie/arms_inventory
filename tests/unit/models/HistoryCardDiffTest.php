<?php

namespace tests\unit\models;

use app\models\CompsHistory;
use app\models\HistoryModel;
use app\models\Sandboxes;
use app\models\SandboxesHistory;
use app\models\TechModelsHistory;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты diff-API карточек изменений журнала истории (issue #194).
 *
 * Проверяется ядро механизма рендера изменений: классификация атрибутов
 * (скаляр / множество значений), представление множеств (ссылки _ids, JSON,
 * списки строк), вычисление выбывших/добавленных значений против предыдущей
 * записи и подбор самой предыдущей записи.
 *
 * Записи журнала для set-diff собираются в памяти (снапшоты - обычные атрибуты,
 * сохранение не требуется); цепочка getPreviousRecord проверяется на реальных
 * записях Sandboxes в транзакции с откатом (как HistoryCommentTest).
 */
class HistoryCardDiffTest extends Unit
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
	 * Множественные ссылки: diff показывает добавленные и выбывшие ID
	 */
	public function testMultiLinkSetDiff()
	{
		$previous = new CompsHistory(['services_ids' => '2,3,4']);
		$record = new CompsHistory(['services_ids' => '1,2,3']);
		$record->setPreviousRecord($previous);

		$this->assertTrue($record->attributeIsMultiValue('services_ids'));

		$diff = $record->attributeSetDiff('services_ids');
		$this->assertEquals([1], $diff['added']);
		$this->assertEquals([4], $diff['removed']);
	}

	/**
	 * Первая запись журнала (предыдущей нет): всё значение - добавленное
	 */
	public function testFirstRecordSetDiff()
	{
		$record = new CompsHistory(['services_ids' => '5,6']);
		$record->setPreviousRecord(null);

		$diff = $record->attributeSetDiff('services_ids');
		$this->assertEquals([5, 6], $diff['added']);
		$this->assertEquals([], $diff['removed']);
	}

	/**
	 * JSON: элементы «ключ: значение», изменение значения ключа даёт
	 * пару выбыло/добавлено
	 */
	public function testJsonSetDiff()
	{
		$previous = new TechModelsHistory(['front_rack_layout' => '{"rows":1,"cols":4}']);
		$record = new TechModelsHistory(['front_rack_layout' => '{"rows":2,"cols":4}']);
		$record->setPreviousRecord($previous);

		$this->assertTrue($record->attributeIsMultiValue('front_rack_layout'));

		$diff = $record->attributeSetDiff('front_rack_layout');
		$this->assertEquals(['rows: 2'], $diff['added']);
		$this->assertEquals(['rows: 1'], $diff['removed']);
	}

	/**
	 * Невалидный JSON не роняет diff: значение сравнивается целиком
	 */
	public function testJsonInvalidFallsBackToWholeValue()
	{
		$record = new TechModelsHistory(['front_rack_layout' => 'кривой-json']);
		$this->assertEquals(['кривой-json'], $record->attributeValueSet('front_rack_layout'));
	}

	/**
	 * Скаляры и одиночные ссылки множеством не считаются
	 */
	public function testScalarsAreNotMultiValue()
	{
		$record = new CompsHistory();
		$this->assertFalse($record->attributeIsMultiValue('name'));
		$this->assertFalse($record->attributeIsMultiValue('arm_id'));
	}

	/**
	 * Список изменённых атрибутов для карточки: несуществующие и служебные
	 * имена отфильтровываются
	 */
	public function testChangedAttributesListFiltering()
	{
		$record = new CompsHistory(['changed_attributes' => 'name,unknown_attr,updated_at']);
		$this->assertEquals(['name'], $record->changedAttributesList());
	}

	/**
	 * Запись об удалении объекта: флаг взводится, список изменений пуст
	 */
	public function testDeletionRecord()
	{
		$record = new CompsHistory(['changed_attributes' => HistoryModel::DELETED_FLAG]);
		$this->assertTrue($record->isDeletionRecord());
		$this->assertEquals([], $record->changedAttributesList());

		$normal = new CompsHistory(['changed_attributes' => 'name']);
		$this->assertFalse($normal->isDeletionRecord());
	}

	/**
	 * getPreviousRecord на реальных записях: последняя запись видит предыдущую,
	 * первая - null; подсунутая setPreviousRecord запись имеет приоритет
	 */
	public function testPreviousRecordChain()
	{
		$m = new Sandboxes(['name' => 'sb-' . uniqid()]);
		$this->assertTrue($m->save(false));
		$m->suffix = 'zz';
		$this->assertTrue($m->save(false));

		$records = SandboxesHistory::find()
			->where(['master_id' => $m->id])
			->orderBy(['id' => SORT_DESC])
			->all();
		$this->assertCount(2, $records);

		[$last, $first] = $records;

		//ленивый запрос находит предыдущую запись
		$this->assertEquals($first->id, $last->previousRecord->id);
		//у первой записи предыдущей нет
		$this->assertNull($first->previousRecord);
		//изменённое поле у второй записи отражено в списке для карточки
		$this->assertContains('suffix', $last->changedAttributesList());
	}
}
