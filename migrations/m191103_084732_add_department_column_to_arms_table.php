<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Добавляем в АРМах ссылку на подразделения
 * Handles adding columns to table `{{%arms}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%department}}`
 */
class m191103_084732_add_department_column_to_arms_table extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$table=$this->db->getTableSchema('{{%arms}}');

		if (!isset($table->columns['departments_id'])) {
			$this->addColumn(
				'{{%arms}}',    //правим АРМы
				'departments_id', //добавпляем подразделения
				$this->integer()      //ссылка на ключ
				->defaultValue(null)    //а иначе будут нули, а у нас внешний ключ
				->after('{{%it_staff_id}}') //не в конец а примерно туда, где у нас прикрепляются все владельцы
			);
			// creates index for column `departments`
			$this->createIndex(
				'{{%idx-arms-department}}',
				'{{%arms}}',
				'departments_id'
			);
		}
		
		// add foreign key for table `{{%department}}`
		$this->addForeignKey(
			'{{%fk-arms-department}}',
			'{{%arms}}',
			'departments_id',
			'{{%departments}}',
			'id',
			'RESTRICT'
		);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		
		$table=$this->db->getTableSchema('{{%arms}}');
		
		// drops foreign key for table `{{%department}}`
		if (isset($table->foreignKeys['fk-arms-department'])) $this->dropForeignKey(
			'{{%fk-arms-department}}',
			'{{%arms}}'
		);
		
		
		if (isset($table->columns['department_id'])) $this->dropColumn('{{%arms}}', 'department_id');
	}
}
