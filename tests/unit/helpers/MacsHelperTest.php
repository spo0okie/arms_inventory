<?php

namespace tests\unit\helpers;

use app\helpers\MacsHelper;
use Codeception\Test\Unit;

/**
 * Тесты нормализации MAC-адресов и компактного хранения диапазонов «через тире»
 * (issue #120). Диапазоны НЕ разворачиваются, а хранятся как "start-end".
 */
class MacsHelperTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Одиночный MAC с любыми разделителями остаётся одним адресом (12 hex).
	 */
	public function testSingleMacNormalized()
	{
		$this->assertEquals('001122334455', MacsHelper::fixList('00-11-22-33-44-55'));
		$this->assertEquals('001122334455', MacsHelper::fixList('00:11:22:33:44:55'));
	}

	/**
	 * Диапазон хранится компактно "start-end", а не разворачивается.
	 */
	public function testRangeStoredCompact()
	{
		$this->assertEquals(
			'001122334400-001122334448',
			MacsHelper::fixList('00:11:22:33:44:00 - 00:11:22:33:44:48')
		);
	}

	/**
	 * Границы диапазона упорядочиваются.
	 */
	public function testRangeBoundsOrdered()
	{
		$this->assertEquals(
			'001122334400-001122334448',
			MacsHelper::fixList('001122334448-001122334400')
		);
	}

	/**
	 * Диапазон из одного адреса схлопывается в одиночный MAC.
	 */
	public function testSingleAddressRangeCollapses()
	{
		$this->assertEquals('001122334455', MacsHelper::fixList('001122334455-001122334455'));
	}

	/**
	 * Смесь одиночного адреса и диапазона; дубликаты убираются.
	 */
	public function testMixedAndDeduplicated()
	{
		$result = MacsHelper::fixList("aabbccddeeff\n001122334400-001122334448\naabbccddeeff");
		$this->assertEquals("aabbccddeeff\n001122334400-001122334448", $result);
	}

	/**
	 * Нулевой одиночный MAC отсеивается, диапазон сохраняется.
	 */
	public function testZeroSingleDroppedRangeKept()
	{
		$this->assertEquals('', MacsHelper::fixList('00:00:00:00:00:00'));
		$this->assertEquals(
			'000000000000-000000000048',
			MacsHelper::fixList('00:00:00:00:00:00-00:00:00:00:00:48')
		);
	}
}
