<?php

namespace tests\unit\models;

use app\helpers\PortsHelper;
use app\models\Manufacturers;
use app\models\Ports;
use app\models\TechModels;
use app\models\Techs;
use app\models\TechTypes;
use Codeception\Test\Unit;
use Yii;

/**
 * Объявление портов устройства (plans/network-map.md, этап 3.0).
 *
 * Порядок портов — единственный ответ на вопрос «где физически находится
 * порт Gi1/0/13»: строки `ports` ленивые и порядка не несут. Обычно порядок
 * объявляет модель оборудования, но у экземпляра имена расходятся с
 * модельными (стек, переименование на MikroTik) — тогда действует
 * `ports_override`, и ТОЛЬКО он: модельные имена в этом случае фантомы.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class TechsPortsTemplateTest extends Unit
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

	/** Оборудование с моделью, у которой объявлены порты */
	private function makeTech(string $modelPorts, ?string $override = null): Techs
	{
		$manufacturer = new Manufacturers();
		$manufacturer->setAttributes(['name' => 'Вендор '.uniqid(), 'comment' => ''], false);
		$this->assertTrue($manufacturer->save(false));

		$type = TechTypes::findOne(['code' => 'net_switch']);
		$model = new TechModels();
		$model->setAttributes([
			'name' => 'Модель '.uniqid(),
			'type_id' => is_object($type) ? $type->id : null,
			'manufacturers_id' => $manufacturer->id,
			'ports' => $modelPorts,
			'comment' => '',
		], false);
		$this->assertTrue($model->save(false));

		$tech = new Techs();
		$tech->setAttributes([
			'model_id' => $model->id,
			'num' => 'SW-'.uniqid(),
			'ports_override' => $override,
			'history' => '',
		], false);
		$this->assertTrue($tech->save(false));
		$tech->refresh();
		return $tech;
	}

	/**
	 * Переобъявили порты - заведённые записи переезжают за своими позициями.
	 *
	 * Порт в инвентаризации это розетка, а не строка текста: за именем стоит
	 * кабель, который никуда не делся. Второй порт остаётся вторым, как бы
	 * устройство его теперь ни называло.
	 */
	public function testRenameKeepsPositionsAndLinks()
	{
		$switch = $this->makeTech("Ge0/1\nGe0/2\nGe0/3");
		$server = $this->makeTech("eth0");

		//во второй порт воткнут сервер - именно эта связь и не должна пострадать
		$port = Ports::forTech($switch, 'Ge0/2');
		$port->link_techs_id = $server->id;
		$port->link_ports_id = 'create:eth0';
		$this->assertTrue($port->save(), implode('; ', $port->firstErrors));

		//без явного выбора ничего не переименовывается: список могли сдвинуть
		$switch->ports_override = "CON\nGi1/0/1\nGi1/0/2\nGi1/0/3";
		$this->assertTrue($switch->save(false));
		$port->refresh();
		$this->assertSame('Ge0/2', $port->name, 'сдвиг списка связи не трогает');
		$switch->ports_override = '';
		$this->assertTrue($switch->save(false));

		$switch->ports_override = "Gi1/0/1\nGi1/0/2\nGi1/0/3";
		$switch->rename_ports = true;
		$this->assertTrue($switch->save(false));

		$port->refresh();
		$this->assertSame('Gi1/0/2', $port->name, 'второй порт остался вторым');
		$this->assertNotEmpty($port->link_ports_id, 'связь пережила переименование');
		$this->assertSame($server->id, $port->linkPort->techs_id);

		//и список портов снова цельный: запись не выпала в хвост
		$this->assertSame(['Gi1/0/1', 'Gi1/0/2', 'Gi1/0/3'], array_keys($switch->portsList));
	}

	/**
	 * Сдвиг имён не спотыкается об уникальность.
	 *
	 * Gi1/0/1 -> Gi1/0/2 при живом Gi1/0/2 - обычное дело при перенумерации
	 * стека, и переименование идёт в два прохода именно поэтому.
	 */
	public function testRenameHandlesNameCollisions()
	{
		$switch = $this->makeTech("p1\np2");

		$first = Ports::forTech($switch, 'p1');
		$first->comment = 'первый';
		$this->assertTrue($first->save());
		$second = Ports::forTech($switch, 'p2');
		$second->comment = 'второй';
		$this->assertTrue($second->save());

		//имена сдвигаются на позицию: p1 становится p2, p2 - p3
		$switch->ports_override = "p2\np3";
		$switch->rename_ports = true;
		$this->assertTrue($switch->save(false));

		$first->refresh();
		$second->refresh();
		$this->assertSame('p2', $first->name);
		$this->assertSame('p3', $second->name);
		$this->assertSame('первый', $first->comment);
	}

	/**
	 * Переименование в шаблоне МОДЕЛИ тянет порты всех её экземпляров - по
	 * явному выбору; экземпляр со своим списком не трогается
	 */
	public function testModelRenameFollowsInstances()
	{
		$switch = $this->makeTech("1\n2\n3");
		$own = new Techs();
		$own->setAttributes(['model_id' => $switch->model_id, 'num' => 'SW2-'.uniqid(),
			'ports_override' => "a\nb\nc", 'history' => ''], false);
		$this->assertTrue($own->save(false));

		$port = Ports::forTech($switch, '2');
		$port->comment = 'второй';
		$this->assertTrue($port->save());
		$ownPort = Ports::forTech($own, 'b');
		$ownPort->comment = 'свой';
		$this->assertTrue($ownPort->save());

		$model = $switch->model;
		$model->ports = "Gi0/1\nGi0/2\nGi0/3";
		$this->assertTrue($model->save(false));
		$port->refresh();
		$this->assertSame('2', $port->name, 'без выбора - ничего');
		$model->ports = "1\n2\n3";
		$this->assertTrue($model->save(false));

		$model->rename_ports = true;
		$model->ports = "Te0/1\nTe0/2\nTe0/3";
		$this->assertTrue($model->save(false));
		$port->refresh();
		$ownPort->refresh();
		$this->assertSame('Te0/2', $port->name);
		$this->assertSame('b', $ownPort->name, 'экземпляр со своим списком живёт по своему списку');
	}

	/** Укоротившееся объявление хвост не трогает: порта просто больше нет */
	public function testRenameKeepsTailUntouched()
	{
		$switch = $this->makeTech("a1\na2\na3");

		$tail = Ports::forTech($switch, 'a3');
		$tail->comment = 'третий';
		$this->assertTrue($tail->save());

		$switch->ports_override = "b1\nb2";
		$switch->rename_ports = true;
		$this->assertTrue($switch->save(false));

		$tail->refresh();
		$this->assertSame('a3', $tail->name, 'выпавший из объявления порт переименовывать не за чем');
	}

	/** Разбор объявления: порядок строк, комментарий после имени, дубли */
	public function testParseList()
	{
		$ports = PortsHelper::parseList("Gi1/0/2\nGi1/0/1 сгорел\n\nGi1/0/1 второй раз\n  \n");

		//порядок объявления сохраняется: он и есть порядок портов на корпусе
		$this->assertSame(['Gi1/0/2', 'Gi1/0/1'], array_keys($ports));
		//первое слово - имя, остальное - комментарий к порту
		$this->assertSame('сгорел', $ports['Gi1/0/1']);
		$this->assertSame('', $ports['Gi1/0/2']);
	}

	/** Пусто в оверрайде - действуют порты модели */
	public function testModelTemplateByDefault()
	{
		$tech = $this->makeTech("Gi0/1\nGi0/2 в патч-панель");

		$this->assertSame(['Gi0/1', 'Gi0/2'], array_keys($tech->portsTemplate));
		$this->assertSame('в патч-панель', $tech->portsTemplate['Gi0/2']);
	}

	/**
	 * Оверрайд действует ЦЕЛИКОМ: после стекирования модельные Gi0/x —
	 * фантомы, рисовать их рядом с настоящими нельзя
	 */
	public function testOverrideReplacesModelTemplate()
	{
		$tech = $this->makeTech("Gi0/1\nGi0/2", "Gi2/0/1\nGi2/0/2 сгорел");

		$this->assertSame(['Gi2/0/1', 'Gi2/0/2'], array_keys($tech->portsTemplate));
		$this->assertSame('сгорел', $tech->portsTemplate['Gi2/0/2']);

		//portsList (порты карточки) следует за объявлением
		$this->assertSame(['Gi2/0/1', 'Gi2/0/2'], array_keys($tech->portsList));
	}

	/** Пробелы оверрайда не считаются объявлением */
	public function testBlankOverrideIsIgnored()
	{
		$tech = $this->makeTech("Gi0/1", "  \n\n");
		$this->assertSame(['Gi0/1'], array_keys($tech->portsTemplate));
	}
}
