<?php
namespace app\migrations;
use app\models\NetIps;
use app\models\Networks;
use app\models\OrgInet;
use app\models\Partners;
use app\models\Services;
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
		
		foreach (OrgInet::find()->all() as $inet) {
			/**
			 * @var $inet OrgInet
			 */
			echo $inet->name."\n";
			$inetService=new Services();
			$inetService->name = 'Услуги связи '.$inet->name;
			$inetService->is_service=false;
			$inetService->is_end_user=false;
			$inetService->contracts_ids=[$inet->contracts_id];
			$inetService->description='Автоматически созданная услуга для ввода интернет';
			
			/** @var Partners $partner */
			$partner= Partners::find()
				->where([
					'or',
					['like','uname',$inet->provTel->name],
					['like','bname',$inet->provTel->name],
				])
				->one();
			if (is_object($partner)) {
				foreach (['cabinet_url','support_tel','comment'] as $field) {
						$partner->$field = $inet->provTel->$field;
				}
				$partner->save(false);
				$inetService->partners_id=$partner->id;
			}
			
			if (strlen($inet->ip_addr)) {
				$ip=new NetIps();
				$ip->text_addr=$inet->ip_addr;
				$ip->beforeSave(false);
				if ($ip->networks_id) $inet->networks_id=$ip->networks_id;
				else {
					$net=new Networks();
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
    	/** @var OrgInet $inet */
		foreach (OrgInet::find()->all() as $inet) {
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
