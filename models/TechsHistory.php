<?php

namespace app\models;

use app\models\traits\TechsModelCalcFieldsTrait;/**
 * This is the model class for table "maintenance_reqs_history".
 *
 * @property int $id
 * @property int|null $master_id
 * @property string|null $hostname
 * @property string|null $num
 * @property string|null $description
 * @property string|null $links
 * @property string|null $services_ids
 * @property string|null $comps_ids
 * @property string|null $scans_id
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 * @property string sname
 * @property TechModels $model
 * @property Users $responsible
 * @property Services[] $services
 */
class TechsHistory extends HistoryModel
{
	use TechsModelCalcFieldsTrait;
	
	public static $title='Изменения оборудования';
	public static $titles='Изменения оборудования';
	
	public $masterClass=Techs::class;
	
	public $linksSchema=[
		'contracts_ids' => 			[Contracts::class,'techs_ids'],
		'services_ids' => 			[Services::class,'techs_ids'],
		'lic_items_ids' =>			[LicItems::class,'techs_ids'],
		'lic_keys_ids' =>			[LicKeys::class,'techs_ids'],
		'lic_groups_ids' =>			[LicGroups::class,'techs_ids'],
		'maintenance_reqs_ids' =>	[MaintenanceReqs::class,'techs_ids'],
		'maintenance_jobs_ids' =>	[MaintenanceJobs::class,'techs_ids'],
		'acls_ids' =>				[Acls::class,'techs_id'],

		'materials_usages_ids' => 	[MaterialsUsages::class,'techs_id'],
		'domain_id'=>Domains::class,
		
		'model_id' =>				[TechModels::class,'loader'=>'model'],
		'arms_id' =>				[Techs::class,'arm_techs_ids'],
		'installed_id' =>			[Techs::class,'installed_techs_ids','loader'=>'installation'],
		
		'places_id' =>				[Places::class,'techs_ids'],
		
		'user_id' =>				[Users::class,'techs_ids'],
		'head_id' =>				[Users::class,'head_techs_ids'],
		'responsible_id' =>			[Users::class,'responsible_techs_ids','loader'=>'admResponsible'],
		'it_staff_id' =>			[Users::class,'it_techs_ids'],
		
		'state_id' =>				TechStates::class,
		'scans_id' =>				Scans::class,
		'departments_id' =>			Departments::class,
		'comp_id' =>				Comps::class,
		
		'partners_id' =>			Partners::class,
	];

	public $netIps=[];				//мы не можем показать пока ИП, надо делать подгрузку
	public $attaches=[];			//мы не храним вложения
	public $maintenanceJobs=[];		//пока не храним джобы
	public $armTechs=[];			//не сохраняем что входит в состав АРМ
	public $installedTechs=[];		//не сохраняем что вставлено в оборудование

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'techs_history';
    }
	
    

}