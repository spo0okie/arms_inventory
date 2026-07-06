<?php

namespace tests\unit\models;

use app\models\CompsSearch;
use app\models\TechModels;
use app\models\TechsSearch;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты поиска MAC по вхождению в сохранённый диапазон (issue #120).
 *
 * Диапазон хранится компактно ("start-end") в поле mac. Проверяется, что поиск
 * находит оборудование/ОС по адресу ИЗ СЕРЕДИНЫ диапазона (которого нет в строке
 * буквально — значит срабатывает не LIKE, а разбор диапазона в SQL), и не находит
 * по адресу вне диапазона.
 *
 * Данные — сырыми INSERT в транзакции с откатом.
 */
class MacRangeSearchTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var int */
	private $techId;

	/** @var int */
	private $compId;

	protected function _before()
	{
		$modelId = (int)TechModels::find()->select('id')->scalar();
		if (!$modelId) {
			$this->markTestSkipped('В тестовой БД нет tech_models');
		}

		$this->transaction = Yii::$app->db->beginTransaction();

		Yii::$app->db->createCommand()->insert('techs', [
			'model_id' => $modelId,
			'history'  => '',
			'mac'      => '001122334400-0011223344ff',
		])->execute();
		$this->techId = (int)Yii::$app->db->getLastInsertID();

		Yii::$app->db->createCommand()->insert('comps', [
			'os'  => 'test os',
			'mac' => 'aabbcc000000-aabbcc0000ff',
		])->execute();
		$this->compId = (int)Yii::$app->db->getLastInsertID();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function techMatches(string $mac): bool
	{
		$dp = (new TechsSearch())->search(['TechsSearch' => ['mac' => $mac]]);
		return (bool)(clone $dp->query)->andWhere(['techs.id' => $this->techId])->exists();
	}

	private function compMatches(string $mac): bool
	{
		$dp = (new CompsSearch())->search(['CompsSearch' => ['mac' => $mac]]);
		return (bool)(clone $dp->query)->andWhere(['comps.id' => $this->compId])->exists();
	}

	/**
	 * Оборудование находится по MAC из середины сохранённого диапазона.
	 */
	public function testTechFoundByMacInsideRange()
	{
		$this->assertTrue($this->techMatches('00:11:22:33:44:80'), 'MAC из середины диапазона должен находиться');
	}

	/**
	 * Оборудование НЕ находится по MAC вне диапазона.
	 */
	public function testTechNotFoundOutsideRange()
	{
		$this->assertFalse($this->techMatches('00:11:22:33:45:ff'), 'MAC вне диапазона не должен находиться');
	}

	/**
	 * ОС находится по MAC из середины сохранённого диапазона.
	 */
	public function testCompFoundByMacInsideRange()
	{
		$this->assertTrue($this->compMatches('aa:bb:cc:00:00:80'), 'MAC из середины диапазона должен находиться');
	}

	/**
	 * ОС НЕ находится по MAC вне диапазона.
	 */
	public function testCompNotFoundOutsideRange()
	{
		$this->assertFalse($this->compMatches('aa:bb:cc:00:01:00'), 'MAC вне диапазона не должен находиться');
	}
}
