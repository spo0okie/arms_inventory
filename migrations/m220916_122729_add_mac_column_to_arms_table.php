<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%arms}}`.
 */
class m220916_122729_add_mac_column_to_arms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		Yii::$app->urlManager->scriptUrl='https://localhost';
		$table = $this->db->getTableSchema('{{%arms}}');
		if (!isset($table->columns['mac'])){
			$this->addColumn('{{%arms}}', 'mac', $this->string()->null());
		}
		
        foreach ($arms=\app\models\OldArms::find()->all() as $arm) {
			/**
			 * @var $arm \app\models\OldArms
			 */
			if (is_object($arm->comp)){
				$arm->mac=$arm->comp->mac;
			} elseif (count($comps=$arm->comps)) {
				foreach ($comps as $comp) {
					if (!strlen($arm->mac) && strlen($comp->mac)) {
						$arm->mac=$comp->mac;
					}
				}
			}
			
			if (strlen($arm->mac))
				$arm->save(false);
		};
    
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table = $this->db->getTableSchema('{{%arms}}');
		if (isset($table->columns['mac']))
	        $this->dropColumn('{{%arms}}', 'mac');
    }
}
