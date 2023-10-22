<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m221007_163802_add_archive_columns
 */
class m221007_163802_add_archive_columns extends Migration
{
	private $tables=['comps','tech_states','org_inet','org_phones'];
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	foreach ($this->tables as $tableName) {
			$table = $this->db->getTableSchema($tableName);
			if (!isset($table->columns['archived'])) {
				$this->addColumn($tableName,'archived',$this->boolean()->defaultValue(false));
				$this->createIndex($tableName.'_archived_index',$tableName,'archived');
			}
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		foreach ($this->tables as $tableName) {
			$table = $this->db->getTableSchema($tableName);
			if (isset($table->columns['archived'])) {
				$this->dropColumn($tableName,'archived');
			}
		}
    }
    
}
