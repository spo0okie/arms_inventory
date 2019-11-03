<?php

use yii\db\Migration;

/**
 * После предыдущих миграций по смене ключа в таблице пользователей, на всех ссылкан на них почемуто стало NOT NULL
 * ALTER TABLE `techs`   CHANGE `it_staff_id` `it_staff_id` INT(16) NOT NULL;
 * ALTER TABLE `techs`   CHANGE `user_id` `user_id` INT(16) NOT NULL;
 * ALTER TABLE `arms`    CHANGE `user_id` `user_id` INT(16) NOT NULL;
 * ALTER TABLE `arms`    CHANGE `it_staff_id` `it_staff_id` INT(16) NOT NULL;
 * ALTER TABLE `arms`    CHANGE `head_id` `head_id` INT(16) NOT NULL;
 * ALTER TABLE `arms`    CHANGE `responsible_id` `responsible_id` INT(16) NOT NULL;
 
 * Class m191103_085902_alter_updatetAt_column_to_arms_table
 */
class m191103_100000_alter_users_columns_to_arms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	    $this->alterColumn('{{%arms}}','{{%user_id}}',$this->integer()->defaultValue(null)	);
	    $this->alterColumn('{{%arms}}','{{%it_staff_id}}',$this->integer()->defaultValue(null)	);
	    $this->alterColumn('{{%arms}}','{{%head_id}}',$this->integer()->defaultValue(null)	);
	    $this->alterColumn('{{%arms}}','{{%responsible_id}}',$this->integer()->defaultValue(null));
	    $this->alterColumn('{{%techs}}','{{%user_id}}',$this->integer()->defaultValue(null));
	    $this->alterColumn('{{%techs}}','{{%it_staff_id}}',$this->integer()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->alterColumn('{{%arms}}','{{%user_id}}',$this->integer()->notNull());
	    $this->alterColumn('{{%arms}}','{{%it_staff_id}}',$this->integer()->notNull());
	    $this->alterColumn('{{%arms}}','{{%head_id}}',$this->integer()->notNull());
	    $this->alterColumn('{{%arms}}','{{%responsible_id}}',$this->integer()->notNull());
	    $this->alterColumn('{{%techs}}','{{%user_id}}',$this->integer()->notNull());
	    $this->alterColumn('{{%techs}}','{{%it_staff_id}}',$this->integer()->notNull());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191103_085902_alter_updatetAt_column_to_arms_table cannot be reverted.\n";

        return false;
    }
    */
}
