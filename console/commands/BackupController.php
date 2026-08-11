<?php

namespace app\console\commands;

use app\helpers\BackupExportHelper;
use app\models\MaintenanceJobs;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Экспорт конфигураций для скриптов резервного копирования из инвентаризации.
 *
 * Источник данных — требования/работы регламентного обслуживания с ключом
 * "backup" в поле external_links (схема ключа — соглашение организации,
 * см. docs/help/models/maintenance-reqs.md и maintenance-jobs.md).
 *
 * Использование:
 *   yii backup/retention-policy [файл]   конфиг прореживания файловых бэкапов
 *   yii backup/tape-jobs [файл]          конфиг заданий переноса на ленту
 *
 * Без аргумента JSON выводится в stdout (предупреждения — в stderr),
 * с аргументом — пишется в указанный файл.
 */
class BackupController extends Controller
{
	/**
	 * Сводка по работам РК и проблемам данных, мешающим экспорту.
	 *
	 * Использование: yii backup/index
	 *
	 * @return int
	 */
	public function actionIndex()
	{
		$jobs=BackupExportHelper::exportJobs(MaintenanceJobs::find()->all());
		$byMechanism=[];
		foreach ($jobs as $job) $byMechanism[$job['mechanism']][]=$job['name'];
		foreach ($byMechanism as $mechanism=>$names)
			$this->stdout("$mechanism: ".count($names)." работ(ы)\n",Console::FG_GREEN);
		$this->reportProblems($jobs);
		return ExitCode::OK;
	}

	/**
	 * Генерирует конфиг прореживания файловых бэкапов (retentionPolicy.json):
	 * работы с backup.mechanism=file, retention — из GFS-схемы привязанного
	 * требования РК.
	 *
	 * Использование: yii backup/retention-policy [файл]
	 *
	 * @param string|null $file путь к файлу; без него JSON выводится в stdout
	 * @return int
	 */
	public function actionRetentionPolicy($file=null)
	{
		$jobs=BackupExportHelper::exportJobs(MaintenanceJobs::find()->all());
		$this->reportProblems($jobs);
		return $this->output(BackupExportHelper::retentionPolicy($jobs),$file);
	}

	/**
	 * Генерирует конфиг заданий переноса на ленту (TapeJobs.json):
	 * работы с backup.mechanism=tape, папки — из источников файловых работ,
	 * чьи GFS-слои совпадают со схемой задания ленты.
	 *
	 * Использование: yii backup/tape-jobs [файл]
	 *
	 * @param string|null $file путь к файлу; без него JSON выводится в stdout
	 * @return int
	 */
	public function actionTapeJobs($file=null)
	{
		$jobs=BackupExportHelper::exportJobs(MaintenanceJobs::find()->all());
		$this->reportProblems($jobs);
		return $this->output(BackupExportHelper::tapeJobs($jobs),$file);
	}

	/**
	 * Вывести предупреждения о проблемах данных в stderr
	 * @param array $jobs плоские описания работ
	 */
	protected function reportProblems(array $jobs)
	{
		foreach (BackupExportHelper::exportProblems($jobs) as $problem)
			$this->stderr("ВНИМАНИЕ! $problem\n",Console::FG_YELLOW);
	}

	/**
	 * Записать результат в файл или вывести в stdout
	 * @param array       $data
	 * @param string|null $file
	 * @return int
	 */
	protected function output(array $data,$file=null)
	{
		$json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
		if (is_null($file)) {
			$this->stdout($json);
			return ExitCode::OK;
		}
		if (file_put_contents($file,$json)===false) {
			$this->stderr("Не удалось записать файл $file\n",Console::FG_RED);
			return ExitCode::IOERR;
		}
		$this->stdout("Записано ".count($data)." заданий: $file\n",Console::FG_GREEN);
		return ExitCode::OK;
	}
}
