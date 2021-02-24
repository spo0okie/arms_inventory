<?php

use yii\db\Migration;

/**
 * Class m210223_130220_add_all_net_ips
 */
class m210223_130220_add_all_net_ips extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_array($comps=\app\models\Comps::find()->all())) {
			foreach ($comps as $comp) $comp->save();
		};
		if (is_array($techs=\app\models\Techs::find()->all())) {
			foreach ($techs as $tech) $tech->save();
		};
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (is_array($ips=\app\models\NetIps::find()->all())) {
			foreach ($ips as $ip) $ip->delete();
		};
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210223_130220_add_all_net_ips cannot be reverted.\n";

        return false;
    }
    */
}
