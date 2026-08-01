<?php

namespace tests\unit\models;

use app\models\Comps;
use app\models\CompsRescanQueue;
use app\models\Domains;
use app\models\Manufacturers;
use app\models\Soft;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты условного/отложенного рескана ПО при сохранении ОС (Comps).
 *
 * Правила:
 *  - скан (softHits_ids из raw_soft+soft_ids) запускается только когда менялся
 *    отпечаток raw_soft или паспортное ПО soft_ids (или взведен forceRescan);
 *  - рядовое сохранение (правка комментария и т.п.) скан не запускает и
 *    заданий в очередь не ставит;
 *  - при soft.deferred_rescan=true скан вместо запуска ставит задание
 *    CompsRescanQueue с soft_id=null, обработчик (cron comps/rescan)
 *    сканирует с forceRescan и чистит очередь.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class CompsRescanDeferTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var mixed исходное значение параметра отложенного рескана */
	private $savedDeferredParam;
	/** @var bool исходное значение глобальной блокировки рескана */
	private $savedDisableRescan;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		$this->savedDeferredParam = Yii::$app->params['soft.deferred_rescan'] ?? false;
		//статические кэши Soft переживают тесты - отключаем, чтобы скан видел
		//созданные/изменённые в транзакции продукты
		Soft::$disable_cache = true;
		//Comps::beforeDelete взводит Soft::$disable_rescan глобально и не сбрасывает -
		//тест, удалявший ОС ранее (например CompsAbsorbTest), оставляет флаг взведенным
		$this->savedDisableRescan = Soft::$disable_rescan;
		Soft::$disable_rescan = false;
	}

	protected function _after()
	{
		Yii::$app->params['soft.deferred_rescan'] = $this->savedDeferredParam;
		Soft::$disable_cache = false;
		Soft::$disable_rescan = $this->savedDisableRescan;
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function makeSoft(string $mask): Soft
	{
		$manufacturer = new Manufacturers();
		$manufacturer->setAttributes(['name' => 'RescanTestVendor'], false);
		$this->assertTrue($manufacturer->save(false), print_r($manufacturer->errors, true));

		$soft = new Soft();
		$soft->setAttributes([
			'manufacturers_id' => $manufacturer->id,
			'descr' => 'rescan-test-product',
			'items' => $mask,
			'additional' => '',
		], false);
		$this->assertTrue($soft->save(false), print_r($soft->errors, true));
		return $soft;
	}

	private function makeComp(?string $rawSoft): Comps
	{
		$domain = new Domains();
		$domain->setAttributes([
			'name' => 'rescantst',
			'fqdn' => 'rescantst.local',
			'comment' => 'домен для теста рескана',
		], false);
		$this->assertTrue($domain->save(), print_r($domain->errors, true));

		$comp = new Comps();
		$comp->setAttributes([
			'name' => 'rescantsthost',
			'os' => 'Debian 13',
			'domain_id' => $domain->id,
			'raw_soft' => $rawSoft,
		], false);
		$this->assertTrue($comp->save(false), print_r($comp->errors, true));
		return $comp;
	}

	/** Строка отпечатка софта без издателя (как у пакетов linux) */
	private static function fingerprint(string ...$names): string
	{
		return implode(',', array_map(
			static fn($name) => '{"publisher":"","name":"' . $name . '"}',
			$names
		));
	}

	/** Задания полного рескана (soft_id=null) для ОС */
	private static function fullRescanQueue(Comps $comp): array
	{
		return CompsRescanQueue::find()
			->where(['comps_id' => $comp->id, 'soft_id' => null])
			->all();
	}

	/**
	 * Realtime-режим: скан на insert с отпечатком; сохранение без изменения
	 * отпечатка скан НЕ перезапускает; изменение отпечатка - перезапускает.
	 */
	public function testRealtimeScanOnlyWhenFingerprintChanges()
	{
		Yii::$app->params['soft.deferred_rescan'] = false;

		$soft = $this->makeSoft('RescanTestPkg');
		$comp = $this->makeComp(static::fingerprint('RescanTestPkg', 'other-package'));

		$this->assertContains($soft->id, $comp->softHits_ids,
			'на insert с отпечатком продукт должен распознаться');

		//ломаем маску продукта напрямую в БД (мимо AR-событий):
		//если следующий save запустит скан - хит пропадет
		Soft::updateAll(['items' => 'NoSuchPackageAnywhere'], ['id' => $soft->id]);

		$comp = Comps::findOne($comp->id);
		$comp->comment = 'ручная правка без отпечатка';
		$this->assertTrue($comp->save(false), print_r($comp->errors, true));
		$this->assertContains($soft->id, $comp->softHits_ids,
			'сохранение без изменения отпечатка не должно перезапускать скан');

		//а вот изменение отпечатка обязано пересканировать (и хит пропадет)
		$comp = Comps::findOne($comp->id);
		$comp->raw_soft = static::fingerprint('RescanTestPkg', 'other-package', 'new-package');
		$this->assertTrue($comp->save(false), print_r($comp->errors, true));
		$this->assertNotContains($soft->id, $comp->softHits_ids,
			'изменение отпечатка должно перезапустить скан по актуальным маскам');
	}

	/**
	 * Отложенный режим: изменение отпечатка не сканирует, а ставит задание
	 * soft_id=null (без дублей); обработчик очереди (forceRescan+silentSave,
	 * как cron comps/rescan) сканирует и чистит очередь.
	 */
	public function testDeferredModeQueuesAndCronProcesses()
	{
		Yii::$app->params['soft.deferred_rescan'] = true;

		$soft = $this->makeSoft('RescanTestPkg');
		$comp = $this->makeComp(static::fingerprint('RescanTestPkg'));

		$this->assertNotContains($soft->id, $comp->softHits_ids,
			'в отложенном режиме insert не должен сканировать');
		$this->assertCount(1, static::fullRescanQueue($comp),
			'insert с отпечатком должен поставить задание полного рескана');

		//повторный push с изменившимся отпечатком - задание не дублируется
		$comp = Comps::findOne($comp->id);
		$comp->raw_soft = static::fingerprint('RescanTestPkg', 'second-package');
		$this->assertTrue($comp->save(false), print_r($comp->errors, true));
		$this->assertCount(1, static::fullRescanQueue($comp),
			'повторное изменение отпечатка не должно плодить задания');

		//обработка очереди - как это делает yii comps/rescan
		$comp = Comps::findOne($comp->id);
		$comp->forceRescan = true;
		$this->assertTrue($comp->silentSave(), print_r($comp->errors, true));
		$this->assertContains($soft->id, $comp->softHits_ids,
			'обработчик очереди должен выполнить скан');
		$this->assertCount(0, CompsRescanQueue::find()->where(['comps_id' => $comp->id])->all(),
			'после выполненного скана очередь этой ОС должна быть очищена');
	}

	/**
	 * Отложенный режим: ручная правка ОС без изменения отпечатка/паспорта
	 * не сканирует и не ставит заданий; правка паспорта (soft_ids) - ставит.
	 */
	public function testDeferredModeManualEditsQueueOnlyOnPassportChange()
	{
		Yii::$app->params['soft.deferred_rescan'] = true;

		$soft = $this->makeSoft('RescanTestPkg');
		$comp = $this->makeComp(null);
		$this->assertCount(0, static::fullRescanQueue($comp),
			'insert без отпечатка не должен ставить заданий');

		$comp = Comps::findOne($comp->id);
		$comp->comment = 'ручная правка';
		$this->assertTrue($comp->save(false), print_r($comp->errors, true));
		$this->assertCount(0, static::fullRescanQueue($comp),
			'правка полей без отпечатка/паспорта не должна ставить заданий');

		//правка паспортного ПО влияет на softHits - должна ставить задание
		$comp = Comps::findOne($comp->id);
		$comp->soft_ids = [$soft->id];
		$this->assertTrue($comp->save(false), print_r($comp->errors, true));
		$this->assertCount(1, static::fullRescanQueue($comp),
			'правка паспорта (soft_ids) должна ставить задание рескана');
	}
}
