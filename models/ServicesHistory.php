<?php

namespace app\models;

use app\models\traits\ServicesModelCalcFieldsTrait;

/**
 * This is the model class for table "maintenance_reqs_history".
 *
 * @property int $id
 * @property int|null $master_id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $links
 * @property string|null $services_ids
 * @property string|null $comps_ids
 * @property string|null $techs_ids
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 * @property string sname
 */
class ServicesHistory extends HistoryModel
{
	use ServicesModelCalcFieldsTrait;

	public static $title='Изменения сервиса/услуги';
	public static $titles='Изменения сервисов/услуг';
	
	public $masterClass=Services::class;
	
	public $linksSchema=[
		'depends_ids' =>				[Services::class,'dependants_ids'],
		'comps_ids' =>					[Comps::class,'services_ids'],
		'techs_ids' =>					[Techs::class,'services_ids'],
		'maintenance_reqs_ids'=>		[MaintenanceReqs::class,'services_ids'],
		'maintenance_jobs_ids'=>		[MaintenanceJobs::class,'services_ids'],
		'support_ids' =>				[Users::class,'support_services_ids'],
		'infrastructure_support_ids' =>	[Users::class,'infrastructure_support_services_ids'],
		'contracts_ids' => 				[Contracts::class,'services_ids'],
		'acls_ids' => 					[Acls::class,'services_id'],
		
		'responsible_id' =>				[Users::class,'services_ids'],
		'infrastructure_user_id' =>		[Users::class,'infrastructure_services_ids','loader'=>'infrastructureResponsible'],
		'providing_schedule_id' =>		[Schedules::class,'providing_services_ids'],
		'support_schedule_id' =>		[Schedules::class,'support_services_ids'],
		'segment_id' =>					[Segments::class,'services_ids'],
		'parent_id' =>					[Services::class,'children_ids','loader'=>'parentService'],
		'partners_id' =>				[Partners::class,'services_ids'],
		'places_id' =>					[Places::class,'services_ids'],
		'currency_id' =>				Currency::class,
	];
	
	public $orgInets=[];	//не ведем историю(
	public $orgPhones=[];	//не ведем историю(
	public $dependants=[];	//не ведем историю(
	public $children=[];	//не ведем историю(
	public $attaches=[];	//не ведем историю(

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'services_history';
    }

}