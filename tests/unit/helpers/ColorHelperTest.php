<?php

namespace tests\unit\helpers;

use app\helpers\ColorHelper;
use Codeception\Test\Unit;

/**
 * Тесты хелпера работы с цветом
 */
class ColorHelperTest extends Unit
{
	public function testIsValidHex()
	{
		$this->assertTrue(ColorHelper::isValidHex('#FF5733'));
		$this->assertTrue(ColorHelper::isValidHex('#fff'));
		$this->assertTrue(ColorHelper::isValidHex('#000000'));

		$this->assertFalse(ColorHelper::isValidHex(null));
		$this->assertFalse(ColorHelper::isValidHex(''));
		$this->assertFalse(ColorHelper::isValidHex('FF5733'));		//без решетки
		$this->assertFalse(ColorHelper::isValidHex('#FF573'));		//5 символов
		$this->assertFalse(ColorHelper::isValidHex('#GGGGGG'));	//не hex
		$this->assertFalse(ColorHelper::isValidHex('red'));		//именованный цвет
	}

	public function testContrastColor()
	{
		//светлые фоны -> черный текст
		$this->assertEquals('#000000', ColorHelper::contrastColor('#FFFFFF'));
		$this->assertEquals('#000000', ColorHelper::contrastColor('#FFFF00'));	//желтый
		$this->assertEquals('#000000', ColorHelper::contrastColor('#90EE90'));	//lightgreen
		$this->assertEquals('#000000', ColorHelper::contrastColor('#DEB887'));	//burlywood

		//темные фоны -> белый текст
		$this->assertEquals('#ffffff', ColorHelper::contrastColor('#000000'));
		$this->assertEquals('#ffffff', ColorHelper::contrastColor('#8B008B'));	//darkmagenta
		$this->assertEquals('#ffffff', ColorHelper::contrastColor('#FF0000'));	//red (luminance 0.299)

		//средние тона -> белый (перцептивный порог 0.6, а не 0.5 - см. contrastColor)
		$this->assertEquals('#ffffff', ColorHelper::contrastColor('#808080'));	//gray (luminance 0.502)
		$this->assertEquals('#ffffff', ColorHelper::contrastColor('#5CB85C'));	//зеленый (0.573)
		$this->assertEquals('#ffffff', ColorHelper::contrastColor('#1DA7EE'));	//ярко-синий (0.525)

		//3-символьная запись расширяется корректно
		$this->assertEquals('#000000', ColorHelper::contrastColor('#fff'));
		$this->assertEquals('#ffffff', ColorHelper::contrastColor('#000'));
	}
}
