<?php

namespace app\modules\schedules\models;

use app\modules\schedules\models\traits\ScheduleEntriesModelCalcFieldsTrait;

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
class SchedulesEntriesHistory extends \app\models\HistoryModel
{
	use ScheduleEntriesModelCalcFieldsTrait;

	public static $title='Изменения записи расписания';
	public static $titles='Изменения записей расписания';
	
	public $masterClass=SchedulesEntries::class;
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'schedules_entries_history';
    }



}
