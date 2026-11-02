<?php

namespace app\console\commands;

use app\components\ResignDeferReport;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/** Периодическая сводка изменений исключений из увольнения для notify/send. */
class ResignDeferController extends Controller
{
	/** Сравнивает списки и ставит изменения в очередь; период задаётся снаружи. */
	public function actionWatch()
	{
		try {
			$report = new ResignDeferReport();
			$queued = $report->run();
			$this->stdout("Поставлено в очередь: $queued\n");
			foreach ($report->errors as $error) $this->stderr($error . "\n");
			return $report->errors ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
		} catch (\Throwable $e) {
			Yii::error($e->getMessage(), 'notify');
			$this->stderr($e->getMessage() . "\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
	}
}
