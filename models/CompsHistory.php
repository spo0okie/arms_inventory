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