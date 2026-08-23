<?php

namespace tests\unit\components;

use app\components\PortsMapWidget;
use app\helpers\PortsHelper;
use app\models\Manufacturers;
use app\models\TechModels;
use app\models\Techs;
use Codeception\Test\Unit;
use Yii;

/**
 * Геометрия корпуса и карта портов (plans/network-map.md, этап 3.0.1).
 *
 * Порядок портов отвечает на вопрос «какой порт следующий», а геометрия — на
 * вопрос «где он физически»: тринадцатый порт может быть и тринадцатой
 * розеткой, и четырнадцатой, смотря как идёт нумерация по рядам.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class PortsLayoutTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) $this->transaction->rollBack();
	}

	/** Имена портов Gi1/0/1..N */
	private function names(int $count): array
	{
		$names = [];
		for ($number = 1; $number <= $count; $number++) $names[] = 'Gi1/0/'.$number;
		return $names;
	}

	/** Разбор объявления: размеры блоков, направление, подписи, мусор */
	public function testParseLayout()
	{
		$blocks = PortsHelper::parseLayout("# передняя панель\n12x2 вниз Основные\n4x1 SFP\n\nерунда\n8");

		$this->assertCount(3, $blocks);
		//12x2 - сетка 12 на 2: 24 порта. Так читает человек, так и разбираем
		$this->assertSame(['count' => 24, 'rows' => 2, 'dir' => PortsHelper::DIR_DOWN,
			'title' => 'Основные'], $blocks[0]);
		$this->assertSame('SFP', $blocks[1]['title']);
		//без указания рядов - один ряд; направление по умолчанию «вниз»
		$this->assertSame(1, $blocks[2]['rows']);
		$this->assertSame(PortsHelper::DIR_DOWN, $blocks[2]['dir']);
	}

	/**
	 * «Вниз» — как считает большинство коммутаторов: 1 сверху слева, 2 под ним.
	 * Именно из-за этого по номеру порта нельзя угадать розетку.
	 */
	public function testSlotsFillDown()
	{
		$slots = PortsHelper::layoutSlots(PortsHelper::parseLayout('4x2 вниз'), $this->names(8));

		$this->assertCount(1, $slots);
		$this->assertSame(['Gi1/0/1', 'Gi1/0/3', 'Gi1/0/5', 'Gi1/0/7'], $slots[0]['grid'][0]);
		$this->assertSame(['Gi1/0/2', 'Gi1/0/4', 'Gi1/0/6', 'Gi1/0/8'], $slots[0]['grid'][1]);
	}

	/** «Вправо» — сначала весь верхний ряд */
	public function testSlotsFillRight()
	{
		$slots = PortsHelper::layoutSlots(PortsHelper::parseLayout('4x2 вправо'), $this->names(8));

		$this->assertSame(['Gi1/0/1', 'Gi1/0/2', 'Gi1/0/3', 'Gi1/0/4'], $slots[0]['grid'][0]);
		$this->assertSame(['Gi1/0/5', 'Gi1/0/6', 'Gi1/0/7', 'Gi1/0/8'], $slots[0]['grid'][1]);
	}

	/** Блоки съедают порты по очереди, лишние показываются отдельно */
	public function testBlocksConsumePortsInOrder()
	{
		$slots = PortsHelper::layoutSlots(
			PortsHelper::parseLayout("2x2 Основные\n2 SFP"), $this->names(8));

		$this->assertSame(['Основные', 'SFP', 'вне раскладки'], array_column($slots, 'title'));
		$this->assertSame(['Gi1/0/5', 'Gi1/0/6'], $slots[1]['grid'][0]);
		//геометрия описывает 6 портов, а объявлено 8: расхождение не прячем
		$this->assertSame(['Gi1/0/7', 'Gi1/0/8'], $slots[2]['grid'][0]);
	}

	/** Подпись слота: в квадратик влезает номер, а не полное имя */
	public function testSlotLabel()
	{
		$this->assertSame('13', PortsHelper::slotLabel('GigabitEthernet1/0/13'));
		$this->assertSame('2', PortsHelper::slotLabel('sfp-sfpplus2'));
		$this->assertSame('upl', PortsHelper::slotLabel('uplink'));
	}

	/** Карта рисуется по геометрии модели и именам экземпляра */
	public function testWidgetRendersMap()
	{
		$manufacturer = new Manufacturers();
		$manufacturer->setAttributes(['name' => 'Вендор '.uniqid(), 'comment' => ''], false);
		$this->assertTrue($manufacturer->save(false));

		$model = new TechModels();
		$model->setAttributes(['name' => 'Модель '.uniqid(), 'manufacturers_id' => $manufacturer->id,
			'ports' => implode("\n", $this->names(8)), 'ports_layout' => '4x2 вниз Основные',
			'comment' => ''], false);
		$this->assertTrue($model->save(false));

		$tech = new Techs();
		$tech->setAttributes(['model_id' => $model->id, 'num' => 'SW-'.uniqid(),
			'history' => ''], false);
		$this->assertTrue($tech->save(false));
		$tech->refresh();

		$html = PortsMapWidget::widget(['model' => $tech, 'ports' => [
			['port' => 'Gi1/0/1', 'verdict' => 'free', 'linked' => null, 'found' => [],
				'description' => ''],
			['port' => 'Gi1/0/2', 'verdict' => 'disabled', 'linked' => null, 'found' => [],
				'description' => 'reserved'],
		]]);

		$this->assertStringContainsString('ports-map', $html);
		$this->assertStringContainsString('Основные', $html);
		//состояние порта видно прямо на карте, а подробности - в подсказке
		$this->assertStringContainsString('выключен на коммутаторе', $html);
		$this->assertStringContainsString('reserved', $html);

		//у устройства без описанного корпуса карты нет - и это не ошибка
		$model->ports_layout = '';
		$this->assertTrue($model->save(false));
		$tech->refresh();
		$this->assertSame('', PortsMapWidget::widget(['model' => $tech]));
	}
}
