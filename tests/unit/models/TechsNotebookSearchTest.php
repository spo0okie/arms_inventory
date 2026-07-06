<?php

namespace tests\unit\models;

use app\models\TechModels;
use app\models\TechsSearch;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты поиска по записной книжке оборудования (issue #206).
 *
 * Записная книжка хранится в колонке techs.history. Проверяется, что:
 *  - отдельный фильтр по записной книжке (history) находит оборудование по заметке;
 *  - поиск по инв-номеру (inv_sn) теперь ищет и по записной книжке;
 *  - оборудование без нужной заметки не попадает в выборку.
 *
 * Данные вставляются сырыми INSERT и оборачиваются в транзакцию с откатом
 * (unit-suite без cleanup) — устойчиво к уже существующим в БД записям.
 */
class TechsNotebookSearchTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var int id оборудования с искомой заметкой */
	private $withNoteId;

	/** @var int id оборудования без заметки */
	private $withoutNoteId;

	/** @var string уникальный маркер заметки */
	private $marker;

	protected function _before()
	{
		$modelId = (int)TechModels::find()->select('id')->scalar();
		if (!$modelId) {
			$this->markTestSkipped('В тестовой БД нет tech_models для создания оборудования');
		}

		$this->transaction = Yii::$app->db->beginTransaction();
		$this->marker = 'NOTEBOOK' . strtoupper(uniqid());

		$this->withNoteId = $this->insertTech($modelId, 'заметка про ' . $this->marker . ' в книжке');
		$this->withoutNoteId = $this->insertTech($modelId, 'посторонний текст без маркера');
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function insertTech(int $modelId, string $history): int
	{
		Yii::$app->db->createCommand()->insert('techs', [
			'model_id' => $modelId,
			'history'  => $history,
		])->execute();
		return (int)Yii::$app->db->getLastInsertID();
	}

	/**
	 * @return int[] id из числа созданных тестом, реально попавших в выборку
	 */
	private function foundOwnIds(array $params): array
	{
		$dataProvider = (new TechsSearch())->search($params);
		$rows = (clone $dataProvider->query)
			->andWhere(['techs.id' => [$this->withNoteId, $this->withoutNoteId]])
			->all();
		return array_map(static function ($t) { return (int)$t->id; }, $rows);
	}

	/**
	 * Отдельный фильтр по записной книжке находит оборудование по заметке.
	 */
	public function testDedicatedNotebookFilter()
	{
		$found = $this->foundOwnIds(['TechsSearch' => ['history' => $this->marker]]);

		$this->assertContains($this->withNoteId, $found, 'Оборудование с заметкой должно находиться по фильтру записной книжки');
		$this->assertNotContains($this->withoutNoteId, $found, 'Оборудование без заметки не должно находиться');
	}

	/**
	 * Поиск по инв-номеру теперь ищет также и по записной книжке.
	 */
	public function testInvNumberSearchCoversNotebook()
	{
		$found = $this->foundOwnIds(['TechsSearch' => ['inv_sn' => $this->marker]]);

		$this->assertContains($this->withNoteId, $found, 'Поиск по инв-номеру должен находить и по записной книжке');
		$this->assertNotContains($this->withoutNoteId, $found, 'Постороннее оборудование не должно находиться');
	}
}
