<?php
namespace app\migrations;
use Yii;
use yii\db\Migration;

/**
 * Handles adding columns to table `{{%arms}}`.
 */
class m220916_122729_add_mac_column_to_arms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		Yii::$app->urlManager->scriptUrl='https://localhost';
		$table = $this->db->getTableSchema('{{%arms}}');
		if (!isset($table->columns['mac'])){
			$this->addColumn('{{%arms}}', 'mac', $this->string()->null());
		}
	
		/** @noinspection SqlWithoutWhere */
		$this->execute('update `arms` inner join `comps` on `comps`.`id`=`arms`.`comp_id` set `arms`.`mac`=`comps`.`mac`');
    
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table = $this->db->getTableSchema('{{%arms}}');
		if (isset($table->columns['mac']))
	        $this->dropColumn('{{%arms}}', 'mac');
    }
}
