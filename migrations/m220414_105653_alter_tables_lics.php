<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m220414_105653_alter_tables_lics
 */
class m220414_105653_alter_tables_lics extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('lic_types', 'comment',$this->text());
		$this->alterColumn('lic_items', 'comment',$this->text());
		$this->alterColumn('lic_groups', 'comment',$this->text());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('lic_items', 'comment',$this->string());
		$this->alterColumn('lic_groups', 'comment',$this->string());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220414_105653_alter_tables_lics cannot be reverted.\n";

        return false;
    }
    */
}
