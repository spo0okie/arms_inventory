<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240802093936PartnersAliases
 */
class M240802093936PartnersAliases extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('partners','alias',$this->string(),true);
		$this->addColumnIfNotExists('attaches','partners_id',$this->integer(),true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('partners','alias');
		$this->dropColumnIfExists('attaches','partners_id');
    }
    
}
