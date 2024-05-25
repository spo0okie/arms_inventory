<?php

namespace app\models;

use app\models\traits\TechsModelCalcFieldsTrait;/**
 * This is the model class for table "maintenance_reqs_history".
 *
 * @property int $id
 * @property int|null $master_id
 * @property string|null $hostname
 * @property string|null $num
 * @property string|null $description
 * @property string|null $links
 * @property string|null $services_ids
 * @property string|null $comps_ids
 * @property string|null $scans_id
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 * @property string sname
 * @property TechModels $model
 * @property Users $responsible
 * @property Services[] $services
 */
class TechsHistory extends HistoryModel
{
	use TechsModelCalcFieldsTrait;
	
	public static $title='Изменения оборудования';
	public static $titles='Изменения оборудования';
	
	public $masterClass=Techs::class;

	public $netIps=[];				//мы не можем показать пока ИП, надо делать подгрузку
	public $attaches=[];			//мы не храним вложения
	public $maintenanceJobs=[];		//пока не храним джобы
	public $armTechs=[];			//не сохраняем что входит в состав АРМ
	public $installedTechs=[];		//не сохраняем что вставлено в оборудование

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'techs_history';
    }
	
    

}