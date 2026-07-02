<?php

namespace tests\unit\models;

use app\models\TechModels;
use app\models\TechsSearch;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты фильтрации TechsSearch.
 *
 * Проверяет поведение из issue #102: в списках оборудования и АРМ
 * (оба используют TechsSearch) оборудование в архивных состояниях
 * (tech_states.archived=1) по умолчанию скрывается, но остаётся доступным,
 * если пользователь явно выбрал этот статус.
 *
 * Suite unit сконфигурирован без транзакций и без cleanup, поэтому тест сам
 * оборачивает свои данные в транзакцию и откатывает её в _after — БД не
 * загрязняется и тест повторяем. Данные вставляются напрямую (createCommand),
 * минуя ActiveRecord-behaviors и журналирование, чтобы не тянуть всё дерево
 * зависимостей модели.
 */
class TechsSearchTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var int */
	private $modelId;

	/** @var int */
	private $activeStateId;

	/** @var int */
	private $archivedStateId;

	/** @var int[] id техники в неархивном состоянии */
	private $activeTechIds = [];

	/** @var int[] id техники в архивном состоянии */
	private $archivedTechIds = [];

	protected function _before()
	{
		$this->modelId = (int)TechModels::find()->select('id')->scalar();
		if (!$this->modelId) {
			$this->markTestSkipped('В тестовой БД нет tech_models для создания оборудования');
		}

		$db = Yii::$app->db;
		$this->transaction = $db->beginTransaction();

		$suffix = uniqid();
		$this->activeStateId = $this->insertState('test_active_' . $suffix, 0);
		$this->archivedStateId = $this->insertState('test_archived_' . $suffix, 1);

		for ($i = 0; $i < 2; $i++) {
			$this->activeTechIds[] = $this->insertTech($this->activeStateId);
			$this->archivedTechIds[] = $this->insertTech($this->archivedStateId);
		}
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function insertState(string $code, int $archived): int
	{
		Yii::$app->db->createCommand()->insert('tech_states', [
			'code' => $code,
			'name' => $code,
			'descr' => '',
			'archived' => $archived,
		])->execute();
		return (int)Yii::$app->db->getLastInsertID();
	}

	private function insertTech(int $stateId): int
	{
		Yii::$app->db->createCommand()->insert('techs', [
			'model_id' => $this->modelId,
			'state_id' => $stateId,
			'history' => '[]',
		])->execute();
		return (int)Yii::$app->db->getLastInsertID();
	}

	/**
	 * Возвращает id из числа созданных тестом, реально попавших в выборку.
	 * Клонируем запрос data-provider'а и ограничиваем его нашими записями,
	 * чтобы не зависеть от пагинации и уже существующих в БД данных.
	 *
	 * @return int[]
	 */
	private function foundOwnIds(array $params): array
	{
		$dataProvider = (new TechsSearch())->search($params);
		$ownIds = array_merge($this->activeTechIds, $this->archivedTechIds);
		$rows = (clone $dataProvider->query)
			->andWhere(['techs.id' => $ownIds])
			->all();
		return array_map(static function ($tech) {
			return (int)$tech->id;
		}, $rows);
	}

	/**
	 * Без фильтра по статусу архивное оборудование не показывается,
	 * неархивное — показывается.
	 */
	public function testArchivedExcludedByDefault()
	{
		$found = $this->foundOwnIds([]);

		foreach ($this->activeTechIds as $id) {
			$this->assertContains($id, $found, "Активное оборудование $id должно быть в списке");
		}
		foreach ($this->archivedTechIds as $id) {
			$this->assertNotContains($id, $found, "Архивное оборудование $id должно быть скрыто по умолчанию");
		}
	}

	/**
	 * Если пользователь явно выбрал архивный статус — такое оборудование видно.
	 */
	public function testArchivedVisibleWhenStatusSelected()
	{
		$found = $this->foundOwnIds([
			'TechsSearch' => ['state_id' => [$this->archivedStateId]],
		]);

		foreach ($this->archivedTechIds as $id) {
			$this->assertContains($id, $found, "При явном выборе статуса архивное оборудование $id должно быть видно");
		}
	}

	/**
	 * При включённом переключателе "Архивные" (archived=1 / showArchived)
	 * архивное оборудование показывается вместе с активным.
	 */
	public function testArchivedVisibleWhenShowArchivedOn()
	{
		$found = $this->foundOwnIds([
			'TechsSearch' => ['archived' => 1],
		]);

		foreach (array_merge($this->activeTechIds, $this->archivedTechIds) as $id) {
			$this->assertContains($id, $found, "С включённым переключателем оборудование $id должно быть видно");
		}
	}
}
