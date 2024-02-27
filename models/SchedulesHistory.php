<?php

namespace app\models;

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

	public static $title='Изменения расписания';
	public static $titles='Изменения расписаний';
	
	public $masterClass=Schedules::class;
	
	public $linksSchema=[
		'parent_id' => 				[Schedules::class,'children_ids'],
		'override_id' =>			[Schedules::class,'overrides_ids'],
		'entries_ids' =>			[SchedulesEntries::class,'schedule_id'],
		'acls_ids' => 				[Acls::class,'schedules_id'],
		'providing_services_ids' => [Services::class,'providing_schedule_id'],
		'support_services_ids' => 	[Services::class,'support_schedule_id'],
		'maintenance_jobs_ids' => 	[MaintenanceJobs::class,'schedules_id'],
		'overrides_ids' => 			[Schedules::class,'override_id'],
	];
	
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'schedules_history';
    }



}