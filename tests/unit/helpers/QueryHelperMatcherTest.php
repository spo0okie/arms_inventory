<?php

namespace tests\unit\helpers;

use app\helpers\QueryHelper;
use Codeception\Test\Unit;

/**
 * Тесты PHP-зеркала поисковой грамматики (QueryHelper::stringMatcher):
 * предикат должен совпадать по смыслу с SQL-условием от querySearchString
 * (&, |, !, ^, $, *, -, экранирование, подстановки % и _)
 */
class QueryHelperMatcherTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Простой токен: вхождение подстроки без учета регистра (включая кириллицу)
	 */
	public function testSimpleToken()
	{
		$matcher=QueryHelper::stringMatcher('office');
		$this->assertTrue($matcher('Microsoft Office 2019'));
		$this->assertTrue($matcher('LIBREOFFICE'));
		$this->assertFalse($matcher('Google Chrome'));

		$matcher=QueryHelper::stringMatcher('касперск');
		$this->assertTrue($matcher('Антивирус Касперского'),'кириллица без учета регистра');
	}

	/**
	 * И/ИЛИ: & имеет приоритет над | (как в tokenizeString)
	 */
	public function testAndOr()
	{
		$matcher=QueryHelper::stringMatcher('Siemens & !NX');
		$this->assertTrue($matcher('Siemens Teamcenter'));
		$this->assertFalse($matcher('Siemens NX 12'));
		$this->assertFalse($matcher('Autodesk Inventor'));

		$matcher=QueryHelper::stringMatcher('Debian | Ubuntu');
		$this->assertTrue($matcher('Debian GNU/Linux 13'));
		$this->assertTrue($matcher('Ubuntu 24.04'));
		$this->assertFalse($matcher('AlmaLinux 9'));
	}

	/**
	 * Отрицание и подстановки: !токен, - (пусто), * (непусто)
	 */
	public function testNegationAndWildcards()
	{
		$matcher=QueryHelper::stringMatcher('!chrome');
		$this->assertTrue($matcher('Mozilla Firefox'));
		$this->assertFalse($matcher('Google Chrome'));

		$matcher=QueryHelper::stringMatcher('-');
		$this->assertTrue($matcher(''),'минус - только пустое значение');
		$this->assertFalse($matcher('x'));

		$matcher=QueryHelper::stringMatcher('*');
		$this->assertTrue($matcher('x'),'звездочка - любое непустое');
		$this->assertFalse($matcher(''));
	}

	/**
	 * Якоря ^/$ и подстановки % и _ внутри токена (передаются в LIKE как есть)
	 */
	public function testAnchorsAndLikeWildcards()
	{
		$matcher=QueryHelper::stringMatcher('^Debian');
		$this->assertTrue($matcher('Debian GNU/Linux'));
		$this->assertFalse($matcher('MX Debian remix'));

		$matcher=QueryHelper::stringMatcher('Linux$');
		$this->assertTrue($matcher('Debian GNU/Linux'));
		$this->assertFalse($matcher('Linux Mint'));

		$matcher=QueryHelper::stringMatcher('micro%office');
		$this->assertTrue($matcher('Microsoft Office'),'% как в LIKE - любой хвост');
		$this->assertFalse($matcher('Office Microsoft'));
	}

	/**
	 * Экранированные служебные символы ищутся как литералы
	 */
	public function testEscapedSymbols()
	{
		$matcher=QueryHelper::stringMatcher('AT\&T');
		$this->assertTrue($matcher('AT&T Global Network Client'));
		$this->assertFalse($matcher('ATT Client'));
	}

	/**
	 * Детект позитивных токенов: чисто негативный фильтр бесполезен для подсветки
	 */
	public function testHasPositiveTokens()
	{
		$this->assertTrue(QueryHelper::hasPositiveTokens('chrome'));
		$this->assertTrue(QueryHelper::hasPositiveTokens('siemens & !nx'));
		$this->assertTrue(QueryHelper::hasPositiveTokens('!a | b'));
		$this->assertFalse(QueryHelper::hasPositiveTokens('!chrome'));
		$this->assertFalse(QueryHelper::hasPositiveTokens('!a & !b'));
		$this->assertFalse(QueryHelper::hasPositiveTokens('-'));
		$this->assertFalse(QueryHelper::hasPositiveTokens('*'));
		$this->assertFalse(QueryHelper::hasPositiveTokens(''));
		$this->assertTrue(QueryHelper::hasPositiveTokens('\!важно'),'экранированный ! - литерал, токен позитивный');
	}
}
