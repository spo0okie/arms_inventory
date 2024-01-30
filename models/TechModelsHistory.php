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
class TechModelsHistory extends HistoryModel
{

	public static $title='Изменения модели оборудования';
	public static $titles='Изменения моделей оборудования';
	
	public $masterClass=TechModels::class;
	
	public $journalLinks=[
		'type_id'=>TechTypes::class,
		'manufacturers_id'=>Manufacturers::class,
		'scans_id'=>Scans::class,
	];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tech_models_history';
    }



}