<?php

namespace app\models;

use app\models\traits\SchedulesModelCalcFieldsTrait;

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
class SchedulesHistory extends HistoryModel
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

}