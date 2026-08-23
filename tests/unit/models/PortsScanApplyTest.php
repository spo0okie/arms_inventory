<?php

namespace tests\unit\models;

use app\models\Manufacturers;
use app\models\Ports;
use app\models\TechModels;
use app\models\Techs;
use Codeception\Test\Unit;
use Yii;

/**
 * Применение находок опроса к портам (plans/network-map.md, этап 3.4).
 *
 * Опрос показывает расхождение, а инженер одним кликом переносит увиденное в
 * инвентаризацию. Связь всегда идёт порт-в-порт: в `ports` есть только
 * `link_ports_id`, а «оборудование на той стороне» вычисляется через
 * встречный порт — поэтому проверяем именно парность связи.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class PortsScanApplyTest extends Unit
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

	private function makeTech(string $num, string $modelPorts = ''): Techs
	{
		$manufacturer = new Manufacturers();
		$manufacturer->setAttributes(['name' => 'Вендор '.uniqid(), 'comment' => ''], false);
		$this->assertTrue($manufacturer->save(false));

		$model = new TechModels();
		$model->setAttributes(['name' => 'Модель '.uniqid(), 'manufacturers_id' => $manufacturer->id,
			'ports' => $modelPorts, 'comment' => ''], false);
		$this->assertTrue($model->save(false));

		$tech = new Techs();
		$tech->setAttributes(['model_id' => $model->id, 'num' => $num.'-'.uniqid(),
			'history' => ''], false);
		$this->assertTrue($tech->save(false));
		return $tech;
	}

	/**
	 * Ярлык группы - содержимое порта: строка с одним лишь «Po1» не удаляет
	 * себя как пустая.
	 *
	 * Порт без связи и без комментария удаляется при сохранении
	 * ({@see Ports::beforeSave()}); членство в группе под это правило
	 * подпадать не должно, иначе собранная группа распадается при первом же
	 * сохранении без связи. Снял ярлык - и порт снова пустой.
	 */
	public function testAggregateLabelKeepsPort()
	{
		$switch = $this->makeTech('SW', "Gi1/0/47\nGi1/0/48");

		$port = Ports::forTech($switch, 'Gi1/0/47');
		$port->aggr = ' Po1 ';
		$this->assertTrue($port->save(), implode('; ', $port->firstErrors));
		$this->assertSame('Po1', $port->aggr, 'ярлык обрезается по краям');

		$port->comment = '';
		$port->save();
		$this->assertNotNull(Ports::findOne($port->id), 'порт с ярлыком группы не пустой');

		$port->aggr = '';
		$port->save();
		$this->assertNull(Ports::findOne($port->id), 'без ярлыка, связи и комментария порт не нужен');
	}

	/** Порт устройства: существующая строка либо новая (строки ленивые) */
	public function testForTech()
	{
		$switch = $this->makeTech('SW');

		$fresh = Ports::forTech($switch, 'Gi1/0/5');
		$this->assertTrue($fresh->isNewRecord);
		$this->assertSame('Gi1/0/5', $fresh->name);
		$this->assertTrue($fresh->save(false));

		$again = Ports::forTech($switch, 'Gi1/0/5');
		$this->assertFalse($again->isNewRecord);
		$this->assertSame($fresh->id, $again->id);
	}

	/** Привязка обнаруженного оборудования к порту создаёт парную связь */
	public function testAttachCreatesPairedLink()
	{
		$switch = $this->makeTech('SW');
		$server = $this->makeTech('SRV', "eth0\neth1");

		$port = Ports::forTech($switch, 'Gi1/0/5');
		$port->link_techs_id = $server->id;
		//порта на той стороне ещё нет - создаётся по имени
		$port->link_ports_id = 'create:eth0';
		$this->assertTrue($port->save());

		$port->refresh();
		$peer = $port->linkPort;
		$this->assertIsObject($peer);
		$this->assertSame('eth0', $peer->name);
		$this->assertSame($server->id, $peer->techs_id);
		//встречная связь ставится сама - иначе половина топологии односторонняя
		$this->assertSame($port->id, (int)$peer->link_ports_id);
	}

	/** Замена: прежний сосед отцепляется, новый цепляется */
	public function testReplaceMovesLink()
	{
		$switch = $this->makeTech('SW');
		$old = $this->makeTech('SRV-OLD', 'eth0');
		$new = $this->makeTech('SRV-NEW', 'eth0');

		$port = Ports::forTech($switch, 'Gi1/0/5');
		$port->link_techs_id = $old->id;
		$port->link_ports_id = 'create:eth0';
		$this->assertTrue($port->save());
		$port->refresh();
		$oldPeerId = (int)$port->link_ports_id;

		$port->link_techs_id = $new->id;
		$port->link_ports_id = 'create:eth0';
		$this->assertTrue($port->save());
		$port->refresh();

		$this->assertSame($new->id, $port->linkPort->techs_id);
		//прежний порт остался, но уже ни с чем не связан
		$oldPeer = Ports::findOne($oldPeerId);
		$this->assertTrue(!is_object($oldPeer) || empty($oldPeer->link_ports_id));
	}

	/**
	 * Удаление порта не оставляет ссылок в никуда.
	 *
	 * Раньше встречный порт продолжал ссылаться на удалённую строку, и
	 * карточка соседа показывала связь, которой нет. Сама строка соседа при
	 * этом может и исчезнуть: порт без связи и без комментария модель
	 * удаляет — он и существовал только ради этой связи.
	 */
	public function testDeleteClearsCounterLink()
	{
		$switch = $this->makeTech('SW');
		$server = $this->makeTech('SRV', 'eth0');

		$port = Ports::forTech($switch, 'Gi1/0/5');
		$port->link_techs_id = $server->id;
		$port->link_ports_id = 'create:eth0';
		$this->assertTrue($port->save());
		$port->refresh();
		$peerId = (int)$port->link_ports_id;
		$portId = $port->id;

		$port->delete();

		$peer = Ports::findOne($peerId);
		$this->assertTrue(!is_object($peer) || empty($peer->link_ports_id));
		//главное: ни одна строка не ссылается на удалённый порт
		$this->assertSame(0, (int)Ports::find()->where(['link_ports_id' => $portId])->count());
	}

	/** Порт с комментарием при удалении соседа остаётся - в нём есть данные */
	public function testDeleteKeepsCommentedCounterPort()
	{
		$switch = $this->makeTech('SW');
		$server = $this->makeTech('SRV', 'eth0');

		$port = Ports::forTech($switch, 'Gi1/0/5');
		$port->link_techs_id = $server->id;
		$port->link_ports_id = 'create:eth0';
		$this->assertTrue($port->save());
		$port->refresh();

		$peer = $port->linkPort;
		$peer->comment = 'жёлтый патчкорд';
		$this->assertTrue($peer->save(false));
		$peerId = $peer->id;

		$port->delete();

		$kept = Ports::findOne($peerId);
		$this->assertIsObject($kept, 'порт с комментарием удалять нельзя');
		$this->assertEmpty($kept->link_ports_id);
	}

	/** Снятие связи отвязывает и встречную сторону */
	public function testDropLink()
	{
		$switch = $this->makeTech('SW');
		$server = $this->makeTech('SRV', 'eth0');

		$port = Ports::forTech($switch, 'Gi1/0/5');
		$port->link_techs_id = $server->id;
		$port->link_ports_id = 'create:eth0';
		$this->assertTrue($port->save());
		$port->refresh();
		$peerId = (int)$port->link_ports_id;

		$this->assertTrue($port->dropLink());

		$peer = Ports::findOne($peerId);
		$this->assertTrue(!is_object($peer) || empty($peer->link_ports_id));
		//сам порт мог и удалиться (пустая строка без связи и комментария) -
		//это штатное поведение модели, а не потеря данных
		$left = Ports::findOne($port->id);
		$this->assertTrue(!is_object($left) || empty($left->link_ports_id));
	}
}
