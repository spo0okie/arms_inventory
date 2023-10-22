<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230622_170155_alter_table_comps
 */
class m230622_170155_alter_table_comps extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('comps','updated_at',$this->timestamp());
		$this->alterColumn('soft_hits','id', $this->integer().' NOT NULL AUTO_INCREMENT');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('soft_hits','id', $this->integer());
		$this->alterColumn('comps','updated_at',$this->timestamp()
			->defaultExpression('CURRENT_TIMESTAMP')
			->append('ON UPDATE CURRENT_TIMESTAMP')
		);
    }
}
