<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M250505122356WikiCache
 */
class M250505122356WikiCache extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->createTable('wiki_cache', [
			'id' => $this->primaryKey(),
			'page' => $this->string()->notNull(),
			'dependencies' => $this->string(),
			'updated_at' => $this->dateTime(),
			'valid' => $this->boolean()->notNull()->defaultValue(1),
		]);

		$this->createIndex('idx-wiki_cache-page', 'wiki_cache', 'page');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTableIfExists('wiki_cache');

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250505122356WikiCache cannot be reverted.\n";

        return false;
    }
    */
}
