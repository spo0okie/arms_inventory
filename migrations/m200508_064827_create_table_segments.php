<?php

use yii\db\Migration;

/**
 * Class m200508_064827_create_table_segments
 */
class m200508_064827_create_table_segments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_null($table = $this->db->getTableSchema('segments'))) {
			$this->createTable('segments', [
				'[[id]]'			=> $this->primaryKey(),		        //ключ
				'[[name]]'	        => $this->string(32),		//имя
				'[[description]]'	=> $this->string(255),		//описание
			],'ENGINE=InnoDB');
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (!is_null($table=$this->db->getTableSchema('segments'))) $this->dropTable('segments');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200508_064827_create_table_segments cannot be reverted.\n";

        return false;
    }
    */
}
