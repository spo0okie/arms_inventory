<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240308075641PlacesMap
 */
class M240308075641PlacesMap extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('places','map',$this->text());
		$this->addColumnIfNotExists('places','map_id',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('places','map');
		$this->dropColumnIfExists('places','map_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240308075641PlacesMap cannot be reverted.\n";

        return false;
    }
    */
}
