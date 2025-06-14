<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Database extends \Codeception\Module
{
	public static function createDatabase($managementDb, $dbName)
	{
		codecept_debug("Creating $dbName test database...");
		$managementDb->open();
		$managementDb->createCommand("CREATE DATABASE IF NOT EXISTS $dbName")->execute();
		$managementDb->close();
		codecept_debug('Complete');
	}
	
	public static function dropDatabase($managementDb, $dbName, $connection=null)
	{
		codecept_debug("Dropping $dbName test database...");
		if ($connection) $connection->close(); // Закрываем подключение к основной БД, чтобы не было конфликтов
		$managementDb->open();
		$managementDb->createCommand("DROP DATABASE IF EXISTS $dbName")->execute();
		$managementDb->close();
		codecept_debug('Complete');
	}
	
	/**
	 * Вытаскивает имя БД из DSN строки
	 * @param $dsn
	 * @return string
	 */
	public static function getDbName($dsn)
	{
		if (preg_match('/(?:dbname|database)=([^;]+)/i', $dsn, $matches)) {
			return $matches[1];
		}
		return '';
	}
	
	public static function prepareYiiDb()
	{
		$managementDb=\Yii::$app->get('db_root');
		if (!$managementDb) {
			throw new \RuntimeException('Не удалось получить подключение к управляющей БД (db_root)');
		}
		$dbName=static::getDbName(\Yii::$app->db->dsn);
		if (!$dbName) {
			throw new \RuntimeException('Не удалось получить имя БД из DSN: ' . \Yii::$app->db->dsn);
		}
		static::createDatabase($managementDb,$dbName);
	}
	
	public static function dropYiiDb()
	{
		$managementDb=\Yii::$app->get('db_root');
		if (!$managementDb) {
			throw new \RuntimeException('Не удалось получить подключение к управляющей БД (db_root)');
		}
		$dbName=static::getDbName(\Yii::$app->db->dsn);
		if (!$dbName) {
			throw new \RuntimeException('Не удалось получить имя БД из DSN: ' . \Yii::$app->db->dsn);
		}
		static::dropDatabase($managementDb,$dbName, \Yii::$app->db);
	}
	
	/**
	 * парсит SQL-дамп с поддержкой DELIMITER
	 * @param string $filePath Путь к SQL-файлу
	 */
	public static function parseSqlDump($filePath) {
		$content = file_get_contents($filePath);
		$commands = [];
		$currentDelimiter = ';';
		$buffer = '';
		$lines = explode("\n", $content);
		
		foreach ($lines as $line) {
			// Пропускаем комментарии и пустые строки
			if (preg_match('/^\s*(--|#)/', $line) || trim($line) === '') {
				continue;
			}
			
			// Обрабатываем директиву DELIMITER
			if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $matches)) {
				$currentDelimiter = $matches[1];
				continue;
			}
			
			$buffer .= $line . "\n";
			
			// Если найден текущий разделитель (не внутри строк)
			if (preg_match('/' . preg_quote($currentDelimiter, '/') . '\s*$/i', $line)) {
				$command = trim($buffer);
				if (!empty($command)) {
					$sql = preg_replace('/' . preg_quote($currentDelimiter, '/') . '\s*$/i', '', $command);
					$commands[] = $sql;
				}
				$buffer = '';
			}
		}
		return $commands;
	}

	public static  function loadSqlDump($fileName)
	{
		$commands=static::parseSqlDump($fileName);
		// Выполняем все команды по порядку
		foreach ($commands as $sql) {
			try {
				// Удаляем сам разделитель из SQL-команды
				$sql = trim($sql);
				
				if (!empty($sql)) {
					\Yii::$app->db->createCommand($sql)->execute();
					codecept_debug("Выполнено: " . substr($sql, 0, 50) . "..." );
				}
			} catch (\yii\db\Exception $e) {
				codecept_debug("Ошибка в запросе: " . $e->getMessage());
				codecept_debug("SQL: " . substr($sql, 0, 200));
			}
		}
		
	}
}
