<?php

namespace tests\unit\components;

use app\components\gridColumns\DefaultColumn;
use app\models\CompsHistory;
use Codeception\Test\Unit;
use kartik\grid\GridView;
use yii\data\ArrayDataProvider;

/**
 * Регрессионный тест DefaultColumn: CSS-класс из contentOptions должен доезжать
 * до ячейки. Колонка рендерит значение через ModelFieldWidget и пропускает в него
 * только собственные свойства виджета - 'class' отсеивался фильтром ДО того, как
 * вклеивался в cardClass, из-за чего табличный журнал истории терял подсветку
 * изменённых ячеек table-warning (issue #194).
 */
class DefaultColumnContentOptionsTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	private function renderCell($contentOptions): string
	{
		$record = new CompsHistory(['name' => 'test-host']);
		$grid = new GridView([
			'dataProvider' => new ArrayDataProvider(['allModels' => [$record]]),
			'columns' => [],
		]);
		$column = new DefaultColumn([
			'grid' => $grid,
			'attribute' => 'name',
			'format' => 'raw',
			'contentOptions' => $contentOptions,
		]);
		return $column->renderDataCell($record, 1, 0);
	}

	/**
	 * Класс из contentOptions-замыкания (подсветка изменённых ячеек журнала)
	 * попадает в класс ячейки, значение при этом рендерится
	 */
	public function testContentOptionsClassReachesCell()
	{
		$html = $this->renderCell(static fn() => ['class' => 'table-warning']);
		$this->assertStringContainsString('table-warning', $html);
		$this->assertStringContainsString('test-host', $html);
	}

	/**
	 * Без класса в contentOptions ячейка рендерится штатно
	 */
	public function testNoClassRendersFine()
	{
		$html = $this->renderCell(static fn() => []);
		$this->assertStringNotContainsString('table-warning', $html);
		$this->assertStringContainsString('test-host', $html);
	}
}
