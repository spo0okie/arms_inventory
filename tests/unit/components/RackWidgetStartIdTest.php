<?php

namespace tests\unit\components;

use app\components\RackWidget;
use Codeception\Test\Unit;

/**
 * Тесты стартового номера юнита в раскладке корзины (issue #136).
 *
 * Стартовый номер (labelStartId) — номер первого места в корзине. Позволяет
 * задавать разную нумерацию передней и задней корзины одного устройства.
 */
class RackWidgetStartIdTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Вертикальная корзина 1 колонка × 6 рядов.
	 * Анонимный класс с пустым init() — чтобы не тянуть регистрацию ассетов
	 * (view/AssetManager) в чистый тест логики нумерации getSectorId().
	 *
	 * @param array $overrides доп. параметры конфигурации (напр. labelStartId)
	 * @return RackWidget
	 */
	private function makeRack(array $overrides = []): RackWidget
	{
		return new class(array_merge([
			'cols' => [['type' => 'units', 'count' => 1, 'size' => 100]],
			'rows' => [['type' => 'units', 'count' => 6, 'size' => 600]],
		], $overrides)) extends RackWidget {
			public function init() {}
		};
	}

	/**
	 * По умолчанию нумерация начинается с 1.
	 */
	public function testDefaultStartsAtOne()
	{
		$rack = $this->makeRack();
		$this->assertEquals(1, $rack->getSectorId(0, 0), 'Первый юнит должен быть 1');
		$this->assertEquals(6, $rack->getSectorId(0, 5), 'Последний юнит должен быть 6');
	}

	/**
	 * Заданный стартовый номер сдвигает всю нумерацию.
	 */
	public function testCustomStartId()
	{
		$rack = $this->makeRack(['labelStartId' => 43]);
		$this->assertEquals(43, $rack->getSectorId(0, 0), 'Первый юнит должен быть 43');
		$this->assertEquals(48, $rack->getSectorId(0, 5), 'Последний юнит должен быть 48');
	}

	/**
	 * Стартовый номер из JSON приходит строкой — должен работать так же.
	 */
	public function testStringStartId()
	{
		$this->assertEquals(43, $this->makeRack(['labelStartId' => '43'])->getSectorId(0, 0));
	}

	/**
	 * Пустое/некорректное значение => начинаем с 1 (обратная совместимость
	 * со старыми раскладками, где ключа labelStartId нет).
	 */
	public function testEmptyStartIdFallsBackToOne()
	{
		$this->assertEquals(1, $this->makeRack(['labelStartId' => ''])->getSectorId(0, 0));
		$this->assertEquals(1, $this->makeRack(['labelStartId' => null])->getSectorId(0, 0));
	}
}
