<?php

use yii\data\SqlDataProvider;
use yii\db\Migration;

/**
 * Class m230628_041251_create_table_org_inets_in_networks
 */
class m230628_041251_create_table_org_inets_in_networks extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_null($table = $this->db->getTableSchema('org_inets_in_networks'))) {
			$this->createTable('org_inets_in_networks', [
				'[[id]]'			=> $this->primaryKey(),		//ключ
				'[[org_inets_id]]'		=> $this->integer(),	//inet
				'[[networks_id]]'		=> $this->integer(),	//networks
			],'ENGINE=InnoDB');
		
			$this->createIndex('{{%idx-org_inets_in_networks-networks}}',	'{{%org_inets_in_networks}}', '[[networks_id]]');
			$this->createIndex('{{%idx-org_inets_in_networks-inets}}', 	'{{%org_inets_in_networks}}', '[[org_inets_id]]');
		}
	
		$inets = new SqlDataProvider(['sql'=> 'SELECT * FROM org_inet ','pagination' => false,]);
		foreach ($inets->models as $model) {
			/** @var $model \app\models\OrgInet */
			if ($model['networks_id']) {
				$this->execute(
					"insert into org_inets_in_networks (org_inets_id,networks_id) values (:inet,:net)",
					[
						':inet'=>$model['id'],
						':net'=>$model['networks_id']
					]
				);
			}
		}

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (!is_null($table = $this->db->getTableSchema('org_inets_in_networks'))) {
			$this->dropTable('org_inets_in_networks');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230628_041251_create_table_org_inets_in_networks cannot be reverted.\n";

        return false;
    }
    */
}
