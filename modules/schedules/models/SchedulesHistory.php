<?php

namespace app\modules\schedules\models;

use app\modules\schedules\models\traits\SchedulesModelCalcFieldsTrait;

/**
 * This is the model class for table "maintenance_reqs_history".
 *
 * @property int $id
 * @property int|null $master_id
 * @property string|null $name
 * @property string|null $services_ids
 * @property string|null $comps_ids
 * @property string|null $techs_ids
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 * @property string sname
 */
class SchedulesHistory extends \app\models\HistoryModel
{
	use SchedulesModelCalcFieldsTrait;
	
	
	public static $title='Изменения расписания';
	public static $titles='Изменения расписаний';
	
	public $masterClass=Schedules::class;
	
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'schedules_history';
    }
	
	/**
	 * Подменяем поиск исключений в архиве расписания,
	 * ибо функция имеет смысл для оперативных данных, а архивным никто не будет пользоваться
	 * @return array
	 */
	public function findExceptions(){return [];}

	/**
	 * Смысл тот же. Это нужно для оперативной работы с расписанием. Можно переделать на работу с уже загруженными данными
	 * но в контексте архивных данных смысл все равно теряется
	 * @return array
	 */
	public function findPeriods(){return [];}

	/**
	 * История не содержит compiled_json, поэтому статус «активно/нет» для архивной записи
	 * не вычисляется (нет реалтайма). Возвращаем 0 — это соответствует семантике «архив не активен».
	 * Calc-поле getStatus() в трейте опирается на $this->isWorkTime(), которого в History нет.
	 * @return int
	 */
	public function getStatus() { return 0; }

	/**
	 * History не содержит compiled_json и не вычисляет рабочее время — заглушка для совместимости.
	 */
	public function isWorkTime($date, $time) { return 0; }

	/**
	 * History не вычисляет meta активного интервала.
	 */
	public function metaAtTime($date, $time) { return '{}'; }

	/**
	 * History не вычисляет meta ближайшего рабочего времени.
	 */
	public function nextWorkingMeta($date, $time) { return '{}'; }
}
