<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210814_125020_create_table_access
 */
class m210825_125020_create_table_access extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_null($table = $this->db->getTableSchema('access_types'))) {
			$this->createTable('access_types', [
				'[[id]]' => $this->primaryKey(),
				'[[code]]' => $this->string(64),
				'[[name]]' => $this->string(64),
				'[[comment]]' => $this->string(255),
				'[[notepad]]' => $this->text(),
			]);
			
		}
	
		//ACEs
		if (is_null($table = $this->db->getTableSchema('aces'))) {
			$this->createTable('aces', [
				'[[id]]' => $this->primaryKey(),
				'[[acls_id]]' => $this->integer()->null(),
				'[[ips]]' => $this->text(),
				'[[comment]]' => $this->string(255),
				'[[notepad]]' => $this->text(),
			]);
			$this->createIndex('{{%idx-aces_acl_id}}', '{{%aces}}', '[[acls_id]]');
			// add foreign key for table `{{%acls}}`
			
		}
		if (is_null($table = $this->db->getTableSchema('access_in_aces'))) {
			$this->createTable('access_in_aces', [
				'[[id]]' => $this->primaryKey(),
				'[[access_types_id]]' => $this->integer()->notNull(),
				'[[aces_id]]' => $this->integer()->notNull(),
			]);
			$this->createIndex('{{%idx-access_in_aces_ace_id}}', '{{%access_in_aces}}', '[[aces_id]]');
			$this->createIndex('{{%idx-access_in_aces_access_id}}', '{{%access_in_aces}}', '[[access_types_id]]');
		}
		if (is_null($table = $this->db->getTableSchema('users_in_aces'))) {
			$this->createTable('users_in_aces', [
				'[[id]]' => $this->primaryKey(),
				'[[users_id]]' => $this->integer()->notNull(),
				'[[aces_id]]' => $this->integer()->notNull(),
			]);
			$this->createIndex('{{%idx-users_in_aces_ace_id}}', '{{%users_in_aces}}', '[[aces_id]]');
			$this->createIndex('{{%idx-users_in_aces_user_id}}', '{{%users_in_aces}}', '[[users_id]]');
		}
		if (is_null($table = $this->db->getTableSchema('ips_in_aces'))) {
			$this->createTable('ips_in_aces', [
				'[[id]]' => $this->primaryKey(),
				'[[ips_id]]' => $this->integer()->notNull(),
				'[[aces_id]]' => $this->integer()->notNull(),
			]);
			$this->createIndex('{{%idx-ips_in_aces_ace_id}}', '{{%ips_in_aces}}', '[[aces_id]]');
			$this->createIndex('{{%idx-ips_in_aces_ip_id}}', '{{%ips_in_aces}}', '[[ips_id]]');
		}
		if (is_null($table = $this->db->getTableSchema('comps_in_aces'))) {
			$this->createTable('comps_in_aces', [
				'[[id]]' => $this->primaryKey(),
				'[[comps_id]]' => $this->integer()->notNull(),
				'[[aces_id]]' => $this->integer()->notNull(),
			]);
			$this->createIndex('{{%idx-comps_in_aces_ace_id}}', '{{%comps_in_aces}}', '[[aces_id]]');
			$this->createIndex('{{%idx-comps_in_aces_comp_id}}', '{{%comps_in_aces}}', '[[comps_id]]');
		}
		
		//ACLs
		if (is_null($table = $this->db->getTableSchema('acls'))) {
			$this->createTable('acls', [
				'[[id]]' => $this->primaryKey(),
				'[[schedules_id]]' => $this->integer()->null(),
				'[[services_id]]' => $this->integer()->null(),
				'[[ips_id]]' => $this->integer()->null(),
				'[[comps_id]]' => $this->integer()->null(),
				'[[techs_id]]' => $this->integer()->null(),
				'[[comment]]' => $this->string(255),
				'[[notepad]]' => $this->text(),
			]);
			
			$this->createIndex('{{%idx-acls_schedule_id}}','{{%acls}}', '[[schedules_id]]');
			$this->createIndex('{{%idx-acls_service_id}}','{{%acls}}', '[[services_id]]');
			$this->createIndex('{{%idx-acls_ip_id}}', 	'{{%acls}}', '[[ips_id]]');
			$this->createIndex('{{%idx-acls_comp_id}}',	'{{%acls}}', '[[comps_id]]');
			$this->createIndex('{{%idx-acls_tech_id}}',	'{{%acls}}', '[[techs_id]]');
			// add foreign key for table `{{%acls}}`
			$this->addForeignKey(
				'{{%fk-acls-schedule}}',
				'{{%acls}}',
				'schedules_id',
				'{{%schedules}}',
				'id',
				'RESTRICT'
			);
			$this->addForeignKey(
				'{{%fk-acls-service}}',
				'{{%acls}}',
				'services_id',
				'{{%services}}',
				'id',
				'RESTRICT'
			);
			$this->addForeignKey(
				'{{%fk-acls-ip}}',
				'{{%acls}}',
				'ips_id',
				'{{%net_ips}}',
				'id',
				'RESTRICT'
			);
			$this->addForeignKey(
				'{{%fk-acls-comp}}',
				'{{%acls}}',
				'comps_id',
				'{{%comps}}',
				'id',
				'RESTRICT'
			);
			$this->addForeignKey(
				'{{%fk-acls-tech}}',
				'{{%acls}}',
				'techs_id',
				'{{%techs}}',
				'id',
				'RESTRICT'
			);
			$this->addForeignKey(
				'{{%fk-aces-acl}}',
				'{{%aces}}',
				'acls_id',
				'{{%acls}}',
				'id',
				'RESTRICT'
			);
			
		}
		
		//в связи с тем что расписание теперь может называться "расписание предоставления прав Пупкину В.А. на доступ к
		//копьютеру Лоханикина В.В." или чего-то в этом роде - увеличиваем максимальную длину поля name
		$this->alterColumn('schedules','name',$this->string(255)->notNull());
		
		$defaults=[
			'Полный'=>'full',
			'Чтение'=>'read',
			'Запись'=>'write',
		];
		foreach ($defaults as $name=>$code) {
			$this->execute("insert into access_types (code,name) values (:code,:name)",[':code'=>$code,':name'=>$name]);
		}
	}

    
    
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (!is_null($table = $this->db->getTableSchema('users_in_aces'))) {
			$this->dropTable('users_in_aces');
		}
		if (!is_null($table = $this->db->getTableSchema('ips_in_aces'))) {
			$this->dropTable('ips_in_aces');
		}
		if (!is_null($table = $this->db->getTableSchema('comps_in_aces'))) {
			$this->dropTable('comps_in_aces');
		}
		if (!is_null($table = $this->db->getTableSchema('aces'))) {
			$this->dropForeignKey('{{%fk-aces-acl}}','aces');
			$this->dropTable('aces');
		}
		if (!is_null($table = $this->db->getTableSchema('access_types'))) {
			$this->dropTable('access_types');
		}
		if (!is_null($table = $this->db->getTableSchema('acls'))) {
			$this->dropForeignKey('{{%fk-acls-schedule}}','acls');
			$this->dropForeignKey('{{%fk-acls-service}}','acls');
			$this->dropForeignKey('{{%fk-acls-ip}}','acls');
			$this->dropForeignKey('{{%fk-acls-comp}}','acls');
			$this->dropForeignKey('{{%fk-acls-tech}}','acls');
			$this->dropTable('acls');
		}
		$this->alterColumn('schedules','name',$this->string(32)->notNull());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210814_125020_create_table_access cannot be reverted.\n";

        return false;
    }
    */
}
