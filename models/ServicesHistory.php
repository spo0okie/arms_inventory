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
	
	public static $masterClass=Services::class;
	
	public static $journalMany2ManyLinks=[
		'depends_ids'=>Services::class,
		'comps_ids'=>Comps::class,
		'techs_ids'=>Techs::class,
		'maintenance_reqs_ids'=>MaintenanceReqs::class,
		'support_ids'=>Users::class,
		'infrastructure_support_ids'=>Users::class,
		'contracts_ids' => Contracts::class,
	];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'services_history';
    }



}