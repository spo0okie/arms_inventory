<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210716_120416_alter_table_comps
 */
class m210716_120416_alter_table_comps extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->alterColumn('comps', 'ip', $this->string(512));
		$this->alterColumn('comps', 'ip_ignore', $this->string(512));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->alterColumn('comps', 'ip', $this->string(256));
		$this->alterColumn('comps', 'ip_ignore', $this->string(256));
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m210716_120416_alter_table_comps cannot be reverted.\n";

		return false;
	}
	*/
}
