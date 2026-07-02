<?php

namespace app\migrations\arms;

use app\helpers\ArrayHelper;
use yii\db\Migration;

/**
 * Базовый класс для миграций ARMS
 *
 * Предоставляет вспомогательные методы для безопасного выполнения операций с БД,
 * проверяя существование объектов перед их созданием или удалением.
 */
class ArmsMigration extends Migration
{
	/**
	 * Стандарт кодировки и коллации проекта — единая точка.
	 *
	 * Все таблицы держим в utf8mb4 / utf8mb4_unicode_ci. Если коллацию не
	 * фиксировать явно, сервер подставляет свой дефолт (MySQL 9 —
	 * utf8mb4_0900_ai_ci, MariaDB 11 — utf8mb4_uca1400_ai_ci, старые дампы —
	 * *_general_ci / utf8mb3_*), коллации становятся разнородными и поисковые
	 * CONCAT-выражения падают с ошибкой 1267 "Illegal mix of collations".
	 * Консистентность стережёт tests/unit/db/CollationConsistencyTest.
	 */
	const CHARSET   = 'utf8mb4';
	const COLLATION = 'utf8mb4_unicode_ci';

	/**
	 * Достраивает опции CREATE TABLE до стандарта проекта (InnoDB + utf8mb4 +
	 * utf8mb4_unicode_ci). Явно заданные значения не перетираются.
	 *
	 * @param string|null $options Исходные опции (например 'engine=InnoDB')
	 * @return string
	 */
	public function tableOptions($options = null)
	{
		$options = (string)$options;
		if (stripos($options, 'engine') === false)
			$options = 'ENGINE=InnoDB ' . $options;
		if (stripos($options, 'charset') === false && stripos($options, 'character set') === false)
			$options .= ' DEFAULT CHARSET=' . static::CHARSET;
		if (stripos($options, 'collate') === false)
			$options .= ' COLLATE=' . static::COLLATION;
		return trim($options);
	}

	/**
	 * {@inheritdoc}
	 *
	 * Любая таблица, создаваемая через миграции ARMS, по умолчанию получает
	 * стандартные движок/кодировку/коллацию, чтобы коллации не "уплывали"
	 * вслед за дефолтом сервера.
	 */
	public function createTable($table, $columns, $options = null)
	{
		parent::createTable($table, $columns, $this->tableOptions($options));
	}

	/**
	 * Приводит существующую таблицу к стандартной кодировке/коллации проекта.
	 * По аналогии с convertTableToInnoDb() — для нормализующих миграций.
	 *
	 * @param string $table Имя таблицы
	 * @return void
	 * @noinspection SqlResolve
	 */
	public function convertTableToCollation($table)
	{
		$this->db->createCommand(
			"ALTER TABLE `$table` CONVERT TO CHARACTER SET " . static::CHARSET . " COLLATE " . static::COLLATION
		)->execute();
	}

