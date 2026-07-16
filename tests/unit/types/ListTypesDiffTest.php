<?php

namespace tests\unit\types;

use app\models\CompsHistory;
use app\types\HwListType;
use app\types\SwListType;
use Codeception\Test\Unit;

/**
 * Тесты diffValues() типов-списков для карточек изменений журнала (issue #194).
 *
 * Сценарии ТЗ по отпечатку софта:
 * - ПО удалено — только строки пропавшего ПО;
 * - ПО установлено — только строки добавленного ПО;
 * - ПО обновлено — вендор сохранился, видно что поменялся кусок версии
 *   (эвристика: совпало всё кроме цифровых фрагментов имени — это смена версии,
 *   иначе — удаление одного ПО и установка другого).
 *
 * Отпечаток железа — added/removed без обновлений (версий у железа нет).
 */
class ListTypesDiffTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	private const SW_BASE='{"publisher":"Google LLC", "name":"Google Chrome"},'
		.'{"publisher":"The Document Foundation", "name":"LibreOffice 7.4.1.2"}';

	/**
	 * ПО установлено: только строки добавленного ПО
	 */
	public function testSoftInstalled()
	{
		$diff=(new SwListType())->diffValues(
			self::SW_BASE,
			self::SW_BASE.',{"publisher":"VideoLAN", "name":"VLC media player"}'
		);
		$this->assertCount(1,$diff['added']);
		$this->assertStringContainsString('VLC media player',$diff['added'][0]);
		$this->assertStringContainsString('VideoLAN',$diff['added'][0]);
		$this->assertEquals([],$diff['removed']);
		$this->assertEquals([],$diff['changed']);
	}

	/**
	 * ПО удалено: только строки пропавшего ПО
	 */
	public function testSoftRemoved()
	{
		$diff=(new SwListType())->diffValues(
			self::SW_BASE,
			'{"publisher":"The Document Foundation", "name":"LibreOffice 7.4.1.2"}'
		);
		$this->assertEquals([],$diff['added']);
		$this->assertCount(1,$diff['removed']);
		$this->assertStringContainsString('Google Chrome',$diff['removed'][0]);
		$this->assertEquals([],$diff['changed']);
	}

	/**
	 * ПО обновлено: вендор и имя без цифр совпали - пара показывается как
	 * обновление с общим началом имени и сменой версионного хвоста
	 */
	public function testSoftUpdated()
	{
		$diff=(new SwListType())->diffValues(
			self::SW_BASE,
			'{"publisher":"Google LLC", "name":"Google Chrome"},'
			.'{"publisher":"The Document Foundation", "name":"LibreOffice 7.5.0.3"}'
		);
		$this->assertEquals([],$diff['added']);
		$this->assertEquals([],$diff['removed']);
		$this->assertCount(1,$diff['changed']);
		//общее начало имени сохранено, старый хвост зачёркнут, новый после стрелки
		$this->assertStringContainsString('LibreOffice',$diff['changed'][0]);
		$this->assertStringContainsString('<del class="text-muted">7.4.1.2</del>',$diff['changed'][0]);
		$this->assertStringContainsString('&rarr; 7.5.0.3',$diff['changed'][0]);
		$this->assertStringContainsString('The Document Foundation',$diff['changed'][0]);
	}

	/**
	 * Замена ПО (другой вендор/имя) - это не обновление, а удаление + установка
	 */
	public function testSoftReplacedIsNotUpdate()
	{
		$diff=(new SwListType())->diffValues(
			'{"publisher":"Mail.ru LLC", "name":"ICQ (версия 23.2.0.48119)"}',
			'{"publisher":"Telegram FZ-LLC", "name":"Telegram Desktop 4.8"}'
		);
		$this->assertCount(1,$diff['added']);
		$this->assertCount(1,$diff['removed']);
		$this->assertEquals([],$diff['changed']);
	}

	/**
	 * Пакетный стиль имени (Linux): версия приклеена дефисами и содержит
	 * буквенно-цифровой суффикс ревизии - тоже опознаётся как обновление
	 */
	public function testSoftUpdatedDebianPackageStyle()
	{
		$publisher='Debian Python Team <team+python@tracker.debian.org>';
		$diff=(new SwListType())->diffValues(
			'{"publisher":"'.addslashes($publisher).'", "name":"python3-jwt-2.6.0-1"}',
			'{"publisher":"'.addslashes($publisher).'", "name":"python3-jwt-2.10.1-2+deb13u1"}'
		);
		$this->assertEquals([],$diff['added']);
		$this->assertEquals([],$diff['removed']);
		$this->assertCount(1,$diff['changed']);
		//общее имя пакета сохранено, сменился только версионный хвост
		$this->assertStringContainsString('python3-jwt-<del class="text-muted">2.6.0-1</del>',$diff['changed'][0]);
		$this->assertStringContainsString('&rarr; 2.10.1-2+deb13u1',$diff['changed'][0]);
	}

	/**
	 * Пакетный стиль: разные пакеты одного вендора не склеиваются в обновление
	 */
	public function testSoftDifferentPackagesAreNotUpdate()
	{
		$diff=(new SwListType())->diffValues(
			'{"publisher":"Debian", "name":"python3-jwt-2.6.0-1"}',
			'{"publisher":"Debian", "name":"python3-yaml-6.0.1-2"}'
		);
		$this->assertCount(1,$diff['added']);
		$this->assertCount(1,$diff['removed']);
		$this->assertEquals([],$diff['changed']);
	}

	/**
	 * Смена версии в скобках (формат «ICQ (версия N)») тоже опознаётся
	 */
	public function testSoftVersionInParentheses()
	{
		$diff=(new SwListType())->diffValues(
			'{"publisher":"Mail.ru LLC", "name":"ICQ (версия 23.2.0.48119)"}',
			'{"publisher":"Mail.ru LLC", "name":"ICQ (версия 24.1.0.11111)"}'
		);
		$this->assertCount(1,$diff['changed']);
		$this->assertEquals([],$diff['added']);
		$this->assertEquals([],$diff['removed']);
	}

	/**
	 * Перестановка/переформатирование без изменения состава: пустой diff
	 * (карточка покажет «изменение только форматирования»)
	 */
	public function testSoftFormattingOnly()
	{
		$diff=(new SwListType())->diffValues(
			self::SW_BASE,
			'{"publisher":"The Document Foundation","name":"LibreOffice 7.4.1.2"},'."\n"
			.'{"publisher":"Google LLC","name":"Google Chrome"}'
		);
		$this->assertEquals([],$diff['added']);
		$this->assertEquals([],$diff['removed']);
		$this->assertEquals([],$diff['changed']);
	}

	/**
	 * Дубли карточек - мультимножество: пропажа одного из двух дублей видна
	 */
	public function testSoftDuplicatesAreCounted()
	{
		$dup='{"publisher":"Корпорация Майкрософт", "name":"Среда выполнения Microsoft Edge WebView2 Runtime"}';
		$diff=(new SwListType())->diffValues($dup.','.$dup,$dup);
		$this->assertCount(1,$diff['removed']);
		$this->assertEquals([],$diff['added']);
	}

	/**
	 * Кривой JSON с любой стороны: типового diff нет (null), карточка падает
	 * на генерик-рендер
	 */
	public function testSoftInvalidJsonFallsBack()
	{
		$type=new SwListType();
		$this->assertNull($type->diffValues('кривой json',self::SW_BASE));
		$this->assertNull($type->diffValues(self::SW_BASE,'кривой json'));
	}

	/**
	 * Первая запись журнала (старого значения нет): весь список - добавленный
	 */
	public function testSoftFirstRecordAllAdded()
	{
		$diff=(new SwListType())->diffValues(null,self::SW_BASE);
		$this->assertCount(2,$diff['added']);
		$this->assertEquals([],$diff['removed']);
	}

	/**
	 * Железо: замена диска - выбывший + добавленный элементы, обновлений нет
	 */
	public function testHwReplaced()
	{
		$base='{"motherboard":{"manufacturer":"ASUS","product":"PRIME B560M-A","serial":"MB-1"}}';
		$diff=(new HwListType())->diffValues(
			$base.',{"harddisk":{"model":"WDC WD10EZEX","size":"1000","serial":"HD-1"}}',
			$base.',{"harddisk":{"model":"Samsung SSD 870","size":"2000","serial":"SSD-1"}}'
		);
		$this->assertCount(1,$diff['added']);
		$this->assertCount(1,$diff['removed']);
		$this->assertEquals([],$diff['changed']);
		$this->assertStringContainsString('harddisk',$diff['added'][0]);
		$this->assertStringContainsString('Samsung SSD 870, 2000, SSD-1',$diff['added'][0]);
		$this->assertStringContainsString('WDC WD10EZEX',$diff['removed'][0]);
	}

	/**
	 * Интеграция с журналом: attributeTypedDiff на CompsHistory.raw_soft
	 * отдаёт diff типа, на скалярах - null
	 */
	public function testHistoryAttributeTypedDiff()
	{
		$previous=new CompsHistory(['raw_soft'=>self::SW_BASE]);
		$record=new CompsHistory([
			'raw_soft'=>self::SW_BASE.',{"publisher":"VideoLAN", "name":"VLC media player"}',
		]);
		$record->setPreviousRecord($previous);

		$diff=$record->attributeTypedDiff('raw_soft');
		$this->assertNotNull($diff);
		$this->assertCount(1,$diff['added']);
		$this->assertStringContainsString('VLC media player',$diff['added'][0]);

		//скаляр без типового diff
		$this->assertNull($record->attributeTypedDiff('name'));
	}
}
