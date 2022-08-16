<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%services}}`.
 */
class m220816_104950_add_weight_column_to_services_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%services}}', 'weight', $this->integer()->notNull()->defaultValue(100));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%services}}', 'weight');
    }
}
