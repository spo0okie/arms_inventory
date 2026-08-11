<?php

namespace tests\unit\models;

use app\models\Techs;
use Codeception\Test\Unit;
use Yii;

/**
 * Контракт слияния external_links при сохранении
 * (ExternalDataModelTrait::externalDataBeforeSave): запись одной внешней
 * ссылки НЕ затирает остальные. На этот контракт опирается обратная
 * запись hostid из скрипта синхронизации Zabbix (arms.zabbix): он шлёт
 * PUT только с ключом Zabbix.hostid и рассчитывает, что VMWare.* и прочее
 * сохранятся.
 */
class ExternalLinksMergeTest extends Unit
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
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	/** Запись нового ключа сохраняет ранее записанные */
	public function testNewKeyMergesWithExisting()
	{
		$tech = new Techs(['num' => 'EXT-1']);
		$tech->external_links = json_encode(['VMWare.UUID' => 'abc@vc']);
		$this->assertTrue($tech->save(false));

		//вторая запись — только Zabbix.hostid (как делает arms.zabbix)
		$reloaded = Techs::findOne($tech->id);
		$reloaded->external_links = json_encode(['Zabbix.hostid' => '10501']);
		$this->assertTrue($reloaded->save(false));

		$final = Techs::findOne($tech->id);
		$data = json_decode($final->external_links, true);
		$this->assertSame('abc@vc', $data['VMWare.UUID'], 'старый ключ сохранён');
		$this->assertSame('10501', $data['Zabbix.hostid'], 'новый ключ записан');
	}

	/** Повторная запись того же ключа обновляет значение */
	public function testSameKeyUpdatesValue()
	{
		$tech = new Techs(['num' => 'EXT-2']);
		$tech->external_links = json_encode(['Zabbix.hostid' => '100']);
		$this->assertTrue($tech->save(false));

		$reloaded = Techs::findOne($tech->id);
		$reloaded->external_links = json_encode(['Zabbix.hostid' => '200']);
		$this->assertTrue($reloaded->save(false));

		$final = Techs::findOne($tech->id);
		$this->assertSame('200', json_decode($final->external_links, true)['Zabbix.hostid']);
	}
}
