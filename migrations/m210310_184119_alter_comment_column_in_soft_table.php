<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210310_184119_alter_comment_column_in_soft_table
 */
class m210310_184119_alter_comment_column_in_soft_table extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->alterColumn('soft', 'comment', $this->text());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->alterColumn('soft', 'comment', $this->string());
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m210310_184119_alter_comment_column_in_soft_table cannot be reverted.\n";

		return false;
	}
	*/
}
