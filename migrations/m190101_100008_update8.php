<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Разделение на несколько организаций
 */
class m190101_100008_update8 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$table=$this->db->getTableSchema('{{%org_struct}}');
		
		if (!isset($table->columns['org_id'])) {
			//добавляем ссылку на организацию в оргструктуре
			$this->addColumn('{{%org_struct}}', 'org_id', $this->integer()->notNull()->defaultValue(1)->comment('Организация')->after('id'));
			$this->dropPrimaryKey('PRIMARY', 'org_struct');
			$this->addPrimaryKey('', 'org_struct', ['id', 'org_id']);
		}
		
		$table=$this->db->getTableSchema('{{%users}}');
		if (!isset($table->columns['employee_id'])) {
			//добавляем отдельное поле табельный в пользователях
			$this->addColumn('{{%users}}', 'employee_id', $this->string(16)->notNull()->comment('Табельный номер')->after('id')->append('COLLATE utf8mb4_unicode_ci'));
			$this->createIndex('{{%idx-users-employee_id}}', '{{%users}}', 'employee_id');
		}
		
		if (!isset($table->columns['org_id'])) {
			//добавляем ссылку на организацию в пользователях
			$this->addColumn('{{%users}}', 'org_id', $this->integer()->notNull()->defaultValue(1)->comment('Организация')->after('employee_id'));
			$this->createIndex('{{%idx-users-org_id}}', '{{%users}}', 'org_id');
		}
		
		if (!isset($table->columns['employ_date'])) {
			//добавляем дату приема
			$this->addColumn('{{%users}}', 'employ_date', $this->string(16)->Null()->comment('Дата приема')->after('manager_id')->append('COLLATE utf8mb4_unicode_ci'));
		}
		if (!isset($table->columns['resign_date'])) {
			//добавляем дату приема
			$this->addColumn('{{%users}}', 'resign_date', $this->string(16)->Null()->comment('Дата увольнения')->after('employ_date')->append('COLLATE utf8mb4_unicode_ci'));
		}
		
		$table=$this->db->getTableSchema('{{%login_journal}}');
		if (isset($table->foreignKeys['login_journal_ibfk_1'])) $this->dropForeignKey('{{%login_journal_ibfk_1}}','{{%login_journal}}');
		if (isset($table->foreignKeys['login_journal_ibfk_2'])) $this->dropForeignKey('{{%login_journal_ibfk_2}}','{{%login_journal}}');
		
		/** @noinspection SqlWithoutWhere */
		$sql=<<<SQL
			SET FOREIGN_KEY_CHECKS=0;
			SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
			SET AUTOCOMMIT = 0;
			
			START TRANSACTION;

			-- проставляем табельные номера
			UPDATE `users` SET users.`employee_id`=`id`,users.org_id=1;
			
			-- нумеруем ID
			UPDATE `users` SET id = (SELECT @a:= @a + 1 FROM (SELECT @a:= 0) as tbl);

			-- подменяем ID в таблице ARMS
			UPDATE `arms` SET user_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`arms`.`user_id` limit 1);
			UPDATE `arms` SET it_staff_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`arms`.`it_staff_id` limit 1);
			UPDATE `arms` SET head_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`arms`.`head_id` limit 1);
			UPDATE `arms` SET responsible_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`arms`.`responsible_id` limit 1);

			-- журнал входов
			UPDATE `login_journal` SET users_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`login_journal`.`users_id` limit 1);

			-- группы пользователей
			UPDATE `users_in_groups` SET users_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`users_in_groups`.`users_id` limit 1);

			-- материалы
			UPDATE `materials` SET it_staff_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`materials`.`it_staff_id` limit 1);

			-- подменяем ID в таблице TECHS
			UPDATE `techs` SET user_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`techs`.`user_id` limit 1);
			UPDATE `techs` SET it_staff_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`techs`.`it_staff_id` limit 1);

			COMMIT;
			SET FOREIGN_KEY_CHECKS=1;
