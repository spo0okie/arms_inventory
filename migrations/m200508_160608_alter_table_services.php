<?php

use yii\db\Migration;

/**
 * Class m200508_160608_alter_table_services
 */
class m200508_160608_alter_table_services extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('services');
	
		if (!isset($table->columns['segment_id'])) {
			$this->addColumn('{{%services}}','[[segment_id]]',$this->integer()->null());
			$this->createIndex('{{%idx-services_segment}}', 		'{{%services}}', '[[segment_id]]');
			// add foreign key for table `{{%department}}`
			$this->addForeignKey(
				'{{%fk-services-segment}}',
				'{{%services}}',
				'[[segment_id]]',
				'{{%segments}}',
				'id',
				'RESTRICT'
			);
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (isset($table->foreignKeys['fk-services-segment']))        $this->dropForeignKey('fk-services-segment','{{%services}}');
		if (isset($table->columns['segment_id']))		  			  $this->dropColumn('{{%services}}','[[segment_id]]');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200508_160608_alter_table_services cannot be reverted.\n";

        return false;
    }
    */
}
