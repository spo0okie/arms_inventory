<?php

use yii\db\Migration;

/**
 * Class m221122_151334_alter_table_ports
 */
class m221122_151334_alter_table_ports extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		//добавляем возможность привязывать порты к АРМ (а почему нет то)
		$table = $this->db->getTableSchema('ports');
		if (!isset($table->columns['arms_id'])) {
			$this->addColumn('ports','arms_id',$this->integer()->null()->after('techs_id'));
			$this->createIndex('ports_arms_id','ports','arms_id');
		}
		
	
		if (isset($table->foreignKeys['fk-ports_link_arms']))
			$this->dropForeignKey('fk-ports_link_arms','ports');
		if (isset($table->foreignKeys['fk-ports_link_tech']))
			$this->dropForeignKey('fk-ports_link_tech','ports');
	
	
		if (isset($table->foreignKeys['fk-ports_tech']))
			$this->dropForeignKey('fk-ports_tech','ports');

		$this->alterColumn('ports','techs_id',$this->integer()->null());
	
		//Добавляем линки на все старые порты связанные напрямую с Tech или Arm
		foreach (\app\models\Ports::find()->all() as $model) {
			/**
			 * @var $model \app\models\Ports
			 */
			
			//если он связан напрямую с оборудованием - делаем порт этому оборудованию с именем порта Null
			if ($model->link_techs_id) {
				$linkedPort=new \app\models\Ports();
				$linkedPort->techs_id=$model->link_techs_id;
				$linkedPort->link_ports_id=$model->id;
				echo "linking ".$model->id." to new port with tech ".$model->link_techs_id." \n";
				$linkedPort->save(false);
			}
			if ($model->link_arms_id) {
				$linkedPort=new \app\models\Ports();
				$linkedPort->arms_id=$model->link_arms_id;
				$linkedPort->link_ports_id=$model->id;
				echo "linking ".$model->id." to new port with arm ".$model->link_arms_id." \n";
				$linkedPort->save(false);
			}
		}
		
		//убираем старые столбцы привязки напрямую к АРМ без указания порта
		if (isset($table->columns['link_arms_id']))
			$this->dropColumn('ports','link_arms_id');
		
		if (isset($table->columns['link_techs_id']))
			$this->dropColumn('ports','link_techs_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		//убираем старые столбцы привязки напрямую к АРМ без указания порта
		$this->addColumn('ports','link_arms_id',$this->integer()->null());
		$this->addColumn('ports','link_techs_id',$this->integer()->null());
		$this->createIndex('ports_link_arms_id','ports','link_arms_id');
		$this->createIndex('ports_link_techs_id','ports','link_techs_id');
	
		foreach (\app\models\Ports::find()->all() as $model) {
			//если он связан напрямую с оборудованием - делаем порт этому оборудованию с именем порта Null
			if (empty($model->name) && !empty($model->arms_id) && !empty($model->link_ports_id)) {
				$linkedPort=$model->linkPort;
				$linkedPort->link_arms_id=$model->arms_id;
				$linkedPort->link_ports_id=null;
				$linkedPort->save(false);
				$model->delete();
			}
		}
	
		$this->dropColumn('ports','arms_id');
		$this->alterColumn('ports','techs_id',$this->integer()->notNull());
	}

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221122_151334_alter_table_ports cannot be reverted.\n";

        return false;
    }
    */
}
