<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m211003_141509_alter_table_partners
 */
class m211003_141509_alter_table_partners extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('partners');
		if (!isset($table->columns['cabinet_url']))
			$this->addColumn('partners', 'cabinet_url', $this->string(255)->null());
		
		if (!isset($table->columns['support_tel']))
			$this->addColumn('partners', 'support_tel', $this->string(255)->null());
		
		if (isset($table->columns['coment']))
			$this->renameColumn('partners', 'coment', 'comment');
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('partners');
		if (isset($table->columns['cabinet_url']))
			$this->dropColumn('partners', 'cabinet_url');
		
		if (isset($table->columns['support_tel']))
			$this->dropColumn('partners', 'support_tel');
		
		if (isset($table->columns['comment']))
			$this->renameColumn('partners', 'comment', 'coment');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m211003_141509_alter_table_partners cannot be reverted.\n";

		return false;
	}
	*/
}
