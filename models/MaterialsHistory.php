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
	public static $nameAttr='model';
	
	public $masterClass=Materials::class;
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'materials_history';
    }
	
	
	public function getTypeName()
	{
		return ($this->type->name??'type_error').': '.$this->model;
	}

}