<?php

namespace app\migrations\arms;

use app\helpers\ArrayHelper;
use yii\db\Migration;
use yii\helpers\StringHelper;

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
	 * Формат строк InnoDB, при котором индекс по varchar(255) utf8mb4 (1020 байт)
	 * помещается в лимит: DYNAMIC/COMPRESSED дают 3072 байта, COMPACT/REDUNDANT —
	 * только 767 (ошибка 1709 "Index column size too large"). Старые дампы часто
	 * несут ROW_FORMAT=COMPACT явно, и ALTER его сохраняет.
	 */
	const ROW_FORMAT = 'DYNAMIC';

	/**
	 * Максимальная длина префикса utf8mb4-строки, помещающаяся в 767 байт —
	 * запасной вариант для индекса, если поднять ROW_FORMAT не удалось.
	 */
	const SHORT_KEY_PREFIX = 191;

	/**
	 * {@inheritdoc}
	 *
	 * Дочитывает результаты всех стейтментов запроса, а не только первого.
	 *
	 * Зачем: PDO для MySQL по умолчанию эмулирует prepare, поэтому строку с несколькими
	 * стейтментами ('SET ...; CREATE FUNCTION ...') отправляет одним запросом, а результат
	 * читает только у первого. Ошибка второго и последующих остается в непрочитанном
	 * rowset'е и до приложения не доходит: миграция рапортует об успехе, а объект БД
	 * не создан. Так на mysql:8 без log_bin_trust_function_creators потерялась ошибка 1419
	 * на CREATE FUNCTION getplacepath — миграции "прошли", а первый же экран с местами
	 * упал 500-й (plans/bugs20260820.md).
	 *
	 * Мультистейтменты в миграциях допустимы только через этот класс — стережёт
	 * tests/unit/db/MigrationSqlHygieneTest.
	 *
	 * @param string $sql
	 * @param array $params
	 * @throws \yii\db\Exception
	 * @see \tests\unit\db\ArmsMigrationExecuteTest
	 */
	public function execute($sql, $params = [])
	{
		$sqlOutput = $sql;
		if ($this->maxSqlOutputLength !== null) {
			$sqlOutput = StringHelper::truncate($sql, $this->maxSqlOutputLength, '[... hidden]');
		}

		$time = $this->beginCommand("execute SQL: $sqlOutput");
		$command = $this->db->createCommand($sql)->bindValues($params);
		$command->execute();
		$this->drainRowsets($command, $sqlOutput);
		$this->endCommand($time);
	}

	/**
	 * Дочитывает оставшиеся наборы результатов, чтобы ошибки хвостовых стейтментов
	 * всплыли исключением, а не потерялись.
	 *
	 * @param \yii\db\Command $command выполненная команда
	 * @param string $sqlOutput запрос для сообщения об ошибке
	 * @throws \yii\db\Exception
	 */
	protected function drainRowsets($command, $sqlOutput)
	{
		$statement = $command->pdoStatement;
		if (!($statement instanceof \PDOStatement)) return;

		try {
			while ($statement->nextRowset()) {}
		} catch (\PDOException $e) {
			//в сообщении PDO нет самого запроса - без него непонятно, что именно упало
			throw new \yii\db\Exception(
				'Ошибка в одном из стейтментов запроса: ' . $e->getMessage() . "\nSQL: " . $sqlOutput,
				$e->errorInfo,
				$e->getCode(),
				$e
			);
		}
	}

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
	 * Сперва гарантирует InnoDB: у MyISAM максимальная длина ключа — 1000 байт,
	 * и составные индексы, укладывавшиеся в лимит на utf8 (3 байта/символ),
	 * могут его превысить на utf8mb4 (4 байта/символ) — ALTER CONVERT упадёт
	 * с ошибкой 1071 "Specified key was too long".
	 *
	 * @param string $table Имя таблицы
	 * @return void
	 * @noinspection SqlResolve
	 */
	public function convertTableToCollation($table)
	{
		$this->convertTableToInnoDb($table);
		$this->convertTableToDynamicRowFormat($table);
		$this->db->createCommand(
			"ALTER TABLE `$table` CONVERT TO CHARACTER SET " . static::CHARSET . " COLLATE " . static::COLLATION
		)->execute();
	}

	/**
	 * Поднимает формат строк InnoDB до DYNAMIC, если таблица лежит в COMPACT или
	 * REDUNDANT (типично для таблиц из старых дампов, где ROW_FORMAT записан явно).
	 *
	 * Зачем: в COMPACT/REDUNDANT индексируемая колонка ограничена 767 байтами, а
	 * varchar(255) в utf8mb4 занимает 1020 — любой UNIQUE/KEY по такой колонке
	 * падает с ошибкой 1709 "Index column size too large". В DYNAMIC лимит 3072.
	 *
	 * Не критично: на серверах без поддержки DYNAMIC (MySQL 5.6 с форматом файлов
	 * Antelope) ALTER молча оставит COMPACT либо бросит ошибку — в обоих случаях
	 * миграция продолжается, а вызывающий код должен уметь откатиться на префиксный
	 * индекс (см. SHORT_KEY_PREFIX).
	 *
	 * @param string $table Имя таблицы
	 * @return bool Удалось ли получить формат DYNAMIC/COMPRESSED
	 * @noinspection SqlResolve
	 */
	public function convertTableToDynamicRowFormat($table)
	{
		$isRoomy = function () use ($table) {
			$status = $this->getTableStatus($table);
			return in_array(strtolower($status['Row_format'] ?? ''), ['dynamic', 'compressed']);
		};

		if ($isRoomy()) return true;

		try {
			$this->db->createCommand("ALTER TABLE `$table` ROW_FORMAT=" . static::ROW_FORMAT)->execute();
		} catch (\Throwable $e) {
			echo "    > не удалось поднять ROW_FORMAT у $table: " . $e->getMessage() . "\n";
			return false;
		}

		return $isRoomy();
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
