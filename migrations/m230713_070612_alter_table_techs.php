<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230713_070612_alter_table_techs
 */
class m230713_070612_alter_table_techs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('techs','ip', $this->string(768));
		$this->alterColumn('comps','ip', $this->string(768));
		$this->alterColumn('techs','mac', $this->string(768));
		$this->alterColumn('comps','mac', $this->string(768));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('techs','ip', $this->string(255));
		$this->alterColumn('comps','ip', $this->string(512));
		$this->alterColumn('techs','mac', $this->string(255));
		$this->alterColumn('comps','mac', $this->string(512));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230713_070612_alter_table_techs cannot be reverted.\n";

        return false;
    }
    */
}
