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
class MaintenanceReqsHistory extends HistoryModel
{

	public static $title='История требований обслуживания';
	public static $titles='Изменения требований обслуживания';
	
	public static $journalMany2ManyLinks=[
		'services_ids'=>Services::class,
		'comps_ids'=>Comps::class,
		'techs_ids'=>Techs::class,
		'includes_ids'=>MaintenanceReqs::class,
		'included_ids'=>MaintenanceReqs::class,
	];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'maintenance_reqs_history';
    }



/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['master_id', 'name', 'description', 'spread_comps', 'spread_techs', 'links', 'services_ids', 'comps_ids', 'techs_ids', 'included_ids', 'includes_ids', 'jobs_ids', 'updated_at', 'updated_by', 'updated_comment'], 'default', 'value' => null],
            [['master_id', 'spread_comps', 'spread_techs'], 'integer'],
            [['links', 'services_ids', 'comps_ids', 'techs_ids', 'included_ids', 'includes_ids'], 'string'],
            [['updated_at'], 'safe'],
            [['name', 'updated_comment'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 1024],
            [['updated_by'], 'string', 'max' => 32],
		];
    }


}