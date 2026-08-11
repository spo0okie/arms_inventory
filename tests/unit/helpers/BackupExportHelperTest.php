<?php

namespace tests\unit\helpers;

use app\helpers\BackupExportHelper;
use Codeception\Test\Unit;

/**
 * Тесты генерации конфигов для скриптов резервного копирования
 * (прореживание retentionPolicy.json и перенос на ленту TapeJobs.json)
 * из плоских описаний работ РК. Чистые функции — без БД.
 */
class BackupExportHelperTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Плоское описание работы (как из BackupExportHelper::exportJob)
	 */
	protected function job($name,$mechanism,$source,$gfs,$type='file',$exclude=[])
	{
		return [
			'name'=>$name,
			'mechanism'=>$mechanism,
			'source'=>$source,
			'type'=>$type,
			'excludeExtensions'=>$exclude,
			'gfs'=>is_null($gfs)?null:BackupExportHelper::normalizeGfs($gfs),
		];
	}

	/**
	 * Нормализация GFS: недостающие слои — нули, пустая схема — null
	 */
	public function testNormalizeGfs()
	{
		$this->assertEquals(
			['days'=>7,'weeks'=>0,'months'=>3,'years'=>0],
			BackupExportHelper::normalizeGfs(['days'=>7,'months'=>3])
		);
		$this->assertNull(BackupExportHelper::normalizeGfs(null));
		$this->assertNull(BackupExportHelper::normalizeGfs('7-0-3-0'));
		$this->assertNull(BackupExportHelper::normalizeGfs(['days'=>0]));
	}

	/**
	 * Метка схемы Д-Н-М-Г
	 */
	public function testGfsLabel()
	{
		$this->assertEquals('7-0-3-0',
			BackupExportHelper::gfsLabel(BackupExportHelper::normalizeGfs(['days'=>7,'months'=>3])));
	}

	/**
	 * Совпадение слоёв: задание ленты забирает бэкап только при точном
	 * совпадении значений всех своих ненулевых слоёв
	 */
	public function testGfsLayersMatch()
	{
		$tape=BackupExportHelper::normalizeGfs(['months'=>12]);
		$this->assertTrue(BackupExportHelper::gfsLayersMatch($tape,
			BackupExportHelper::normalizeGfs(['days'=>14,'weeks'=>4,'months'=>12,'years'=>5])));
		//months=6 не попадает в задание months=12
		$this->assertFalse(BackupExportHelper::gfsLayersMatch($tape,
			BackupExportHelper::normalizeGfs(['months'=>6])));
		//days=14 не попадает в задание days=7
		$this->assertFalse(BackupExportHelper::gfsLayersMatch(
			BackupExportHelper::normalizeGfs(['days'=>7]),
			BackupExportHelper::normalizeGfs(['days'=>14,'weeks'=>4])));
	}

	/**
	 * Папка задания ленты: путь относительно корня, обрезанный до «Тип\Сервер»
	 */
	public function testRelativeFolder()
	{
		$this->assertEquals('MSSQL\TCDB',
			BackupExportHelper::relativeFolder('F:\FileBackups\MSSQL\TCDB\full\ReportDB','F:\FileBackups\\'));
		//путь ровно в два уровня
		$this->assertEquals('VDI\XS',
			BackupExportHelper::relativeFolder('F:\FileBackups\VDI\XS','F:\FileBackups'));
		//источник вне корня
		$this->assertNull(
			BackupExportHelper::relativeFolder('D:\Other\MSSQL\TCDB','F:\FileBackups\\'));
		//регистр не мешает
		$this->assertEquals('Sites\azimut.ru',
			BackupExportHelper::relativeFolder('f:\filebackups\Sites\azimut.ru','F:\FileBackups\\'));
	}

	/**
	 * Конфиг прореживания: только file-работы с источником и схемой,
	 * retention без нулевых слоёв, сортировка по имени
	 */
	public function testRetentionPolicy()
	{
		$entries=BackupExportHelper::retentionPolicy([
			$this->job('teamcenterTC','file','F:\FileBackups\MSSQL\TCDB\full\TC',
				['days'=>14,'weeks'=>4,'months'=>12,'years'=>5]),
			$this->job('HanaEqa','file','F:\FileBackups\HANA\hana-eqa\full',
				['months'=>3],'folder'),
			$this->job('logs','file','F:\FileBackups\Logs',null),				//без требования - не экспортируется
			$this->job('vm-backup','veeam-vm',null,['days'=>14]),				//не file - не экспортируется
			$this->job('tape','tape','F:\FileBackups\\',['months'=>12]),		//лента - не сюда
			$this->job('BSE','file','F:\FileBackups\MSSQL\BSE\full\bse',
				['weeks'=>4,'months'=>6,'years'=>5],'file',['log','tmp']),
		]);

		$this->assertEquals(['BSE','HanaEqa','teamcenterTC'],array_column($entries,'jobName'));

		$this->assertEquals([
			'jobName'=>'HanaEqa',
			'type'=>'folder',
			'source'=>'F:\FileBackups\HANA\hana-eqa\full',
			'retentionPolicy'=>['months'=>3],
		],$entries[1]);

		//исключаемые расширения выгружаются, retention содержит только ненулевые слои
		$this->assertEquals(['log','tmp'],$entries[0]['excludeExtensions']);
		$this->assertEquals(['weeks'=>4,'months'=>6,'years'=>5],$entries[0]['retentionPolicy']);
	}

	/**
	 * Конфиг заданий ленты: имя задания — GFS-схема требования, папки —
	 * «Тип\Сервер» файловых работ с совпадающими слоями
	 */
	public function testTapeJobs()
	{
		$jobs=[
			//файловые бэкапы
			$this->job('teamcenterTC','file','F:\FileBackups\MSSQL\TCDB\full\TC',
				['days'=>14,'weeks'=>4,'months'=>12,'years'=>5]),
			$this->job('teamcenterReportDB','file','F:\FileBackups\MSSQL\TCDB\full\ReportDB',
				['days'=>14,'weeks'=>4,'months'=>12,'years'=>5]),
			$this->job('HanaEqa','file','F:\FileBackups\HANA\hana-eqa\full',['months'=>3],'folder'),
			$this->job('site_azimut','file','F:\FileBackups\Sites\azimut.ru',['days'=>7,'weeks'=>4],'folder'),
			$this->job('elsewhere','file','D:\OtherRoot\MSSQL\XXX',['months'=>12]),	//вне корня ленты
			//задания ленты
			$this->job('Лента месячных','tape','F:\FileBackups\\',['months'=>12]),
			$this->job('Лента квартальных','tape','F:\FileBackups\\',['months'=>3]),
			$this->job('Лента дневных','tape','F:\FileBackups\\',['days'=>7]),
		];

		$entries=BackupExportHelper::tapeJobs($jobs);

		//сортировка natcase: 0-0-3-0 < 0-0-12-0 < 7-0-0-0
		$this->assertEquals(['0-0-3-0','0-0-12-0','7-0-0-0'],array_column($entries,'jobName'));

		$byName=array_column($entries,null,'jobName');
		//папка TCDB дедуплицирована из двух работ, вне корня - отброшено
		$this->assertEquals(['MSSQL\TCDB'],$byName['0-0-12-0']['folders']);
		$this->assertEquals(['HANA\hana-eqa'],$byName['0-0-3-0']['folders']);
		//days=14 в задание days=7 не попадает
		$this->assertEquals(['Sites\azimut.ru'],$byName['7-0-0-0']['folders']);
		$this->assertEquals('F:\FileBackups\\',$byName['7-0-0-0']['source']);
	}

	/**
	 * Проблемы данных: механизм РК задан, а источника или схемы нет
	 */
	public function testExportProblems()
	{
		$problems=BackupExportHelper::exportProblems([
			$this->job('ok','file','F:\FileBackups\A\B',['days'=>7]),
			$this->job('noSource','file',null,['days'=>7]),
			$this->job('noReq','tape','F:\FileBackups\\',null),
			$this->job('vm','veeam-vm',null,null),	//veeam-vm не проверяется
		]);
		$this->assertCount(2,$problems);
		$this->assertStringContainsString('noSource',$problems[0]);
		$this->assertStringContainsString('noReq',$problems[1]);
	}
}