	/**
	 * Добавляет колонку в таблицу, если она еще не существует
	 *
	 * @param string $table Имя таблицы
	 * @param string $column Имя колонки
	 * @param string $type Тип данных колонки
	 * @param bool $index Создавать ли индекс для колонки (по умолчанию false)
	 * @return void
	 */
	function addColumnIfNotExists($table, $column, $type, $index=false)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (!isset($tableSchema->columns[$column])) {
			$this->addColumn($table,$column,$type);
			if ($index) $this->createIndex("idx-$table-$column",$table,$column);
			
		}
	}
	
	/**
	 * Удаляет колонку из таблицы, если она существует
	 *
	 * @param string $table Имя таблицы
	 * @param string $column Имя колонки для удаления
	 * @return void
	 */
	function dropColumnIfExists($table, $column)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->columns[$column])) {
			$this->dropColumn($table,$column);
		}
	}
	
	/**
	 * Удаляет внешний ключ (foreign key), если он существует
	 *
	 * @param string $name Имя внешнего ключа
	 * @param string $table Имя таблицы
	 * @return void
	 */
	function dropFkIfExists($name, $table)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->foreignKeys[$name])) {
			$this->dropForeignKey($name,$table);
		}
	}
	
	/**
	 * Проверяет существование таблицы в базе данных
	 *
	 * @param string $table Имя таблицы
	 * @return bool True, если таблица существует, иначе false
	 */
	function tableExists($table)
	{
		$tableSchema = $this->db->getTableSchema($table);
		return !is_null($tableSchema);
	}
	
	/**
	 * Удаляет таблицу, если она существует
	 *
	 * @param string $table Имя таблицы для удаления
	 * @return void
	 */
	function dropTableIfExists($table)
	{
		if ($this->tableExists($table))
			$this->dropTable($table);
	}
	
	/**
	 * Проверяет существование индекса в таблице
	 *
	 * @param string $name Имя индекса
	 * @param string $table Имя таблицы
	 * @return bool True, если индекс существует, иначе false
	 */
	function indexExists($name,$table) {
		$command = $this->getDb()->createCommand('show index from '.$table);
		$tableIndexes = $command->queryAll();
		return count(ArrayHelper::findByField($tableIndexes,'Key_name',$name));
	}
	
	/**
	 * Получает статус таблицы (информацию о движке, кодировке и т.д.)
	 *
	 * @param string $table Имя таблицы
	 * @return array|false Массив с информацией о таблице или false при ошибке
	 */
	public function getTableStatus($table) {
		$command = $this->getDb()->createCommand("show table status where Name = '$table'");
		return $command->queryOne();
	}
	
	/**
	 * Удаляет индекс, если он существует
	 *
	 * @param string $name Имя индекса
	 * @param string $table Имя таблицы
	 * @return void
	 */
	function dropIndexIfExists($name, $table)
	{
		if ($this->indexExists($name,$table))
			$this->dropIndex($name,$table);
	}
	
	/**
	 * Создает таблицу связи многие-ко-многим (Many-to-Many)
	 *
	 * Автоматически создает таблицу с двумя внешними ключами, индексами и уникальным составным индексом.
	 * Поддерживает добавление дополнительных полей в таблицу связи.
	 *
	 * @param string $tableName Имя создаваемой таблицы связи (например, 'maintenance_reqs_in_techs')
	 * @param array $links Массив связей в формате ['field_id'=>'table_name'] или просто ['field1_id', 'field2_id']
	 *                     Пример: ['techs_id'=>'techs', 'reqs_id'=>'maintenance_reqs']
	 * @param array $additionalFields Дополнительные поля для таблицы (по умолчанию пустой массив)
	 * @return void
	 */
	function createMany2ManyTable(string $tableName, array $links,$additionalFields=[])
	{
		//если у нас числовые ключи
		if (isset($links[0])) {
			$keys=$links;
			$tables=null;
		} else {
			$keys=array_keys($links);
			$tables=array_values($links);
		}
		$this->dropTableIfExists($tableName);
		$this->createTable($tableName,array_merge([
			'id'=>$this->primaryKey(),
			$keys[0]=>$this->integer(),
			$keys[1]=>$this->integer(),
		],$additionalFields),'engine=InnoDB');
		$this->createIndex($tableName.'-'.$keys[0],$tableName,$keys[0]);
		$this->createIndex($tableName.'-'.$keys[1],$tableName,$keys[1]);
		$this->createIndex($tableName.'-m2m',$tableName,$keys,true);
		
		//для генератора моделей полезно чтобы были ссылки в БД
		if (is_array($tables)) {
			$this->addForeignKey(
				'fk-'.$tableName.'-'.$keys[0],
				$tableName,
				$keys[0],
				$tables[0],
				'id'
			);
			$this->addForeignKey(
				'fk-'.$tableName.'-'.$keys[1],
				$tableName,
				$keys[1],
				$tables[1],
				'id'
			);
		}
	}
	
	
	/**
	 * Конвертирует таблицу в движок InnoDB, если она использует другой движок
	 *
	 * Проверяет текущий движок таблицы и выполняет конвертацию только при необходимости.
	 * InnoDB обеспечивает поддержку транзакций и внешних ключей.
	 *
	 * @param string $table Имя таблицы для конвертации
	 * @return void
	 * @noinspection SqlResolve
	 */
	public function convertTableToInnoDb($table) {
		$status=$this->getTableStatus($table);
		if (strtolower($status['Engine']??'')!=='innodb') {
			$this->db->createCommand("alter table `$table` engine = InnoDB")->execute();
		}
	}

}
