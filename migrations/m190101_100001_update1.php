<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Добавление статуса документам
 */
class m190101_100001_update1 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		
		if (is_null($table=$this->db->getTableSchema('contracts_states'))) $this->createTable('contracts_states',[
			'id'    => $this->primaryKey()->comment('id'),
			'code'  => $this->string(64)->notNull()->unique()->comment('Код')->append(' COLLATE utf8mb4_unicode_ci'),
			'name'  => $this->string(128)->notNull()->unique()->comment('Наименование')->append(' COLLATE utf8mb4_unicode_ci'),
			'descr'=>$this->text()->comment('Описание')->append(' COLLATE utf8mb4_unicode_ci')
		],'ENGINE=InnoDB');
		
		$table=$this->db->getTableSchema('{{%contracts}}');

		if (!isset($table->columns['state_id'])) {
			$this->addColumn(
				'{{%contracts}}',    //правим АРМы
				'state_id', //добавляем подразделения
				$this->integer()      //ссылка на ключ
				->defaultValue(null)    //а иначе будут нули, а у нас внешний ключ
			);
			// creates index for column `departments`
			$this->createIndex(
				'{{%idx-contracts-state}}',
				'{{%contracts}}',
				'state_id'
			);
		}
		
		// add foreign key for table `{{%department}}`
		$this->addForeignKey(
			'{{%fk-contracts-state}}',
			'{{%contracts}}',
			'state_id',
			'{{%contracts_states}}',
			'id',
			'RESTRICT'
		);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		
		$table=$this->db->getTableSchema('{{%contracts}}');
		
		// drops foreign key for table `{{%department}}`
		if (isset($table->foreignKeys['fk-contracts-state'])) $this->dropForeignKey(
			'{{%fk-contracts-state}}',
			'{{%contracts}}'
		);
		
		
		if (isset($table->columns['state_id_id'])) $this->dropColumn('{{%contracts}}', 'state_id');

		if (!is_null($table=$this->db->getTableSchema('contracts_states'))) $this->dropTable('contracts_states');
		
	}
}
