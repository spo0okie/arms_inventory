<?php

use yii\db\Migration;

/**
 * Class m221209_180857_create_tables_dynagrid
 */
class m230302_180857_create_tables_dynagrid extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_null($table = $this->db->getTableSchema('ui_dynagrid')))
			$this->createTable('ui_dynagrid',[
				'id'=>$this->string(100)->notNull()->comment('Unique dynagrid setting identifier'),
				'filter_id'=>$this->string(100)->comment('Filter setting identifier'),
				'sort_id'=>$this->string(100)->comment('Sort setting identifier'),
				'data'=>$this->string(5000)->defaultValue(null)->comment('Json encoded data for the dynagrid configuration'),
				'PRIMARY KEY(id)'
			]);
		
		if (is_null($table = $this->db->getTableSchema('ui_dynagrid_dtl')))
			$this->createTable('ui_dynagrid_dtl',[
				'id'=>$this->string(100)->notNull()->comment('Unique dynagrid detail setting identifier'),
				'category'=>$this->string(10)->notNull()->comment('Dynagrid detail setting category "filter" or "sort"'),
				'name'=>$this->string(150)->notNull()->comment('Name to identify the dynagrid detail setting'),
				'data'=>$this->string(5000)->defaultValue(null)->comment('Json encoded data for the dynagrid detail configuration'),
				'dynagrid_id'=>$this->string(100)->notNull()->comment('Related dynagrid identifier'),
				'PRIMARY KEY(id)',
				'UNIQUE KEY `tbl_dynagrid_dtl_UK1` (`name`,`category`,`dynagrid_id`)'
			],'DEFAULT CHARSET=utf8');
		
		if (is_null($table = $this->db->getTableSchema('ui_tables_cols'))) {
			$this->createTable('ui_tables_cols',[
				'[[id]]' => $this->primaryKey(),
				'[[table]]' => $this->string(64),
				'[[column]]' => $this->string(32),
				'[[user_id]]' => $this->integer(),
				'[[value]]' => $this->string(255),
			]);
			$this->createIndex("idx-ui_tables_cols-table",'ui_tables_cols','table');
			$this->createIndex("idx-ui_tables_cols-column",'ui_tables_cols','column');
			$this->createIndex("idx-ui_tables_cols-user_id",'ui_tables_cols','user_id');
		}
	
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (!is_null($table = $this->db->getTableSchema('ui_dynagrid_dtl')))
			$this->dropTable('ui_dynagrid_dtl');
		
		if (!is_null($table = $this->db->getTableSchema('ui_dynagrid')))
			$this->dropTable('ui_dynagrid');
		
		if (!is_null($table = $this->db->getTableSchema('ui_tables_cols')))
			$this->dropTable('ui_tables_cols');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221209_180857_create_tables_dynagrid cannot be reverted.\n";

        return false;
    }
    */
}
