<?php

use yii\db\Migration;

/**
 * Class m200616_205619_alter_table_techs_format_mac
 */
class m200616_205619_alter_table_techs_format_mac extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		foreach (\app\models\Techs::find()->all() as $model) {
			$mac=preg_replace('/[^0-9a-f]/', '', mb_strtolower($model->mac));
			echo $mac;
			$model->mac=$mac;
			echo ($model->save(false))?"OK\n":"Err\n";
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200616_205619_alter_table_techs_format_mac cannot be reverted.\n";

        return false;
    }
    */
}