SQL;
		$this->execute($sql);

		//меняем тип ключа в пользователях на классику (вместо табельного)
		$this->alterColumn('users','id',$this->integer(16)->notNull());
		$this->alterColumn('arms','user_id',$this->integer(16)->notNull());
		$this->alterColumn('arms','it_staff_id',$this->integer(16)->notNull());
		$this->alterColumn('arms','head_id',$this->integer(16)->notNull());
		$this->alterColumn('arms','responsible_id',$this->integer(16)->notNull());
		$this->alterColumn('login_journal','users_id',$this->integer(16)->notNull());
		$this->alterColumn('users_in_groups','users_id',$this->integer(16)->notNull());
		$this->alterColumn('materials','it_staff_id',$this->integer(16)->notNull());
		$this->alterColumn('techs','user_id',$this->integer(16)->notNull());
		$this->alterColumn('techs','it_staff_id',$this->integer(16)->notNull());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->alterColumn('users','id',$this->string(16)->notNull());
		$this->alterColumn('arms','user_id',$this->string(16)->notNull());
		$this->alterColumn('arms','it_staff_id',$this->string(16)->notNull());
		$this->alterColumn('arms','head_id',$this->string(16)->notNull());
		$this->alterColumn('arms','responsible_id',$this->string(16)->notNull());
		$this->alterColumn('login_journal','users_id',$this->string(16)->notNull());
		$this->alterColumn('users_in_groups','users_id',$this->string(16)->notNull());
		$this->alterColumn('materials','it_staff_id',$this->string(16)->notNull());
		$this->alterColumn('techs','user_id',$this->string(16)->notNull());
		$this->alterColumn('techs','it_staff_id',$this->string(16)->notNull());
		
		/** @noinspection SqlResolve */
		/** @noinspection SqlWithoutWhere */
		$sql=<<<SQL
			SET FOREIGN_KEY_CHECKS=0;
			SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
			SET AUTOCOMMIT = 0;
			
			START TRANSACTION;
			
			-- подменяем ID в таблице ARMS
			UPDATE `arms` SET user_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);
			UPDATE `arms` SET it_staff_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);
			UPDATE `arms` SET head_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);
			UPDATE `arms` SET responsible_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);

			-- журнал входов
			UPDATE `login_journal` SET users_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);

			-- группы пользователей
			UPDATE `users_in_groups` SET users_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);

			-- материалы
			UPDATE `materials` SET it_staff_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);

			-- подменяем ID в таблице TECHS
			UPDATE `techs` SET user_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);
			UPDATE `techs` SET it_staff_id=(SELECT employee_id FROM `users` WHERE `users`.`id`=`arms`.`user_id` limit 1);

			-- проставляем табельные номера - что интересно обратное действие лучше не делать, если у нас уже 2 организации или более
			UPDATE `users` SET users.`id`=`employee_id`;

			COMMIT;
			SET FOREIGN_KEY_CHECKS=1;
SQL;
		$this->execute($sql);

		//вот это вот тоже хз как пройдет если уже есть совпадения между организациями
		$table=$this->db->getTableSchema('{{%org_struct}}');
		
		if (isset($table->columns['org_id'])) {
			$this->dropPrimaryKey('PRIMARY', 'org_struct');
			$this->addPrimaryKey('', 'org_struct', ['id']);
		}
		
		$table=$this->db->getTableSchema('{{%users}}');
		
		if (isset($table->columns['org_id']))
			$this->dropColumn('users', 'org_id');
		if (isset($table->columns['employee_id']))
			$this->dropColumn('users', 'employee_id');
		if (isset($table->columns['employ_date']))
			$this->dropColumn('users', 'employ_date');
		if (isset($table->columns['resign_date']))
			$this->dropColumn('users', 'resign_date');
		

		$this->dropColumn('org_struct','org_id');
	}

	
}
