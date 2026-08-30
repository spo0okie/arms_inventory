<?php

namespace tests\unit\models;

use app\models\Comps;
use app\models\TechModels;
use app\models\Techs;
use Codeception\Test\Unit;
use Yii;

/**
 * Стандарт хранения MAC-адресов: строки голого hex в нижнем регистре
 * (диапазон — компактная пара start-end), канон — MacsHelper::fixList().
 *
 * На стандарт рассчитан поиск (LIKE по подстроке hex): запись с
 * разделителями он молча промахивает, и адрес, который инвентаризация
 * знает, выглядит неопознанным. Поэтому нормализацию держит beforeSave
 * ОБЕИХ моделей — фильтр валидации save(false) обходит, а именно так
 * пишут агенты; легаси подчищает миграция
 * m260830_000001_normalize_mac_storage.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class MacStorageStandardTest extends Unit
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

	/** ОС: save(false) (путь агентов) всё равно нормализует адреса */
	public function testCompsNormalizesMacOnRawSave()
	{
		$os = new Comps();
		$os->setAttributes(['name' => 'PC-STD-'.uniqid(),
			'mac' => "D8-BB-C1-8B-7E-B6\n00:1a:2b:3c:4d:5e\ne0cb.4eec.e74b"], false);
		$this->assertTrue($os->save(false));
		$os->refresh();

		$this->assertSame("d8bbc18b7eb6\n001a2b3c4d5e\ne0cb4eece74b", $os->mac);
	}

	/** Оборудование: тот же стандарт тем же путём (включая диапазон) */
	public function testTechsNormalizesMacOnRawSave()
	{
		$model = new TechModels();
		$model->setAttributes(['name' => 'Модель MAC-стандарта '.uniqid(), 'comment' => ''], false);
		$this->assertTrue($model->save(false));

		$tech = new Techs();
		$tech->setAttributes(['model_id' => $model->id, 'num' => 'STD-'.uniqid(),
			'mac' => "D8:BB:C1:8B:7E:00\nd8bb.c18b.7e00-d8bb.c18b.7eff", 'history' => ''], false);
		$this->assertTrue($tech->save(false));
		$tech->refresh();

		$lines = explode("\n", (string)$tech->mac);
		$this->assertSame('d8bbc18b7e00', $lines[0]);
		//диапазон хранится компактной парой start-end
		$this->assertStringContainsString('d8bbc18b7e00', $lines[1]);
		$this->assertStringContainsString('d8bbc18b7eff', $lines[1]);
		$this->assertDoesNotMatchRegularExpression('/[^0-9a-f\-\n]/', $tech->mac);
	}
}
