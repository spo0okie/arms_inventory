<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\models\Comps;
use app\models\CompsRescanQueue;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Консольный контроллер для управления записями компьютеров (Comps).
 *
 * Использование:
 *   yii comps/index
 *   yii comps/fix-dupes
 *   yii comps/resave
 *   yii comps/rescan [count]
 *   yii comps/find <name>
 */
class CompsController extends Controller
{
	/**
	 * Заглушка — точка входа контроллера.
	 *
	 * Использование: yii comps/index
	 *
	 * @return int ExitCode::OK
	 */
	public function actionIndex()
	{
		return ExitCode::OK;
	}
	

	/**
	 * Находит и поглощает дублирующиеся записи ПК без привязки к домену (domain_id IS NULL).
	 *
	 * Для каждого ПК с domain_id ищет дубликаты по имени без domain_id
	 * и вызывает absorbComp() для их слияния.
	 *
	 * Использование: yii comps/fix-dupes
	 *
	 * @return int ExitCode::OK
	 */
	public function actionFixDupes()
	{
		if (is_array($comps= Comps::find()->all()))
			foreach ($comps as $comp)
				if ($comp->domain_id && is_array($dupes=$comp->dupes) && count($dupes)) {
					foreach ($dupes as $dupe) if (!$dupe->domain_id) {
						echo "{$comp->domainName} absorbing {$dupe->domainName}\n";
						$comp->absorbComp($dupe);
					}
				}
			
		return ExitCode::OK;
	}
	
	/**
	 * Пересохраняет все записи ПК через silentSave() без запуска событий истории.
	 *
	 * Применяется для массового пересчёта вычисляемых полей.
	 *
	 * Использование: yii comps/resave
	 *
	 * @return int ExitCode::OK
	 */
	public function actionResave()
	{
		foreach (Comps::find()->all() as $comp) {
			/** @var Comps $comp */
			$comp->silentSave();
		}
		
		return ExitCode::OK;
	}

	/**
	 * Обрабатывает очередь пересканирования ПК (CompsRescanQueue).
	 *
	 * Для каждой записи очереди вызывает silentSave() соответствующего ПК,
	 * что инициирует повторное сканирование его данных.
	 *
	 * Использование: yii comps/rescan [count]
	 *
	 * @param int $count Максимальное количество записей очереди для обработки за один запуск (по умолчанию: 100)
	 * @return int ExitCode::OK
	 */
	public function actionRescan($count=100)
	{
		$queue=CompsRescanQueue::find()
			->groupBy(['comps_id'])
			->limit($count)
			->all();
		
		foreach ($queue as $item) {
			/** @var Comps $comp */
			$comp=Comps::findOne($item->comps_id);
			if (is_object($comp)) {
				echo $comp->fqdn."\n";
				$comp->silentSave();
			} else {
				echo $item->comp_id." missing \n";
			}
		}
		
		return ExitCode::OK;
	}
	
	/**
	 * Ищет ПК по имени (любому формату) и выводит его числовой идентификатор.
	 *
	 * Использует Comps::findByAnyName() для поиска по всем вариантам имени.
	 * При успехе выводит ID в stdout и возвращает OK,
	 * при отсутствии записи — возвращает UNAVAILABLE.
	 *
	 * Использование: yii comps/find <name>
	 *
	 * @param string $name Имя ПК (hostname, FQDN или любой поддерживаемый формат)
	 * @return int ExitCode::OK при успехе, ExitCode::UNAVAILABLE если ПК не найден
	 */
	public function actionFind($name)
	{
		if (is_object($comp= Comps::findByAnyName($name))) {
			/** @var Comps $comp */
			echo "{$comp->id}\n";
			return ExitCode::OK;
		}
		return ExitCode::UNAVAILABLE;
	}
}
