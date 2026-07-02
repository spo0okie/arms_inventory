<?php

namespace tests\unit\models;

use app\models\Comps;
use app\models\Sandboxes;
use Codeception\Test\Unit;

/**
 * Тесты отображения суффикса песочницы в имени ОС (issue #207).
 *
 * renderName() — единая точка формирования отображаемого имени ОС, которая
 * используется в списках/ссылках (views/comps/item.php) и та же sandbox->suffix
 * выводится в заголовке карточки (views/comps/card.php). ОС, клонированная в
 * песочницу, должна показываться с суффиксом песочницы, чтобы отличать её от
 * продуктивной ВМ.
 *
 * Модели строятся в памяти (populateRelation) — БД не требуется.
 */
class CompsRenderNameTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	private function makeComp(?string $suffix): Comps
	{
		$comp = new Comps();
		$comp->name = 'testhost';
		if ($suffix === null) {
			$comp->populateRelation('sandbox', null);
		} else {
			$sandbox = new Sandboxes();
			$sandbox->suffix = $suffix;
			$comp->populateRelation('sandbox', $sandbox);
		}
		return $comp;
	}

	/**
	 * У ОС в песочнице имя дополняется суффиксом песочницы.
	 */
	public function testRenderNameAppendsSandboxSuffix()
	{
		$name = $this->makeComp('-sbx')->renderName();

		$this->assertStringEndsWith('-sbx', $name, 'Суффикс песочницы должен выводиться после имени ОС');
		$this->assertStringContainsString('TESTHOST', $name);
	}

	/**
	 * У продуктивной ОС (без песочницы) суффикса нет.
	 */
	public function testRenderNameWithoutSandbox()
	{
		$this->assertEquals('TESTHOST', $this->makeComp(null)->renderName());
	}

	/**
	 * Пустой суффикс не добавляет ничего лишнего.
	 */
	public function testRenderNameEmptySuffix()
	{
		$this->assertEquals('TESTHOST', $this->makeComp('')->renderName());
	}
}
