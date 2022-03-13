<?php

use yii\db\Migration;

/**
 * Class m220303_191454_alter_table_org_inets
 */
class m220303_191454_alter_table_org_inets extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		$table = $this->db->getTableSchema('org_inet');
		if (!isset($table->columns['services_id']))
			$this->addColumn('org_inet', 'services_id', $this->integer()->defaultValue(null));
	
		if (!isset($table->columns['networks_id']))
			$this->addColumn('org_inet', 'networks_id', $this->integer()->defaultValue(null));
		
		foreach (app\models\OrgInet::find()->all() as $inet) {
			/**
			 * @var $inet \app\models\OrgInet
			 */
			echo $inet->name."\n";
			$inetService=new app\models\Services();
			$inetService->name = 'Услуги связи '.$inet->name;
			$inetService->is_service=false;
			$inetService->is_end_user=false;
			$inetService->contracts_ids=[$inet->contracts_id];
			$inetService->description='Автоматически созданная услуга для ввода интернет';
			
			$partner=app\models\Partners::find()
				->where([
					'or',
					['like','uname',$inet->provTel->name],
					['like','bname',$inet->provTel->name]
				])
				->one();
			if (is_object($partner)) {
				$needSave=false;
				foreach (['cabinet_url','support_tel','comment'] as $field) {
					//if (strlen($inet->provTel->$field) && !strlen($partner->$field)) {
						$partner->$field = $inet->provTel->$field;
						$needSave = true;
					//}
				}
				if ($needSave) $partner->save(false);
				$inetService->partners_id=$partner->id;
			}
			
			if (strlen($inet->ip_addr)) {
				$ip=new \app\models\NetIps();
				$ip->text_addr=$inet->ip_addr;
				$ip->beforeSave(false);
				if ($ip->networks_id) $inet->networks_id=$ip->networks_id;
				else {
					$net=new \app\models\Networks();
					$net->text_addr=$inet->ip_addr.'/30';
					$net->save(false);
					$inet->networks_id=$net->id;
				}
			}
			
			$inetService->save(false);
			$inet->services_id = $inetService->id;
			$inet->save(false);
			//$inetService->partner=$inet->provTel->;
		}
	
	}

    /**
     * {@inheritdoc}
     */
    public function down()
    {
		foreach (app\models\OrgInet::find()->all() as $inet) {
			if (is_object($inet->service)) $inet->service->delete();
		}
  
		$table = $this->db->getTableSchema('org_inet');
		if (isset($table->columns['services_id']))
			$this->dropColumn('org_inet', 'services_id');

		if (isset($table->columns['networks_id']))
			$this->dropColumn('org_inet', 'networks_id');
		
		
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220303_191454_alter_table_org_inets cannot be reverted.\n";

        return false;
    }
    */
}
