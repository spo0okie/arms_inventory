<?php

namespace app\models;

use app\models\traits\CompsModelCalcFieldsTrait;

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
class CompsHistory extends HistoryModel
{
	use CompsModelCalcFieldsTrait;
	
	public static $title='Изменения ОС/ВМ';
	public static $titles='Изменения ОС/ВМ';
	
	public $masterClass=Comps::class;
	
	public $linksSchema=[
		'arm_id' =>						Techs::class,
		'domain_id' =>					Domains::class,
		'user_id' =>					Users::class,
		
		'services_ids'=>[Services::class,'comps_ids'],
		'aces_ids'=>[Aces::class,'comps_ids'],
		'acls_ids'=>[Acls::class,'comp_ids'],
		'lic_groups_ids' => [LicGroups::class,'comp_ids'],
		'lic_items_ids' => [LicItems::class,'comp_ids'],
		'lic_keys_ids' => [LicKeys::class,'comp_ids'],
		
		'maintenance_reqs_ids'=>[MaintenanceReqs::class,'comps_ids'],
		'maintenance_jobs_ids'=>[MaintenanceJobs::class,'comps_ids'],
	
	];
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'comps_history';
    }
	
	public $responsible=null;
	public $supportTeam=[];
	public $netIps=[];
	public $lastThreeLogins=[];
}