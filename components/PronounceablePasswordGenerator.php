<?php

namespace app\components;

/**
 * Генератор «произносимых» паролей: слово собирается из фонетических
 * элементов (согласные/гласные/дифтонги), поэтому его проще продиктовать
 * и ввести, чем случайный набор символов. При этом пароль соответствует
 * доменной парольной политике: гарантированно содержит заглавную букву,
 * цифру и спецсимвол (плюс строчные из фонем), длина настраивается
 * (по умолчанию 12).
 *
 * Порт PowerShell-скрипта отдела (ad-usermanagement/lib_pwgen.ps1),
 * который до появления интеграции решал задачу «сбросить пароль так,
 * чтобы пользователь смог его ввести». Источник случайности — CSPRNG
 * (random_int).
 */
class PronounceablePasswordGenerator
{
	//флаги фонетических элементов
	const CONSONANT = 1;
	const VOWEL = 2;
	const DIPTHONG = 4;
	const NOT_FIRST = 8;

	//какие классы символов обязательно вставить
	const INCLUDE_NUMBER = 1;
	const INCLUDE_CAPITAL = 2;
	const INCLUDE_SPECIAL = 4;

	/** допустимые спецсимволы (без ' " \ ` и пробела — безопасны в вводе/URL) */
	const SPECIALS = '!@#$^*()-_+?=./:,';

	/** страховка от невозможной комбинации: максимум попыток собрать слово */
	const MAX_ATTEMPTS = 1000;

	/** @var int длина пароля */
	public int $length = 12;
	public bool $includeCapital = true;
	public bool $includeNumber = true;
	public bool $includeSpecial = true;

	/** @var array<array{0:string,1:int}> фонетические элементы */
	private array $elements;

	public function __construct(int $length = 12)
	{
		$this->length = $length;
		$this->elements = [
			['a', self::VOWEL],
			['ae', self::VOWEL | self::DIPTHONG],
			['ah', self::VOWEL | self::DIPTHONG],
			['ai', self::VOWEL | self::DIPTHONG],
			['b', self::CONSONANT],
			['c', self::CONSONANT],
			['ch', self::CONSONANT | self::DIPTHONG],
			['d', self::CONSONANT],
			['e', self::VOWEL],
			['ee', self::VOWEL | self::DIPTHONG],
			['ei', self::VOWEL | self::DIPTHONG],
			['f', self::CONSONANT],
			['g', self::CONSONANT],
			['gh', self::CONSONANT | self::DIPTHONG | self::NOT_FIRST],
			['h', self::CONSONANT],
			['i', self::VOWEL],
			['ie', self::VOWEL | self::DIPTHONG],
			['j', self::CONSONANT],
			['k', self::CONSONANT],
			['l', self::CONSONANT],
			['m', self::CONSONANT],
			['n', self::CONSONANT],
			['ng', self::CONSONANT | self::DIPTHONG | self::NOT_FIRST],
			['o', self::VOWEL],
			['oh', self::VOWEL | self::DIPTHONG],
			['oo', self::VOWEL | self::DIPTHONG],
			['p', self::CONSONANT],
			['ph', self::CONSONANT | self::DIPTHONG],
			['qu', self::CONSONANT | self::DIPTHONG],
			['r', self::CONSONANT],
			['s', self::CONSONANT],
			['sh', self::CONSONANT | self::DIPTHONG],
			['t', self::CONSONANT],
			['th', self::CONSONANT | self::DIPTHONG],
			['u', self::VOWEL],
			['v', self::CONSONANT],
			['w', self::CONSONANT],
			['x', self::CONSONANT],
			['y', self::CONSONANT],
			['z', self::CONSONANT],
		];
	}

