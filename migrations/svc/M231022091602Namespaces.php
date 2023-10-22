<?php

namespace app\migrations\svc;

use yii\db\Migration;

/**
 * Class M231022091602Namespaces
 */
class M231022091602Namespaces extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->execute("update migration set version=CONCAT('app\\\\migrations\\\\',version)");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "M231022091602Namespaces cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M231022091602Namespaces cannot be reverted.\n";

        return false;
    }
    */
}
