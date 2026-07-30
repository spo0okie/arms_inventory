<?php

namespace tests\unit\models;

use app\models\TechModels;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты устойчивости цепочки "установлено в"/"входит в АРМ" к кольцам в данных.
 *
 * Регрессия: REST /api/techs?expand=effectiveUser падал по max_execution_time —
 * в данных было кросс-кольцо (техника A установлена в B, а B входит в АРМ A),
 * рекурсивный getEffectiveUser() крутился по нему вечно: каждый виток загружал
 * свежие экземпляры моделей, и relation-кэш не срабатывал. Одиночные проверки
 * validateRecursiveLink (arms_id и installed_id по отдельности) такое кольцо
 * не ловят, потому что оно проходит через два разных поля.
 *
 * Фикс двойной: (1) getEffectiveUser обходит цепочку итеративно с учетом
 * посещенных id; (2) validateEffectiveChain не дает сохранить такое кольцо.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class TechsEffectiveChainTest extends Unit
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
	 * Создает минимальную запись оборудования в обход валидации
	 * (формат инв. номера и пр. здесь не при чем - проверяем цепочку связей)
	 */
	private function makeTech(array $attrs): Techs
	{
		$tech = new Techs();
		$tech->setAttributes(array_merge([
			'model_id' => $this->makeModel()->id,
			'history' => '',
		], $attrs), false);
		$this->assertTrue($tech->save(false));
		return $tech;
	}

	/** Одна модель оборудования на весь тест */
	private function makeModel(): TechModels
	{
		static $model = null;
		if (is_object($model) && !$model->isNewRecord && TechModels::findOne($model->id)) return $model;
		$model = new TechModels();
		$model->setAttributes(['name' => 'Тестовая модель для цепочек ' . uniqid(), 'comment' => ''], false);
		$this->assertTrue($model->save(false));
		return $model;
	}

	/**
	 * То самое кольцо из инцидента: A установлено в B, а B входит в АРМ A.
	 * getEffectiveUser должен завершиться (до фикса тест бы завис) и вернуть null.
	 */
	public function testEffectiveUserSurvivesCrossLoop()
	{
		$a = $this->makeTech(['num' => 'TST-999901']);
		$b = $this->makeTech(['num' => 'TST-999902', 'arms_id' => $a->id]);
		$a->updateAttributes(['installed_id' => $b->id]);

		//загружаем свежие экземпляры - как это делает REST-выдача
		$this->assertNull(Techs::findOne($a->id)->effectiveUser);
		$this->assertNull(Techs::findOne($b->id)->effectiveUser);
	}

	/**
	 * Штатное разрешение пользователя по цепочке не сломано:
	 * телефон -> установлен в док-станцию -> та входит в АРМ -> у АРМа пользователь.
	 */
	public function testEffectiveUserResolvesThroughChain()
	{
		$user = new Users();
		$user->setAttributes(['Ename' => 'Тестовый Владелец Армович', 'Persg' => 2, 'Uvolen' => 0], false);
		$this->assertTrue($user->save(false));

		$arm = $this->makeTech(['num' => 'TST-999903', 'user_id' => $user->id]);
		$dock = $this->makeTech(['num' => 'TST-999904', 'arms_id' => $arm->id]);
		$phone = $this->makeTech(['num' => 'TST-999905', 'installed_id' => $dock->id]);

		$effective = Techs::findOne($phone->id)->effectiveUser;
		$this->assertTrue(is_object($effective));
		$this->assertEquals($user->id, $effective->id);
	}

	/**
	 * Валидация не дает замкнуть кросс-кольцо: B уже входит в АРМ A,
	 * попытка установить A в B должна быть отклонена.
	 */
	public function testValidatorRejectsCrossLoop()
	{
		$a = $this->makeTech(['num' => 'TST-999906']);
		$b = $this->makeTech(['num' => 'TST-999907', 'arms_id' => $a->id]);

		$a->installed_id = $b->id;
		$this->assertFalse($a->validate(['installed_id']));
		$this->assertNotEmpty($a->getErrors('installed_id'));
	}

	/**
	 * Ложных срабатываний нет: честная цепочка (телефон в док-станции,
	 * док-станция в АРМе) и ромб (installed и arm на одну и ту же цель)
	 * проходят валидацию.
	 */
	public function testValidatorAllowsLegitChains()
	{
		$arm = $this->makeTech(['num' => 'TST-999908']);
		$dock = $this->makeTech(['num' => 'TST-999909', 'arms_id' => $arm->id]);

		$phone = $this->makeTech(['num' => 'TST-999910']);
		$phone->installed_id = $dock->id;
		$this->assertTrue($phone->validate(['installed_id']), print_r($phone->errors, true));

		//ромб: обе ссылки на одну цель - сходящиеся пути, но не кольцо
		$device = $this->makeTech(['num' => 'TST-999911']);
		$device->installed_id = $arm->id;
		$device->arms_id = $arm->id;
		$this->assertTrue($device->validate(['installed_id', 'arms_id']), print_r($device->errors, true));
	}
}
