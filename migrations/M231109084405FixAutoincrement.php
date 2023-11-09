<?php

namespace app\migrations;

use yii\db\Migration;

/**
 * Class M231109084405FixAutoincrement
 */
class M231109084405FixAutoincrement extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$this->execute('set @max_id=(select max(id) from hw_ignore); update hw_ignore set id=@max_id+1 where id=0;');
		$this->alterColumn('hw_ignore','id',$this->integer()->append(' AUTO_INCREMENT'));
		$this->execute('set @max_id=(select max(id) from tech_states); update tech_states set id=@max_id+1 where id=0;');
		$this->alterColumn('tech_states','id',$this->integer()->append(' AUTO_INCREMENT'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "M231109084405FixAutoincrement no need to be reverted.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M231109084405FixAutoincrement cannot be reverted.\n";

        return false;
    }
    */
}
