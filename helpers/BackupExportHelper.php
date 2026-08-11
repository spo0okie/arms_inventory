<?php
/**
 * Экспорт конфигураций для скриптов резервного копирования из данных
 * требований/работ регламентного обслуживания (ключ "backup" в external_links).
 *
 * ВАЖНО: сама схема ключа "backup" — соглашение организации, а не продукта
 * (см. docs/help/models/maintenance-reqs.md и maintenance-jobs.md).
 * Продуктовый механизм здесь один — JSON-поле external_links.
 *
 * Соглашение:
 *  - требование РК:  {"backup": {"class": "vm"|"file",
 *                                "gfs": {"days":N,"weeks":N,"months":N,"years":N}}}
 *  - работа РК:      {"backup": {"mechanism": "veeam-vm"|"file"|"tape",
 *                                "source": "путь",
 *                                "type": "file"|"folder",
 *                                "excludeExtensions": ["log",...]}}
 * Retention работы не хранится — выводится из привязанных требований
 * (с учётом наследования от родительской работы и перекрытия требований).
 */

namespace app\helpers;

use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;

class BackupExportHelper
{
	/** @var string[] слои GFS-схемы в каноническом порядке (Д-Н-М-Г) */
	const GFS_LAYERS=['days','weeks','months','years'];

	/** @var int сколько уровней пути входит в папку задания ленты («Тип\Сервер») */
	const TAPE_FOLDER_DEPTH=2;

	/*=================================================================
	 * Чистые функции над массивами (тестируются без БД)
	 *================================================================*/

	/**
	 * Нормализовать GFS-схему: все четыре слоя целыми числами.
	 * @param mixed $gfs как хранится в JSON
	 * @return array|null null если схема не задана или пустая (все слои 0)
	 */
	public static function normalizeGfs($gfs)
	{
		if (!is_array($gfs)) return null;
		$result=[];
		foreach (static::GFS_LAYERS as $layer)
			$result[$layer]=max(0,(int)($gfs[$layer]??0));
		return array_sum($result)?$result:null;
	}

	/**
	 * Текстовая метка GFS-схемы вида Д-Н-М-Г ("7-0-3-0")
	 * @param array $gfs нормализованная схема
	 * @return string
	 */
	public static function gfsLabel(array $gfs)
	{
		$values=[];
		foreach (static::GFS_LAYERS as $layer) $values[]=$gfs[$layer]??0;
		return implode('-',$values);
	}

	/**
	 * Ненулевые слои GFS-схемы (для retentionPolicy в конфиге прореживания)
	 * @param array $gfs нормализованная схема
	 * @return array
	 */
	public static function gfsPolicy(array $gfs)
	{
		return array_filter($gfs,static function($value){return $value>0;});
	}

	/**
	 * Попадает ли файловый бэкап в задание ленты: у каждого ненулевого слоя
	 * схемы ленты значение слоя файлового бэкапа совпадает.
	 * (задание ленты "0-0-12-0" забирает бэкапы с months=12)
	 * @param array $tapeGfs нормализованная схема задания ленты
	 * @param array $fileGfs нормализованная схема файлового бэкапа
	 * @return bool
	 */
	public static function gfsLayersMatch(array $tapeGfs, array $fileGfs)
	{
		$matched=false;
		foreach (static::GFS_LAYERS as $layer) {
			if (!($tapeGfs[$layer]??0)) continue;
			if (($fileGfs[$layer]??0)!=($tapeGfs[$layer]??0)) return false;
			$matched=true;
		}
		return $matched;
	}

	/**
	 * Папка задания ленты («Тип\Сервер») из пути источника файлового бэкапа:
	 * путь относительно корня, обрезанный до $depth уровней.
	 * @param string $source полный путь источника (F:\FileBackups\MSSQL\TCDB\full\ReportDB)
	 * @param string $root   корневая папка задания ленты (F:\FileBackups\)
	 * @param int    $depth  сколько уровней оставить
	 * @return string|null null если источник не внутри корня
	 */
	public static function relativeFolder(string $source, string $root, int $depth=self::TAPE_FOLDER_DEPTH)
	{
		$normalize=static function($path){return rtrim(str_replace('/','\\',$path),'\\');};
		$source=$normalize($source);
		$root=$normalize($root);
		if (!strlen($root)) return null;
		$prefix=$root.'\\';
		if (strncasecmp($source,$prefix,strlen($prefix))!==0) return null;
		$parts=array_values(array_filter(explode('\\',substr($source,strlen($prefix))),'strlen'));
		if (!count($parts)) return null;
		return implode('\\',array_slice($parts,0,$depth));
	}

	/**
	 * Конфиг прореживания (retentionPolicy.json) из плоских описаний работ.
	 * Берутся работы с mechanism=file, у которых есть источник и GFS-схема.
	 * @param array $jobs плоские описания работ {@see exportJob()}
	 * @return array
	 */
	public static function retentionPolicy(array $jobs)
	{
		$entries=[];
		foreach ($jobs as $job) {
			if (($job['mechanism']??null)!=='file') continue;
			if (empty($job['source'])||empty($job['gfs'])) continue;
			$entry=[
				'jobName'=>$job['name'],
				'type'=>$job['type'],
				'source'=>$job['source'],
				'retentionPolicy'=>static::gfsPolicy($job['gfs']),
			];
			if (!empty($job['excludeExtensions']))
				$entry['excludeExtensions']=$job['excludeExtensions'];
			$entries[]=$entry;
		}
		usort($entries,static function($a,$b){return strnatcasecmp($a['jobName'],$b['jobName']);});
		return $entries;
	}

