<?php

namespace app\models;

use app\models\traits\AcesModelCalcFieldsTrait;

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
class AcesHistory extends HistoryModel
{
	use AcesModelCalcFieldsTrait;
	
	
	public static $title='Изменения записи доступа';
	public static $titles='Изменения записей доступа';
	
	public $masterClass=Aces::class;
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aces_history';
    }


    public $netIps=[]; //надо как-то их собирать из просто IP списка

}