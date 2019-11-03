<?php

use yii\db\Migration;

/**
 * Class m191101_192502_departments
 * Создает таблицу отделов
 */
class m191101_192502_departments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_null($table=$this->db->getTableSchema('departments'))) $this->createTable('departments',[
    		'id'    => $this->primaryKey()->comment('id'),
		    'name'  => $this->string(64)->notNull()->unique()->comment('Подразделение'),
		    'comment'=>$this->text()->comment('Комментарии')
	    ],'ENGINE=InnoDB');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    if (!is_null($table=$this->db->getTableSchema('departments'))) $this->dropTable('departments');

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191101_192502_departments cannot be reverted.\n";

        return false;
    }
    */
}
