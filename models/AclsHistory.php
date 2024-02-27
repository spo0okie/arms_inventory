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
class AclsHistory extends HistoryModel
{

	public static $title='Изменения списка доступа';
	public static $titles='Изменения списков доступа';
	
	public $masterClass=Acls::class;
	
	public $linksSchema=[
		'aces_ids'=>[Aces::class,'acls_id'],
		'schedules_id'=>[Schedules::class,'acls_ids'],
		'services_id'=>[Services::class,'acls_ids'],
		'comps_id'=>[Comps::class,'acls_ids'],
		'techs_id'=>[Techs::class,'acls_ids'],
	];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'acls_history';
    }



}