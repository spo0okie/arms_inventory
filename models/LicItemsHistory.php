<?php

namespace app\models;

use app\models\traits\LicGroupsModelCalcFieldsTrait;
use app\models\traits\LicItemsModelCalcFieldsTrait;

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
class LicItemsHistory extends HistoryModel
{
	use LicItemsModelCalcFieldsTrait;
	
	public static $title='Изменения закупки лицензий';
	public static $titles='Изменения закупки лицензий';
	
	public $masterClass=LicItems::class;
	public static $nameAttr='descr';
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lic_items_history';
    }
}