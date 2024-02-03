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
class TechsHistory extends HistoryModel
{

	public static $title='Изменения оборудования';
	public static $titles='Изменения оборудования';
	
	public $masterClass=Techs::class;
	
	public $journalMany2ManyLinks=[
		'contracts_ids' => Contracts::class,
		'services_ids' => Services::class,
		'lic_items_ids' => LicItems::class,
		'lic_keys_ids' => LicKeys::class,
		'lic_groups_ids' => LicGroups::class,
		'maintenance_reqs_ids' => MaintenanceReqs::class,
		'materials_usages_ids' => MaterialsUsages::class,
	];
	
	public $journalLinks=[
		'domain_id'=>Domains::class,
		
		'model_id'=>TechModels::class,
		'arms_id'=>Techs::class,
		'installed_id'=>Techs::class,
		
		'places_id'=>Places::class,
		
		'user_id'=>Users::class,
		'head_id'=>Users::class,
		'responsible_id'=>Users::class,
		'it_staff_id'=>Users::class,
		
		'state_id'=>TechStates::class,
		'scans_id'=>Scans::class,
		'departments_id'=>Departments::class,
		'comp_id'=>Comps::class,
		
		'partners_id'=>Partners::class,
	];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'techs_history';
    }

    
}