<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Пытался сделать другое изменение, ругнулось что у меня тут столбец не в порядке. Вот типа привел в порядок
 * Class m191103_085902_alter_updatetAt_column_to_arms_table
 */
class m191103_084000_alter_updatetAt_column_to_arms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn(
			'{{%arms}}',
			'{{%updated_at}}',
			$this->timestamp()
				->defaultExpression('CURRENT_TIMESTAMP')
				->append('ON UPDATE CURRENT_TIMESTAMP')
		);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->alterColumn(
		    '{{%arms}}',
		    '{{%updated_at}}',
		    $this->timestamp()
			    ->defaultValue(null)
	    );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191103_085902_alter_updatetAt_column_to_arms_table cannot be reverted.\n";

        return false;
    }
    */
}