	/**
	 * Конфиг заданий ленты (TapeJobs.json) из плоских описаний работ.
	 * Задание — работа с mechanism=tape: имя задания — GFS-схема её требования,
	 * source — корень файловых бэкапов, folders — папки «Тип\Сервер» файловых
	 * работ (mechanism=file), чьи GFS-слои совпадают со слоями задания.
	 * @param array $jobs плоские описания работ {@see exportJob()}
	 * @return array
	 */
	public static function tapeJobs(array $jobs)
	{
		$entries=[];
		foreach ($jobs as $tape) {
			if (($tape['mechanism']??null)!=='tape') continue;
			if (empty($tape['source'])||empty($tape['gfs'])) continue;
			$folders=[];
			foreach ($jobs as $file) {
				if (($file['mechanism']??null)!=='file') continue;
				if (empty($file['source'])||empty($file['gfs'])) continue;
				if (!static::gfsLayersMatch($tape['gfs'],$file['gfs'])) continue;
				if (is_null($folder=static::relativeFolder($file['source'],$tape['source']))) continue;
				$folders[strtolower($folder)]=$folder;	//дедупликация без учета регистра
			}
			usort($folders,'strnatcasecmp');
			$entries[]=[
				'jobName'=>static::gfsLabel($tape['gfs']),
				'type'=>$tape['type'],
				'source'=>$tape['source'],
				'folders'=>array_values($folders),
			];
		}
		usort($entries,static function($a,$b){return strnatcasecmp($a['jobName'],$b['jobName']);});
		return $entries;
	}

	/*=================================================================
	 * Извлечение данных из моделей
	 *================================================================*/

	/**
	 * GFS-схема требования РК (ключ backup.gfs в external_links)
	 * @param MaintenanceReqs $req
	 * @return array|null
	 */
	public static function reqGfs(MaintenanceReqs $req)
	{
		if (!$req->is_backup) return null;
		return static::normalizeGfs($req->getExternalItem(['backup','gfs']));
	}

	/**
	 * Эффективная GFS-схема работы: требования работы (с наследованием от
	 * родительской работы), у которых задана GFS-схема, минус перекрытые
	 * другими требованиями набора; при нескольких оставшихся — по каждому
	 * слою берётся максимум (нормально эффективное требование одно).
	 * @param MaintenanceJobs $job
	 * @return array|null
	 */
	public static function jobGfs(MaintenanceJobs $job)
	{
		$reqs=[];
		foreach ($job->reqsRecursive??[] as $req) {
			if (!is_null(static::reqGfs($req))) $reqs[]=$req;
		}
		if (!count($reqs)) return null;

		$merged=[];
		foreach (MaintenanceReqs::filterEffective($reqs) as $req) {
			/** @var MaintenanceReqs $req */
			if ($req->archivedOrAbsorbed) continue;
			foreach (static::reqGfs($req) as $layer=>$value)
				$merged[$layer]=max($merged[$layer]??0,$value);
		}
		return static::normalizeGfs($merged);
	}

	/**
	 * Плоское описание работы для экспорта: механизм/источник из ключа
	 * backup в external_links, GFS-схема — из привязанных требований.
	 * @param MaintenanceJobs $job
	 * @return array|null null если работа не участвует в РК (нет backup.mechanism)
	 *                    или архивирована
	 */
	public static function exportJob(MaintenanceJobs $job)
	{
		if ($job->isArchived) return null;
		$backup=$job->getExternalItem(['backup']);
		if (!is_array($backup)||empty($backup['mechanism'])) return null;
		return [
			'name'=>$job->name,
			'mechanism'=>$backup['mechanism'],
			'source'=>$backup['source']??null,
			'type'=>$backup['type']??'file',
			'excludeExtensions'=>array_values((array)($backup['excludeExtensions']??[])),
			'gfs'=>static::jobGfs($job),
		];
	}

	/**
	 * Плоские описания всех работ РК
	 * @param MaintenanceJobs[] $jobs
	 * @return array
	 */
	public static function exportJobs($jobs)
	{
		$result=[];
		foreach ($jobs as $job) {
			if (!is_null($export=static::exportJob($job))) $result[]=$export;
		}
		return $result;
	}

	/**
	 * Проблемы данных, мешающие экспорту (для предупреждений оператору):
	 * у работы с механизмом РК не задан источник или не выводится GFS-схема.
	 * @param array $jobs плоские описания работ {@see exportJobs()}
	 * @return string[]
	 */
	public static function exportProblems(array $jobs)
	{
		$problems=[];
		foreach ($jobs as $job) {
			if (!in_array($job['mechanism'],['file','tape'])) continue;
			if (empty($job['source']))
				$problems[]="{$job['name']}: не задан backup.source";
			if (empty($job['gfs']))
				$problems[]="{$job['name']}: не привязано требование РК с GFS-схемой";
		}
		return $problems;
	}
}
