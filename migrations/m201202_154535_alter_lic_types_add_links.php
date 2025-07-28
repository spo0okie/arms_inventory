<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m201202_154535_alter_lic_types_add_links
 */
class m201202_154535_alter_lic_types_add_links extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('lic_types');
		if (!isset($table->columns['links'])) {
			$this->addColumn('lic_types', 'links', $this->text()->Null());
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('lic_types');
		if (isset($table->columns['links'])) {
			$this->dropColumn('lic_types', 'links');
		}
	}
	
}