	/**
	 * Сгенерировать пароль. Повторяет попытки, пока слово не соберётся с
	 * всеми обязательными классами символов.
	 * @throws \RuntimeException если за MAX_ATTEMPTS собрать не удалось
	 */
	public function generate(): string
	{
		for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
			$result = $this->generateOnce();
			if ($result !== null) return $result;
		}
		throw new \RuntimeException('Не удалось сгенерировать пароль заданной длины');
	}

	/** [0, $maxExclusive) через CSPRNG */
	private function rnd(int $maxExclusive): int
	{
		return random_int(0, $maxExclusive - 1);
	}

	/**
	 * Одна попытка сборки. Возвращает null, если не удалось разместить все
	 * обязательные классы символов (вызывающий повторит).
	 */
	private function generateOnce(): ?string
	{
		$result = '';
		$prev = 0;
		$isFirst = true;

		$requested = 0;
		if ($this->includeCapital) $requested |= self::INCLUDE_CAPITAL;
		if ($this->includeNumber) $requested |= self::INCLUDE_NUMBER;
		if ($this->includeSpecial) $requested |= self::INCLUDE_SPECIAL;

		$shouldBe = $this->rnd(2) ? self::VOWEL : self::CONSONANT;

		while (strlen($result) < $this->length) {
			[$str, $flags] = $this->elements[$this->rnd(count($this->elements))];

			//нужный тип (гласная/согласная)?
			if (($flags & $shouldBe) === 0) continue;
			//дифтонг вроде "gh"/"ng" не может быть первым
			if ($isFirst && ($flags & self::NOT_FIRST) !== 0) continue;
			//не даём двум гласным-дифтонгам слипаться
			if (($prev & self::VOWEL) && ($flags & self::VOWEL) && ($flags & self::DIPTHONG)) continue;
			//не вылезаем за длину
			if (strlen($result) + strlen($str) > $this->length) continue;

			//одна заглавная: на первом элементе или иногда на согласной
			if ($requested & self::INCLUDE_CAPITAL) {
				if ($isFirst || (($flags & self::CONSONANT) && $this->rnd(10) > 3)) {
					$str = ucfirst($str);
					$requested &= ~self::INCLUDE_CAPITAL;
				}
			}

			$result .= $str;

			//одна цифра: с некоторой вероятностью (не в начале)
			if ($requested & self::INCLUDE_NUMBER) {
				if (!$isFirst && $this->rnd(10) < 3) {
					if (strlen($result) + 1 > $this->length) $this->dropLast($result, $str, $requested);
					$result .= (string)$this->rnd(10);
					$requested &= ~self::INCLUDE_NUMBER;
					$isFirst = true;
					$prev = 0;
					$shouldBe = $this->rnd(2) ? self::VOWEL : self::CONSONANT;
					continue;
				}
			}

			//один спецсимвол: аналогично
			if ($requested & self::INCLUDE_SPECIAL) {
				if (!$isFirst && $this->rnd(10) < 3) {
					if (strlen($result) + 1 > $this->length) $this->dropLast($result, $str, $requested);
					$result .= self::SPECIALS[$this->rnd(strlen(self::SPECIALS))];
					$requested &= ~self::INCLUDE_SPECIAL;
					$isFirst = true;
					$prev = 0;
					$shouldBe = $this->rnd(2) ? self::VOWEL : self::CONSONANT;
					continue;
				}
			}

			//чередуем гласные/согласные
			if ($shouldBe === self::CONSONANT) {
				$shouldBe = self::VOWEL;
			} elseif (($prev & self::VOWEL) || ($flags & self::DIPTHONG) || $this->rnd(10) > 3) {
				$shouldBe = self::CONSONANT;
			} else {
				$shouldBe = self::VOWEL;
			}
			$prev = $flags;
			$isFirst = false;
		}

		//не все обязательные классы удалось разместить — попытка неудачна
		if ($requested & (self::INCLUDE_NUMBER | self::INCLUDE_SPECIAL | self::INCLUDE_CAPITAL)) {
			return null;
		}

		return $result;
	}
	/**
	 * Снять с конца последний добавленный слог целиком.
	 *
	 * Когда для обязательной цифры/спецсимвола не хватает длины, места надо
	 * освободить ровно на слог: обрубок «qu» -> «q» ломает произносимость,
	 * ради которой этот генератор и существует.
	 *
	 * @param string $result собранный пароль (меняется)
	 * @param string $last   последний добавленный слог
	 * @param int    $requested флаги обязательных символов (меняются)
	 */
	private function dropLast(string &$result, string $last, int &$requested): void
	{
		$result = substr($result, 0, -strlen($last));

		//заглавная могла уехать вместе со слогом - тогда её надо поставить снова
		if ($last !== lcfirst($last)) $requested |= self::INCLUDE_CAPITAL;
	}

}
