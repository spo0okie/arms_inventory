<?php

namespace tests\unit\models;

use app\models\Comps;
use Codeception\Test\Unit;

/**
 * Тесты Comps::getRawSearchLines - разбор отпечатков raw_soft/raw_hw в строки
 * для перепроверки текстового фильтра на стороне PHP (ячейка "ОС / софт")
 */
class CompsRawSearchLinesTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	public function testSoftAndHwLines()
	{
		$comp=new Comps();
		$comp->raw_soft='{"name":"7-Zip 22.01","publisher":"Igor Pavlov"},'
			.'{"name":"Google Chrome","publisher":""},'
			.'{"name":"","publisher":""}';
		$comp->raw_hw='{"motherboard":{"manufacturer":"ASUSTeK COMPUTER INC.","product":"H61M-G","serial":"140222247102869"},'
			.'"cpu":"Intel Core i5-3450"}';

		$this->assertSame([
			'7-Zip 22.01 (Igor Pavlov)',
			'Google Chrome',
			'motherboard: ASUSTeK COMPUTER INC. H61M-G 140222247102869',
			'cpu: Intel Core i5-3450',
		],$comp->rawSearchLines);
	}

	public function testEmptyAndBrokenData()
	{
		$comp=new Comps();
		$this->assertSame([],$comp->rawSearchLines,'пустые отпечатки - пустой список');

		$comp->raw_soft='{"name":"обрублен'; //битый JSON
		$comp->raw_hw='{"cpu":"Intel"}';
		$this->assertSame(['cpu: Intel'],$comp->rawSearchLines,'битый raw_soft не мешает разбору raw_hw');
	}
}
