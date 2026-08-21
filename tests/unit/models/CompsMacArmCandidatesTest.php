<?php

namespace tests\unit\models;

use app\models\Comps;
use app\models\TechModels;
use app\models\Techs;
use Codeception\Test\Unit;
use Yii;

/**
 * Подсказка «нашлось оборудование с этим MAC» у ОС без АРМ (issue #218):
 * Comps::macArmCandidates() ищет железо с тем же адресом, чтобы карточка
 * предложила привязку.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class CompsMacArmCandidatesTest extends Unit
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

	/** Минимальная запись оборудования в обход валидации (проверяем поиск, не формат номера) */
	private function makeTech(string $mac): Techs
	{
		$model = new TechModels();
		$model->setAttributes(['name' => 'Модель для теста MAC '.uniqid(), 'comment' => ''], false);
		$this->assertTrue($model->save(false));

		$tech = new Techs();
		$tech->setAttributes([
			'model_id' => $model->id,
			'num' => 'MAC-'.uniqid(),
			'mac' => $mac,
			'history' => '',
		], false);
		$this->assertTrue($tech->save(false));
		return $tech;
	}

	private function makeComp(array $attrs): Comps
	{
		$comp = new Comps();
		$comp->setAttributes(array_merge(['name' => 'mac-test-'.uniqid()], $attrs), false);
		$this->assertTrue($comp->save(false));
		return $comp;
	}

	/** ОС без АРМ, но с MAC железа — железо предлагается кандидатом */
	public function testCandidateFound()
	{
		$tech = $this->makeTech("001122aa4455\n001122aa4466");
		$comp = $this->makeComp(['mac' => '001122aa4466', 'arm_id' => null]);

		$candidates = $comp->macArmCandidates();
		$this->assertCount(1, $candidates);
		$this->assertSame($tech->id, $candidates[0]->id);
	}

	/** У ОС с привязанным АРМ подсказки нет (и запроса тоже) */
	public function testNoCandidatesWhenArmSet()
	{
		$tech = $this->makeTech('001122bb4455');
		$comp = $this->makeComp(['mac' => '001122bb4455', 'arm_id' => $tech->id]);

		$this->assertSame([], $comp->macArmCandidates());
	}

	/** Ни MAC, ни совпадений — пусто */
	public function testNoCandidatesWithoutMatch()
	{
		$this->makeTech('001122cc4455');

		$this->assertSame([], $this->makeComp(['mac' => ''])->macArmCandidates());
		$this->assertSame([], $this->makeComp(['mac' => '001122cc9999'])->macArmCandidates());
	}

	/** Диапазон адресов (issue #120) кандидатов не ищет: сопоставляем конкретные адреса */
	public function testRangeIsNotMatched()
	{
		$this->makeTech('001122dd4400-001122dd44ff');
		$comp = $this->makeComp(['mac' => '001122dd4400-001122dd44ff']);

		$this->assertSame([], $comp->macArmCandidates());
	}
}
