<?php

namespace app\models;

use app\models\traits\LicGroupsModelCalcFieldsTrait;
use app\models\traits\NetworksModelCalcFieldsTrait;

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
class NetworksHistory extends HistoryModel
{
	use NetworksModelCalcFieldsTrait;
	
	public static $title='Изменения сети';
	public static $titles='Изменения сетей';
	
	public $masterClass=Networks::class;
	//public static $nameAttr='descr';
	
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'networks_history';
    }
}