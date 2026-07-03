<?php

namespace tests\unit\components;

use app\components\RackWidget;
use Codeception\Test\Unit;

/**
 * Тесты подгона шрифта под длину текста в слоте корзины (issue #154).
 *
 * RackWidget::fitFontSize() используется в td-unit.php: для каждого слота отдельно
 * подбирается кегль так, чтобы текст (имя оборудования) влезал по ширине слота —
 * чем длиннее текст, тем мельче шрифт.
 */
class RackWidgetFitFontTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Ключевое требование: в одном и том же слоте более длинный текст => мельче шрифт.
	 */
	public function testLongerTextGetsSmallerFont()
	{
		$short = RackWidget::fitFontSize(60, 100, 3);
		$long  = RackWidget::fitFontSize(60, 100, 12);
		$this->assertLessThan($short, $long, 'Длинный текст должен получить меньший шрифт');
	}

	/**
	 * Монотонность: чем больше символов, тем не больше шрифт.
	 */
	public function testMonotonicNonIncreasing()
	{
		$prev = INF;
		foreach ([1, 2, 4, 8, 16, 32] as $len) {
			$font = RackWidget::fitFontSize(80, 40, $len);
			$this->assertLessThanOrEqual($prev, $font, "len=$len не должен увеличивать шрифт");
			$prev = $font;
		}
	}

	/**
	 * Ограничение по ширине слота: font = ширина / (символы * 0.6).
	 */
	public function testWidthConstraint()
	{
		// 60 / (10*0.6) = 10, при большой высоте потолок 16 не мешает
		$this->assertEqualsWithDelta(10, RackWidget::fitFontSize(60, 100, 10), 0.001);
	}

	/**
	 * Ограничение по высоте слота (0.8 высоты), когда текст короткий/влезает.
	 */
	public function testHeightConstraint()
	{
		// высота 10 => 0.8*10 = 8; по ширине не ограничивает
		$this->assertEqualsWithDelta(8, RackWidget::fitFontSize(1000, 10, 1), 0.001);
	}

	/**
	 * Короткий текст в большом слоте => потолок max (16 по умолчанию).
	 */
	public function testCapAtMax()
	{
		$this->assertEqualsWithDelta(16, RackWidget::fitFontSize(1000, 1000, 1), 0.001);
	}

	/**
	 * Очень длинный текст в узком слоте => не мельче min (4 по умолчанию).
	 */
	public function testClampAtMin()
	{
		$this->assertEqualsWithDelta(4, RackWidget::fitFontSize(6, 1000, 100), 0.001);
	}

	/**
	 * Нет текста (0 символов) => ширина не ограничивает, только высота/потолок.
	 */
	public function testNoTextUsesHeightOnly()
	{
		$this->assertEqualsWithDelta(8, RackWidget::fitFontSize(5, 10, 0), 0.001);
	}
}
