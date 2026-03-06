# Подготовка тестовых данных

## Назначение

Наполнение тестовой БД консистентными данными, достаточными для всестороннего тестирования. Для этого в тестовой БД должны быть заполнены несколько экземпляров каждого типа сущностей и настроены связи между ними.

## Database Helper

Класс [`tests/_support/Helper/Database.php`](tests/_support/Helper/Database.php) - хелпер управления тестовой БД. Использует дополнительный БД компонент db_root - доступ к management-командам БД (CREATE/DROP DATABASE) через разрешения root-пользователя.

### Основные методы

| Метод | Назначение |
|-------|------------|
| `prepareYiiDb()` | Создаёт пустую тестовую БД через компонент `db_root` |
| `dropYiiDb()` | Удаляет тестовую БД после тестов |
| `loadSqlDump($fileName)` | Загружает SQL-дамп в БД |
| `parseSqlDump($filePath)` | Парсит SQL с поддержкой `DELIMITER` |

### Загрузка arms_demo.sql

```php
Helper\Database::prepareYiiDb();
Helper\Database::loadSqlDump(__DIR__ . '/../_data/arms_demo.sql');
```

Путь: [`tests/_data/arms_demo.sql`](tests/_data/arms_demo.sql) (~646 KB)

### Конфигурация MySQL (config/test-web.php)

```php
// Тестовая БД
'db' => ['dsn'=>'mysql:host=127.0.0.1;dbname=arms_test', 'username'=>'arms_user' 'password'=>'arms_password']
// Management-соединение (CREATE/DROP DATABASE)
'db_root' => ['dsn'=>'mysql:host=127.0.0.1', ...]
```