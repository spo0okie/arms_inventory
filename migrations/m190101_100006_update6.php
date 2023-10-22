<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Добавление материалов
 */
class m190101_100006_update6 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		
		
		if (is_null($table = $this->db->getTableSchema('materials_types'))) {
			$this->createTable('materials_types', [
				'id' => $this->primaryKey()->comment('id'),
				'code' => $this->string(12)->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'name' => $this->string(128)->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'units' => $this->string(16)->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'comment' => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
			$this->createIndex(
				'{{%idx-materials_types-code}}',
				'{{%materials_types}}',
				'code'
			);
			
			$this->createIndex(
				'{{%idx-materials_types-name}}',
				'{{%materials_types}}',
				'name'
			);
			
		}
		
		if (is_null($table = $this->db->getTableSchema('materials'))) {
			$this->createTable('materials', [
				'id' => $this->primaryKey()->comment('id'),
				'parent_id' => $this->integer()->Null()->comment('источник'),
				'date' => $this->date()->notNull()->comment('Дата поступления'),
				'count' => $this->integer()->notNull()->comment('Количество'),
				'type_id' => $this->integer()->notNull()->comment('тип материалов'),
				'model' => $this->string(128)->Null()->comment('наименование')->append(' COLLATE utf8mb4_unicode_ci'),
				'places_id' => $this->integer()->Null()->comment('помещение'),
				'it_staff_id' => $this->integer()->Null(),
				'comment' => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'history' => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
		
			$this->createIndex(
				'{{%idx-materials-it_staff_id}}',
				'{{%materials}}',
				'it_staff_id'
			);
			
			$this->createIndex(
				'{{%idx-materials-places_id}}',
				'{{%materials}}',
				'places_id'
			);
			
			$this->createIndex(
				'{{%idx-materials-date}}',
				'{{%materials}}',
				'date'
			);
		}
		
		if (is_null($table=$this->db->getTableSchema('materials_usages'))) {
			$this->createTable('materials_usages',[
				'id'    => $this->primaryKey()->comment('id'),
				'materials_id'  => $this->integer()->notNull()->comment('Материал'),
				'count'  => $this->integer()->notNull()->comment('Количество'),
				'date'  => $this->date()->notNull()->comment('Дата расхода'),
				'arms_id'  => $this->integer()->Null()->comment('АРМ'),
				'techs_id'  => $this->integer()->Null()->comment('Оборудование'),
				'comment'=>$this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'history'=>$this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
			],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
		
			$this->createIndex(
				'{{%idx-materials_usages-materials_id}}',
				'{{%materials_usages}}',
				'materials_id'
			);
			
			$this->createIndex(
				'{{%idx-materials_usages-arms_id}}',
				'{{%materials_usages}}',
				'arms_id'
			);
			
			$this->createIndex(
				'{{%idx-materials_usages-techs_id}}',
				'{{%materials_usages}}',
				'techs_id'
			);
			
		}
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		if (!is_null($table=$this->db->getTableSchema('materials_usages'))) $this->dropTable('materials_usages');
		if (!is_null($table=$this->db->getTableSchema('materials'))) $this->dropTable('materials');
		if (!is_null($table=$this->db->getTableSchema('materials_types'))) $this->dropTable('materials_types');
	}
}
