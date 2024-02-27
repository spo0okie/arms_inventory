<?php

namespace app\models;

/**
 * This is the model class for table "maintenance_reqs_history".
 *
 * @property int $id
 * @property int|null $master_id
 * @property string|null $name
 * @property string|null $description
 * @property int|null $spread_comps
 * @property int|null $spread_techs
 * @property string|null $links
 * @property string|null $services_ids
 * @property string|null $comps_ids
 * @property string|null $techs_ids
 * @property string|null $reqs_ids
 * @property string|null $jobs_ids
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 * @property string sname
 */
class MaterialsHistory extends HistoryModel
{

	public static $title='Изменения ЗИП и материалов';
	public static $titles='Изменения ЗИП и материалов';
	
	public $masterClass=Materials::class;
	
	public $linksSchema=[
		'contracts_ids' =>	[Contracts::class,'materials_ids'],
		'usages_ids' =>		[MaterialsUsages::class,'materials_id'],
		'parent_id' =>		[Materials::class,'children_ids'],
		'type_id' =>		[MaterialsTypes::class,'materials_ids'],
		'places_id' =>		[Places::class,'materials_ids'],
		'it_staff_id' =>	[Users::class,'materials_ids'],
		'currency_id' =>	Currency::class
	];
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'materials_history';
    }


    

}