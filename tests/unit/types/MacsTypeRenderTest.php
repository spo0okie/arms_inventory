<?php

namespace tests\unit\types;

use app\components\ModelFieldWidget;
use app\models\Comps;
use app\models\Techs;
use app\types\MacsType;
use Codeception\Test\Unit;
use yii\web\View;

/**
 * Вывод MAC-адресов (issue #218): рядом с адресом — иконка поиска с
 * тултип-меню (оборудование / ОС / порты коммутаторов), и ОС с
 * оборудованием показывают адреса одинаково, потому что рендер живёт на
 * типе атрибута, а не в карточках.
 */
class MacsTypeRenderTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	private function render($model): string
	{
		return (string)ModelFieldWidget::renderFieldValue($model, 'mac');
	}

	/** Одиночный адрес: сам адрес и иконка поиска рядом */
	public function testSingleMacGetsSearchIcon()
	{
		$html = $this->render(new Comps(['mac' => '001122334455']));

		$this->assertStringContainsString('00:11:22:33:44:55', $html);
		$this->assertStringContainsString('mac_address', $html);
		$this->assertStringContainsString('mac-search-icon', $html);
	}

	/** В меню — обычные списки с фильтром по этому адресу */
	public function testMenuLeadsToStandardLists()
	{
		$html = $this->render(new Comps(['mac' => '001122334455']));

		//ссылки лежат в атрибуте тултипа, поэтому кавычки экранированы
		$this->assertStringContainsString('TechsSearch', $html);
		$this->assertStringContainsString('CompsSearch', $html);
		$this->assertStringContainsString('001122334455', $html);
		//опрос коммутаторов появляется только при включённой интеграции
		$this->assertStringNotContainsString('integrations/panel', $html);
	}

	/** ОС и оборудование выводят адреса одинаково — это и есть унификация */
	public function testCompsAndTechsRenderSame()
	{
		$this->assertSame(
			$this->render(new Comps(['mac' => '001122334455'])),
			$this->render(new Techs(['mac' => '001122334455']))
		);
	}

	/** Несколько адресов — каждый своей строкой и со своей иконкой */
	public function testSeveralMacs()
	{
		$html = $this->render(new Techs(['mac' => "001122334455\n001122334466"]));

		$this->assertSame(2, substr_count($html, 'mac-search-icon'));
		$this->assertStringContainsString('<br />', $html);
		$this->assertStringContainsString('001122334466', $html);
	}

	/**
	 * Диапазон адресов (issue #120) показывается двумя границами, а не одной
	 * склейкой из двенадцати октетов, и у каждой границы своя иконка: искать
	 * умеем конкретный адрес, а границы — как раз конкретные адреса
	 */
	public function testRangeShowsBothBounds()
	{
		$html = $this->render(new Comps(['mac' => '001122334400-0011223344ff']));

		$this->assertStringContainsString('00:11:22:33:44:00', $html);
		$this->assertStringContainsString('00:11:22:33:44:FF', $html);
		//именно две границы, а не «адрес» 00:11:22:33:44:00:00:11:22:33:44:FF
		$this->assertStringNotContainsString('00:11:22:33:44:00:00', $html);
		$this->assertSame(2, substr_count($html, 'mac-search-icon'));
	}

	/** Список без иконок диапазон тоже разделяет на границы */
	public function testRangeStaticView()
	{
		$html = (new MacsType())->renderOutput(new View(),
			new Comps(['mac' => '001122334400-0011223344ff']), 'mac', ['search' => false]);

		$this->assertSame('<span class="mac_address">00:11:22:33:44:00</span>'
			.' - <span class="mac_address">00:11:22:33:44:FF</span>', $html);
	}

	/** Тот же диапазон в списковом форматировании (колонки, печать, тултипы) */
	public function testRangeFormattedForLists()
	{
		$this->assertSame('00:11:22:33:44:00 - 00:11:22:33:44:FF',
			Techs::formatMacs('001122334400-0011223344ff'));
	}

	/** Пустое значение — пустой вывод */
	public function testEmpty()
	{
		$this->assertSame('', $this->render(new Comps(['mac' => ''])));
	}

	/** Режим без иконок — для списков, печати и тултипов */
	public function testIconsCanBeDisabled()
	{
		$html = (new MacsType())->renderOutput(new View(), new Comps(['mac' => '001122334455']),
			'mac', ['search' => false]);

		$this->assertStringNotContainsString('mac-search-icon', $html);
		$this->assertStringContainsString('00:11:22:33:44:55', $html);
	}
}
