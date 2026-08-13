<?php

namespace tests\unit\components;

use app\components\PronounceablePasswordGenerator;
use Codeception\Test\Unit;

/**
 * Генератор произносимых паролей: соответствие парольной политике
 * (длина, заглавная, строчная, цифра, спецсимвол) и «произносимость»
 * (собран из фонетических слогов, а не случайного набора).
 */
class PronounceablePasswordGeneratorTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** Длина ровно запрошенная — на разных значениях, много прогонов */
	public function testLength()
	{
		foreach ([12, 14, 16, 20, 32] as $length) {
			for ($i = 0; $i < 10; $i++) {
				$password = (new PronounceablePasswordGenerator($length))->generate();
				$this->assertSame($length, strlen($password), "длина $length");
			}
		}
	}

	/**
	 * Соответствие политике: есть заглавная, строчная, цифра, спецсимвол.
	 * Прогоняем много раз — генерация вероятностная.
	 */
	public function testPolicyCompliance()
	{
		for ($i = 0; $i < 200; $i++) {
			$password = (new PronounceablePasswordGenerator(12))->generate();
			$this->assertMatchesRegularExpression('/[A-Z]/', $password, "заглавная в '$password'");
			$this->assertMatchesRegularExpression('/[a-z]/', $password, "строчная в '$password'");
			$this->assertMatchesRegularExpression('/[0-9]/', $password, "цифра в '$password'");
			$this->assertMatchesRegularExpression('/['.preg_quote(PronounceablePasswordGenerator::SPECIALS, '/').']/',
				$password, "спецсимвол в '$password'");
		}
	}

	/**
	 * «Произносимость»: если убрать одну заглавную, одну цифру и один
	 * спецсимвол, остаётся строка из известных фонетических слогов —
	 * значит пароль читается по слогам, а не случайный
	 */
	public function testPronounceable()
	{
		//все слоги генератора (нижним регистром) + одиночные буквы
		$syllables = 'a|ae|ah|ai|b|c|ch|d|e|ee|ei|f|g|gh|h|i|ie|j|k|l|m|n|ng|o|oh|oo|p|ph|qu|r|s|sh|t|th|u|v|w|x|y|z';

		for ($i = 0; $i < 50; $i++) {
			$password = (new PronounceablePasswordGenerator(12))->generate();

			//убираем ровно по одному «обязательному» инородному символу:
			//цифру, спецсимвол; заглавные опускаем в нижний регистр
			$letters = strtolower(preg_replace(
				['/[0-9]/', '/['.preg_quote(PronounceablePasswordGenerator::SPECIALS, '/').']/'],
				['', ''],
				$password
			));

			//оставшееся должно полностью разбираться на слоги
			$this->assertMatchesRegularExpression(
				'/^(?:'.$syllables.')+$/',
				$letters,
				"остаток '$letters' из '$password' должен собираться из слогов"
			);
		}
	}

	/** Разные вызовы дают разные пароли (CSPRNG-источник) */
	public function testUnique()
	{
		$seen = [];
		for ($i = 0; $i < 50; $i++) {
			$seen[(new PronounceablePasswordGenerator(12))->generate()] = true;
		}
		$this->assertGreaterThan(45, count($seen), 'пароли должны различаться');
	}
}
