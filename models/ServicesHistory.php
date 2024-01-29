<?php

namespace app\models;

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

	public static $title='Изменения сервиса/услуги';
	public static $titles='Изменения сервисов/услуг';
	
	public $masterClass=Services::class;
	
	public $journalMany2ManyLinks=[
		'depends_ids'=>Services::class,
		'comps_ids'=>Comps::class,
		'techs_ids'=>Techs::class,
		'maintenance_reqs_ids'=>MaintenanceReqs::class,
		'support_ids'=>Users::class,
		'infrastructure_support_ids'=>Users::class,
		'contracts_ids' => Contracts::class,
	];
	
	public $journalLinks=[
		'responsible_id'=>Users::class,
		'infrastructure_user_id'=>Users::class,
		'providing_schedule_id'=>Schedules::class,
		'support_schedule_id'=>Schedules::class,
		'segment_id'=>Segments::class,
		'parent_id'=>Services::class,
		'partners_id'=>Partners::class,
		'places_id'=>Places::class,
		'currency_id'=>Currency::class,
	];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'services_history';
    }



}