<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M250425033845CompsSoftMediumtext
 */
class M250425033845CompsSoftMediumtext extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('comps', 'raw_soft', 'mediumtext');
		$this->alterColumn('comps_history', 'raw_soft', 'mediumtext');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('comps', 'raw_soft', $this->text());
		$this->alterColumn('comps_history', 'raw_soft', $this->text());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250425033845CompsSoftMediumtext cannot be reverted.\n";

        return false;
    }
    */
}
