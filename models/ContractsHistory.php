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
class ContractsHistory extends HistoryModel
{

	public static $title='Изменения документа';
	public static $titles='Изменения документов';
	
	public $masterClass=Contracts::class;
	

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contracts_history';
    }



}