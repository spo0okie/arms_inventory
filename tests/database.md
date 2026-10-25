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

Дамп — канонический источник схемы для тестов: **CI миграции не применяет**.
Появились миграции со схемой — регенерируйте дамп по инструкции
[`tests/_data/readme.md`](tests/_data/readme.md).

### Когда импортировать дамп

Дамп загружается **один раз на набор**, до построения data provider. Наборы
можно запускать отдельно; каждый начинает с канонической схемы и демо-данных.
Один импорт на весь процесс недостаточен: acceptance/REST меняют данные через
HTTP без общего отката, а migrations пересоздаёт и в конце удаляет БД.

| Набор | Место импорта | Зачем нужен дамп |
|-------|---------------|-----------------|
| unit | `unit/_bootstrap.php` | Схема, справочники и демо-связи для существующих тестов; генеративные тесты создают собственные модели |
| acceptance | `acceptance/_bootstrap.php` | Демо-история для `HistoryPagesCest`, записи и роли для `AuthorizationModesCest`; CRUD-модели создаёт `ModelFactory` |
| acceptance-extra | `acceptance-extra/_bootstrap.php` | Проверки оборудования по фиксированным ID 18, 1, 3 |
| rest | `RestAccessCest::routesProvider()` | Чистая исходная БД после других наборов; провайдер строит собственные CRUD-данные |
| migrations | Импорта нет | Проверяется применение всей цепочки миграций к пустой БД |

Повторные импорты удалены из `ModelGenerationTest`, `ModelTypeSafetyTest`,
`DeterministicGenerationTest`, `HelpCoverageTest`, `ApiSchemaResolvableTest`,
`AttributeTypeBasicsTest` и провайдеров acceptance. Проверки метаданных и типов
не требуют восстановления данных; генеративные сценарии создают модели сами.
Изменяющие общие данные тесты должны откатывать транзакцию или удалять свои
записи, а не восстанавливать всю БД перед следующим сценарием.

`arms_demo.sql` содержит `DROP DATABASE`/`CREATE DATABASE`, поэтому повторный
`dropYiiDb()` перед ним не нужен. Однако PDO сначала открывает соединение с
`dbname=arms_test`: если предыдущий набор удалил БД, до команд дампа оно не
дойдёт. Поэтому общий `loadSqlDump()` вызывает `prepareYiiDb()` до импорта.
В migrations создание и удаление БД сохранены: там дампа нет. Кэш схемы в обоих
тестовых конфигах выключен, чтобы после пересоздания БД не читать старые столбцы.
В CI отдельный предварительный импорт не нужен: БД готовит unit bootstrap.

### Конфигурация MySQL (config/test-web.php)

```php
// Тестовая БД
'db' => ['dsn'=>'mysql:host=127.0.0.1;dbname=arms_test', 'username'=>'arms_user' 'password'=>'arms_password']
// Management-соединение (CREATE/DROP DATABASE)
'db_root' => ['dsn'=>'mysql:host=127.0.0.1', ...]
```