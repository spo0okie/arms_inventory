<?php

namespace app\models;

use app\models\traits\ServicesModelCalcFieldsTrait;

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
class ServicesHistory extends HistoryModel
{
	use ServicesModelCalcFieldsTrait;

	public static $title='Изменения сервиса/услуги';
	public static $titles='Изменения сервисов/услуг';
	
	public $masterClass=Services::class;
	
	public $orgInets=[];	//не ведем историю(
	public $orgPhones=[];	//не ведем историю(
	public $dependants=[];	//не ведем историю(
	public $children=[];	//не ведем историю(
	public $attaches=[];	//не ведем историю(

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'services_history';
    }

}