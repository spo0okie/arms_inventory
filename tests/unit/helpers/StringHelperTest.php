<?php

namespace tests\unit\helpers;

use app\helpers\StringHelper;
use Codeception\Test\Unit;

/**
 * Тесты StringHelper (пока только транслитерация - остальные методы
 * покрыты косвенно через использующий их код)
 */
class StringHelperTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Транслит в нижний регистр латиницы: многобуквенные ж/ч/ш/щ/ю/я,
	 * отбрасывание ъ/ь, латиница и не-буквы не трогаются (кроме регистра)
	 */
	public function testTranslit()
	{
		$this->assertSame('ivanov', StringHelper::translit('Иванов'));
		$this->assertSame('schukin', StringHelper::translit('Щукин'));
		$this->assertSame('yurii zhdanov', StringHelper::translit('Юрий Жданов'), 'й -> i');
		$this->assertSame('podyachii', StringHelper::translit('Подъячий'), 'ъ отбрасывается');
		$this->assertSame('igor', StringHelper::translit('Игорь'), 'ь отбрасывается');
		//латиница/цифры/знаки сохраняются, лишь приводится регистр
		$this->assertSame('abc-123.x', StringHelper::translit('Abc-123.X'));
		$this->assertSame('', StringHelper::translit(''));
	}
}
