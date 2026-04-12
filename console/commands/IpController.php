<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Консольный контроллер для пересчёта IP-данных ПК и оборудования.
 *
 * Использование:
 *   yii ip/index
 *   yii ip/recalc
 *   yii ip/recalc-comps
 *   yii ip/recalc-techs
 */
class IpController extends Controller
{
	/**
	 * Заглушка — точка входа контроллера.
	 *
	 * Использование: yii ip/index
	 *
	 * @return int ExitCode::OK
	 */
	public function actionIndex()
	{
		
		
		return ExitCode::OK;
	}
	
	/**
	 * Пересохраняет все записи ПК для пересчёта IP-данных.
	 *
	 * @return void
	 */
	private function reCalcComps()
	{
		if (is_array($comps=\app\models\Comps::find()->all())) {
			foreach ($comps as $comp) $comp->save();
		};
	}
	
	/**
	 * Пересохраняет все записи техники для пересчёта IP-данных.
	 *
	 * Выводит в stdout номер и IP для каждой записи.
	 *
	 * @return void
	 */
	private function reCalcTechs()
	{
		if (is_array($techs=\app\models\Techs::find()->all())) {
			foreach ($techs as $tech) {
				echo "{$tech->num}: {$tech->ip}\n";
				$tech->save(false);
			}
		};
	}
	
	/**
	 * Пересчитывает IP-данные для всех ПК и оборудования (Techs).
	 *
	 * Последовательно вызывает reCalcComps() и reCalcTechs().
	 *
	 * Использование: yii ip/recalc
	 *
	 * @return int ExitCode::OK
	 */
	public function actionRecalc()
	{
		$this->reCalcComps();
		$this->reCalcTechs();
		return ExitCode::OK;
	}
	
	/**
	 * Пересчитывает IP-данные только для ПК (Comps).
	 *
	 * Использование: yii ip/recalc-comps
	 *
	 * @return int ExitCode::OK
	 */
	public function actionRecalcComps()
	{
		$this->reCalcComps();
		return ExitCode::OK;
	}

	/**
	 * Пересчитывает IP-данные только для оборудования (Techs).
	 *
	 * Использование: yii ip/recalc-techs
	 *
	 * @return int ExitCode::OK
	 */
	public function actionRecalcTechs()
	{
		$this->reCalcTechs();
		return ExitCode::OK;
	}
}
