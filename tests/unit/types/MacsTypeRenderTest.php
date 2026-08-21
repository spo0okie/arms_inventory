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
	 * Диапазон адресов (issue #120) иконки не получает: и в списках, и на
	 * портах ищется конкретный адрес
	 */
	public function testRangeHasNoIcon()
	{
		$html = $this->render(new Comps(['mac' => '001122334400-0011223344ff']));

		$this->assertStringNotContainsString('mac-search-icon', $html);
		$this->assertNotEmpty(trim(strip_tags($html)));
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
