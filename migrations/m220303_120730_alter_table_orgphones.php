<?php

use yii\db\Migration;

/**
 * Class m220303_120730_alter_table_orgphones
 */
class m220303_120730_alter_table_orgphones extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		$table = $this->db->getTableSchema('org_phones');
		if (!isset($table->columns['services_id']))
			$this->addColumn('org_phones', 'services_id', $this->integer()->defaultValue(null));
	
		foreach (app\models\OrgPhones::find()->all() as $phone) {
			/**
			 * @var $phone \app\models\OrgPhones
			 */
			echo $phone->fullNum."\n";
			$serviceName='Услуги связи '.$phone->provTel->name;
			
			$service=app\models\Services::find()
				->where([
					'like',
					'name',
					$serviceName,
				])
				->one();
			if (is_object($service))
				$phone->services_id=$service->id;
			else {
				$inetService=new app\models\Services();
				$inetService->name = $serviceName;
				$inetService->is_service=false;
				$inetService->is_end_user=false;
				$inetService->contracts_ids=[$phone->contracts_id];
				$inetService->description='Автоматически созданная услуга для ввода интернет';
				$partner=app\models\Partners::find()
					->where([
						'or',
						['like','uname',$phone->provTel->name],
						['like','bname',$phone->provTel->name]
					])
					->one();
				if (is_object($partner)) {
					echo $partner->uname."\n";
					$needSave=false;
					foreach (['cabinet_url','support_tel','comment'] as $field) {
						//if (strlen($phone->provTel->$field) && !strlen($partner->$field)) {
							$partner->$field = $phone->provTel->$field;
							$needSave = true;
						//}
					}
					if ($needSave) $partner->save(false);
					$inetService->partners_id=$partner->id;
				}
				
				$inetService->save(false);
				$phone->services_id = $inetService->id;
			}
			$phone->save(false);
		}
	
	
	}

    /**
     * {@inheritdoc}
     */
    public function down()
    {
		foreach (app\models\OrgPhones::find()->all() as $phone) {
			/**
			 * @var $phone \app\models\OrgPhones
			 */
			if (is_object($phone->service)) $phone->service->delete();
		}

		$table = $this->db->getTableSchema('org_phones');
		if (isset($table->columns['services_id']))
			$this->dropColumn('org_phones', 'services_id');
    }

}
