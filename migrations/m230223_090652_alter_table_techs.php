<?php

use yii\db\Migration;

/**
 * Class m230223_090652_alter_table_techs
 */
class m230223_090652_alter_table_techs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$this->addColumn('techs','comp_id',$this->integer()->null());
		$this->addColumn('techs','installed_id',$this->integer()->null());
		$this->addColumn('techs','installed_pos',$this->string(128));
		$this->addColumn('techs','head_id',$this->integer()->null());
		$this->addColumn('techs','responsible_id',$this->integer()->null());
		$this->addColumn('techs','hw',$this->text()->defaultValue(''));
		$this->addColumn('techs','updated_at',$this->timestamp());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumn('techs','comp_id');
		$this->dropColumn('techs','installed_id');
		$this->dropColumn('techs','installed_pos');
		$this->dropColumn('techs','head_id');
		$this->dropColumn('techs','responsible_id');
		$this->dropColumn('techs','hw');
		$this->dropColumn('techs','updated_at');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230223_090652_alter_table_techs cannot be reverted.\n";

        return false;
    }
    */
}
