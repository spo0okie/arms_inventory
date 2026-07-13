<?php

namespace tests\unit\models;

use app\models\Markers;
use Codeception\Test\Unit;

/**
 * Тесты модели цветовых маркеров (issue #141):
 * автоконтраст текста и сборка инлайн CSS-переменных (styleVars).
 */
class MarkersTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	protected function _before()
	{
		Markers::deleteAll();
	}

	public function testCreateMarker()
	{
		$marker = new Markers([
			'name' => 'Тестовый маркер',
			'color' => '#5CB85C',
		]);
		$this->assertTrue($marker->save(), 'Маркер с именем и фоном должен сохраняться');
		$this->assertNotEmpty($marker->id);
	}

	public function testColorRequired()
	{
		$marker = new Markers(['name' => 'Без цвета']);
		$this->assertFalse($marker->save(), 'Фон - обязательный канал маркера');
		$this->assertArrayHasKey('color', $marker->errors);
	}

	public function testColorFormatValidation()
	{
		$marker = new Markers(['name' => 'Кривой цвет', 'color' => 'red']);
		$this->assertFalse($marker->save());
		$this->assertArrayHasKey('color', $marker->errors);
	}

	public function testBorderStyleValidation()
	{
		$marker = new Markers([
			'name' => 'Кривая рамка',
			'color' => '#5CB85C',
			'border_style' => 'dotted', //не из списка solid/dashed
		]);
		$this->assertFalse($marker->save());
		$this->assertArrayHasKey('border_style', $marker->errors);
	}

	public function testTextColorAutoContrast()
	{
		//светлый фон -> черный текст
		$marker = new Markers(['color' => '#FFFF00']);
		$this->assertEquals('#000000', $marker->textColor);

		//темный фон -> белый текст
		$marker = new Markers(['color' => '#8B008B']);
		$this->assertEquals('#ffffff', $marker->textColor);
	}

	public function testTextColorOverride()
	{
		//явное переопределение важнее автоконтраста (кейс guest_dmz)
		$marker = new Markers(['color' => '#3C3C3C', 'text_color' => '#FFA500']);
		$this->assertEquals('#FFA500', $marker->textColor);
	}

	public function testStyleVarsBackgroundOnly()
	{
		//зеленый - средний тон: автоконтраст дает белый (перцептивный порог 0.6)
		$marker = new Markers(['color' => '#5CB85C']);
		$this->assertEquals(
			'--marker-bg:#5CB85C;--marker-fg:#ffffff',
			$marker->styleVars
		);
	}

	public function testStyleVarsWithBorder()
	{
		$marker = new Markers([
			'color' => '#FFFF00',
			'border_color' => '#FF0000',
			'border_style' => 'dashed',
		]);
		$this->assertEquals(
			'--marker-bg:#FFFF00;--marker-fg:#000000;--marker-border:1.2pt dashed #FF0000',
			$marker->styleVars
		);
	}

	public function testStyleVarsBorderStyleDefaultsToSolid()
	{
		//стиль рамки без цвета рамки не рендерится, а рамка без стиля - сплошная
		$marker = new Markers(['color' => '#FFFF00', 'border_color' => '#FF0000']);
		$this->assertStringContainsString('1.2pt solid #FF0000', $marker->styleVars);

		$marker = new Markers(['color' => '#FFFF00', 'border_style' => 'dashed']);
		$this->assertStringNotContainsString('--marker-border', $marker->styleVars);
	}

	public function testStyleVarsEmptyOnInvalidColor()
	{
		//без валидного фона маркер не рендерится (нет мусора в style)
		$marker = new Markers(['name' => 'Пустой']);
		$this->assertSame('', $marker->styleVars);
	}
}
