<?php

namespace app\models;

use app\models\traits\MaintenanceReqsModelCalcFieldsTrait;

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
	use MaintenanceReqsModelCalcFieldsTrait;
	
	public static $title='Изменения требований обслуживания';
	public static $titles='Изменения требований обслуживания';
	
	public $masterClass=MaintenanceReqs::class;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'maintenance_reqs_history';
    }
	
    public $absorbed=false;

    

}