# Тесты проекта ARMS

Документация по тестовой инфраструктуре системы автоматизированного тестирования ARMS.

---

## 1. Обзор тестовой инфраструктуры

### Фреймворк
- **Codeception** — основан на PHPUnit
- Версия PHP: >=8.1
- Точка входа: [`test.bat`](test.bat) (Windows) или `vendor/bin/codecept run` (Linux/macOS)

### Технологический стек
- PHPUnit ядро для assertions
- Yii2 интеграция через `yii2-codeception`
- Selenium WebDriver для acceptance тестов (опционально)

---

## 2. Структура тестов

```
tests/
├── unit/              # Unit тесты - изолированное тестирование моделей
├── acceptance/        # Acceptance тесты - функциональное тестирование через браузер
├── acceptance-extra/  # Расширенные acceptance тесты
├── functional/        # Функциональные тесты - тестирование контроллеров
├── rest/              # REST API тесты
├── migrations/        # Тесты миграций БД
├── _data/             # Тестовые данные (SQL, конфигурации)
├── _support/          # Helpers и базовые классы
└── bin/               # Yii2 консоль для тестов
```

### Описание типов тестов

| Директория | Назначение | Примеры |
|------------|------------|---------|
| [`unit/`](unit/) | Модульные тесты моделей и компонентов | TagsTest, TagsSearchTest |
| [`acceptance/`](acceptance/) | Функциональные тесты страниц через браузер | PageAccessCest, HistoryPagesCest |
| [`acceptance-extra/`](acceptance-extra/) | Расширенные acceptance тесты | PageAccessCest |
| [`functional/`](functional/) | Функциональные тесты контроллеров | — |
| [`rest/`](rest/) | REST API эндпоинты | BaseCrudCest, CompsCest |
| [`migrations/`](migrations/) | Тесты миграций БД | MigrationTest |

---

## 3. Как запускать тесты

### Основные команды

```bash
# Все тесты (через test.bat)
test.bat

# Все тесты (Linux/macOS)
vendor/bin/codecept run

# Конкретный тип тестов
vendor/bin/codecept run unit
vendor/bin/codecept run acceptance
vendor/bin/codecept run acceptance-extra
vendor/bin/codecept run functional
vendor/bin/codecept run rest
vendor/bin/codecept run migrations

# Конкретный тест
vendor/bin/codecept run unit models/TagsTest
vendor/bin/codecept run acceptance PageAccessCest
vendor/bin/codecept run rest CompsCest

# С детализацией
vendor/bin/codecept run unit --debug

# Покрытие кода
vendor/bin/codecept run --coverage
```

### Сокращённые команды

```bash
# Только unit тесты
vendor/bin/codecept run unit

# Только acceptance тесты
vendor/bin/codecept run acceptance

# Только REST API
vendor/bin/codecept run rest
```

---

## 4. Конфигурация

### Основные конфигурационные файлы

| Файл | Назначение |
|------|------------|
| [`codeception.yml`](codeception.yml) | Основная конфигурация Codeception |
| [`config/test-web.php`](config/test-web.php) | Конфигурация для веб-тестов (acceptance, functional, rest) |
| [`config/test-console.php`](config/test-console.php) | Конфигурация для консольных тестов (unit, migrations) |

### Suite конфигурации

| Suite | Конфигурация | Применение |
|-------|--------------|------------|
| unit | [`unit.suite.yml`](unit.suite.yml) | `config/test-console.php` |
| functional | [`functional.suite.yml`](functional.suite.yml) | `config/test-web.php` |
| acceptance | [`acceptance.suite.yml`](acceptance.suite.yml) | `config/test-web.php` |
| acceptance-extra | [`acceptance-extra.suite.yml`](acceptance-extra.suite.yml) | `config/test-web.php` |
| rest | [`rest.suite.yml`](rest.suite.yml) | `config/test-web.php` |
| migrations | [`migrations.suite.yml`](migrations.suite.yml) | `config/test-console.php` |

### Особенности конфигурации test-web.php

- Подавление deprecated warnings для PHP 8.2: `error_reporting(E_ALL & ~E_DEPRECATED)`
- Тестовая БД: `arms_test` (mysql:host=127.0.0.1)
- Отключена CSRF валидация: `enableCsrfValidation => false`
- Debug модуль включён

---

## 5. Существующие тесты

### Unit тесты

| Тест | Файл | Описание |
|------|------|----------|
| TagsTest | [`unit/models/TagsTest.php`](unit/models/TagsTest.php) | Тесты модели Tags: создание, валидация, генерация slug, цвета |
| TagsSearchTest | [`unit/models/TagsSearchTest.php`](unit/models/TagsSearchTest.php) | Тесты поиска и фильтрации тегов |

### Acceptance тесты

Описание вынесено в отдельный файл: [acceptance.md](acceptance.md)

### REST API тесты

| Тест | Файл | Описание |
|------|------|----------|
| BaseCrudCest | [`rest/BaseCrudCest.php`](rest/BaseCrudCest.php) | Базовые CRUD операции для REST API |
| CompsCest | [`rest/CompsCest.php`](rest/CompsCest.php) | Тестирование API компьютеров |

### Миграционные тесты

| Тест | Файл | Описание |
|------|------|----------|
| MigrationTest | [`migrations/MigrationTest.php`](migrations/MigrationTest.php) | Проверка корректности миграций БД |

---

## 6. Тестовые данные

### SQL дампы

| Файл | Описание |
|------|----------|
| [`_data/arms_demo.sql`](_data/arms_demo.sql) | [Демо-дамп БД для тестов (~630KB)](database.md) |
| [`_data/get-routes-data.php`](_data/get-routes-data.php) | Конфигурация маршрутов для тестирования |

### Helpers

| Helper | Файл | Назначение |
|--------|------|------------|
| Database | [`_support/Helper/Database.php`](_support/Helper/Database.php) | Управление тестовой БД |
| Yii2 | [`_support/Helper/Yii2.php`](_support/Helper/Yii2.php) | Инициализация Yii2 приложения |
| Rest | [`_support/Helper/Rest.php`](_support/Helper/Rest.php) | Хелпер для REST API тестов |
| Migrations | [`_support/Helper/Migrations.php`](_support/Helper/Migrations.php) | Хелпер для миграций |
| ModelData | [`_support/Helper/ModelData.php`](_support/Helper/ModelData.php) | Генерация тестовых данных моделей |

---

## 7. Узнайте больше о модуле Schedules

### Назначение модуля

Проверяйте работоспособность контроллеров модуля через браузер:

- **Доступность страниц**: Проверка доступности всех действий контроллеров (index, view, create, update, delete)
- **Динамическое обнаружение**: Тесты автоматически включают контроллеры модуля благодаря наследованию от `ArmsBaseController`
- **Параметризация**: Использование макросов `{anyId}`, `{anyName}` для подстановки реальных данных из БД
- **Конфигурация**: Маршруты модуля настраиваются в [`tests/_data/get-routes-data.php`](../tests/_data/get-routes-data.php)

**Примеры маршрутов для тестирования:**

```php
// tests/_data/get-routes-data.php
'/^schedules\/index$/' => ['GET' => []],
'/^schedules\/view$/' => ['GET' => ['id' => '{anyId}']],
'/^schedules\/create$/' => ['GET' => [], 'POST' => [...]],
'/^schedules\/update$/' => ['GET' => ['id' => '{anyId}'], 'POST' => [...]],
'/^schedules\/delete$/' => ['POST' => ['id' => '{anyId}']],
'/^schedules-entries\/create$/' => ['GET' => [], 'POST' => [...]],
'/^schedules-entries\/update$/' => ['GET' => ['id' => '{anyId}'], 'POST' => [...]],
'/^schedules-entries\/delete$/' => ['POST' => ['id' => '{anyId}']],
'/^scheduled-access\/index$/' => ['GET' => []],
'/^scheduled-access\/view$/' => ['GET' => ['id' => '{anyId}']],
'/^scheduled-access\/status$/' => ['GET' => ['id' => '{anyId}']],
```

#### 2. Unit тесты

Тестирование отдельных компонентов модуля:

- **TimeIntervalsHelper**: Математика временных интервалов (слияние, вычитание, пересечение)
- **Модели**: Валидация, вычисляемые поля, бизнес-логика расписаний
- **Граничные случаи**: Переход через полночь, иерархия расписаний, перекрытия

**Рекомендуемые тесты:**

```php
// tests/unit/helpers/TimeIntervalsHelperTest.php
- testMergeIntervals()           // Слияние пересекающихся интервалов
- testSubtractIntervals()        // Вычитание интервалов
- testIntersectIntervals()       // Пересечение интервалов
- testMidnightCrossing()         // Интервалы через полночь

// tests/unit/models/SchedulesTest.php
- testScheduleValidation()       // Валидация расписания
- testScheduleHierarchy()        // Иерархия (parent_id)
- testScheduleOverride()         // Перекрытия (override_id)
- testGetStatus()                // Определение текущего статуса

// tests/unit/models/SchedulesEntriesTest.php
- testEntryValidation()          // Валидация записи
- testTimeIntervalsCalculation() // Вычисление интервалов
```

#### 3. REST API тесты

Тестирование API эндпоинтов модуля (если реализованы):

- **CRUD операции**: GET, POST, PUT, DELETE
- **Фильтрация**: Поиск по параметрам
- **Валидация**: Проверка ошибок при некорректных данных

**Примеры эндпоинтов:**

```
GET    /api/schedules              # Список расписаний
GET    /api/schedules/{id}         # Просмотр расписания
POST   /api/schedules              # Создание расписания
PUT    /api/schedules/{id}         # Обновление расписания
DELETE /api/schedules/{id}         # Удаление расписания
GET    /api/schedules-entries      # Список записей
POST   /api/schedules-entries      # Создание записи
```

### Как запускать тесты для модуля

#### Все тесты модуля

```bash
# Через основной скрипт (все тесты проекта)
test.bat

# Или через Codeception
vendor/bin/codecept run
```

#### Acceptance тесты

```bash
# Все acceptance тесты (включая модуль Schedules)
vendor/bin/codecept run acceptance

# Конкретный тест
vendor/bin/codecept run acceptance PageAccessCest

# С детализацией
vendor/bin/codecept run acceptance --debug
```

#### Unit тесты

```bash
# Все unit тесты
vendor/bin/codecept run unit

# Только тесты модуля Schedules
vendor/bin/codecept run unit helpers/TimeIntervalsHelperTest
vendor/bin/codecept run unit models/SchedulesTest
```

#### REST API тесты

```bash
# Все REST API тесты
vendor/bin/codecept run rest

# Тесты модуля Schedules (если реализованы)
vendor/bin/codecept run rest SchedulesCest
```

#### Миграции модуля

```bash
# Тесты миграций (включая миграции модуля)
vendor/bin/codecept run migrations
```

### Интеграция с основным приложением

Модуль Schedules интегрирован в основное приложение и автоматически включается в тесты благодаря:

1. **Наследованию контроллеров**: Все контроллеры наследуют `ArmsBaseController`, что позволяет `PageAccessCest` автоматически их обнаруживать
2. **Наследованию моделей**: Все модели наследуют `ArmsModel`, что обеспечивает совместимость с тестовой инфраструктурой
3. **Миграциям БД**: Миграции модуля применяются при инициализации тестовой БД
4. **Тестовым данным**: Демо-данные в `arms_demo.sql` включают примеры расписаний

### Документация модуля

Подробная информация о модуле Schedules:

- **Основная документация**: [`modules/schedules/README.md`](../modules/schedules/README.md)
- **Схема БД**: [`modules/schedules/docs/database.md`](../modules/schedules/docs/database.md)
- **Детальная документация**: [`docs/SCHEDULES.md`](../docs/SCHEDULES.md)
- **История изменений**: [`modules/schedules/CHANGELOG.md`](../modules/schedules/CHANGELOG.md)

---

## 8. Известные проблемы и решения

### PHP 8.2 deprecated warnings

**Проблема:** PHP 8.2 генерирует deprecated warnings для некоторых функций Yii2.

**Решение:** Подавлены в [`config/test-web.php`](config/test-web.php):
```php
error_reporting(E_ALL & ~E_DEPRECATED);
```

### RBAC тесты

**Проблема:** Тесты связанные с RBAC могут пропускаться из-за сложности настройки прав.

**Решение:** Используется `@skip` аннотация для пропуска тестов:
```php
/**
 * @skip Тест требует настройки RBAC
 */
public function testRbacFeature(...)
```

### Проверка таблиц БД

**Проблема:** Тесты могут падать если таблицы не созданы.

**Решение:** 
- Убедиться что миграции применены: `tests/bin/yii migrate`
- Проверить существование таблиц в тестовой БД `arms_test`

### История БД (History Tables)

**Проблема:** Тесты истории могут падать если таблицы истории не существуют.

**Решение:** Проверка на существование таблиц перед тестированием:
```php
if (!$this->tester->grabColumn('information_schema.TABLES', 'TABLE_NAME', 
    ['TABLE_SCHEMA' => 'arms_test', 'TABLE_NAME' => 'arm_history'])) {
    $this->markTestSkipped('Таблица истории не существует');
}
```

---

## 9. Требования для запуска

### Системные требования

- PHP >= 8.1
- MySQL/MariaDB
- Composer

### Инфраструктура

1. **Веб-сервер:** localhost:8081
   ```bash
   # Запуск встроенного сервера
   php yii serve --docroot=@web --port=8081
   ```

2. **База данных:**
   - Имя: `arms_test`
   - Пользователь: `root`
   - Пароль: (пу умолчаниюстой по)
   - Хост: `127.0.0.1`

3. **Миграции:** Должны быть применены к БД `arms_test`
   ```bash
   tests/bin/yii migrate --db=db
   ```

### Установка зависимостей

```bash
composer install --dev
```

---

## 10. Рекомендации по расширению тестов

### Создание нового Unit теста

1. Создать файл в `tests/unit/models/`
2. Наследовать `Codeception\Test\Unit`
3. Использовать `$this->tester` для доступа к Yii2

```php
<?php
namespace app\tests\unit\models;

use Codeception\Test\Unit;
use app\models\MyModel;

class MyModelTest extends Unit
{
    public function testValidation()
    {
        $model = new MyModel();
        $model->name = '';
        $this->assertFalse($model->validate());
    }
}
```

### Создание Acceptance теста

1. Создать файл в `tests/acceptance/`
2. Использовать методы `$I->` для взаимодействия со страницей

```php
<?php
namespace app\tests\acceptance;

use AcceptanceTester;

class MyPageCest
{
    public function testPageLoads(AcceptanceTester $I)
    {
        $I->amOnPage('/my-controller/index');
        $I->see('Заголовок страницы');
    }
}
```

### Создание REST API теста

1. Создать файл в `tests/rest/`
2. Использовать методы API хелпера

```php
<?php
namespace app\tests\rest;

use ApiTester;

class MyApiCest
{
    public function testGetList(ApiTester $I)
    {
        $I->sendGET('/api/comps');
        $I->seeResponseCodeIs(200);
    }
}
```

### Добавление тестовых данных

1. Создать fixture класс в `_support/_data/fixtures/`
2. Загрузить данные через Database Helper

```php
// В тесте
$this->tester->haveInDatabase('table_name', [
    'field1' => 'value1',
    'field2' => 'value2',
]);
```

### Лучшие практики

1. **Именование:** Использовать `ModelNameTest` для unit, `ModelNameCest` для acceptance/rest
2. **Изоляция:** Каждый тест должен быть независимым
3. **Очистка:** Использовать `_before` и `_after` методы для setup/teardown
4. **Ассерты:** Использовать понятные сообщения об ошибках
5. **Пропуски:** Использовать `$this->markTestSkipped()` для нереализованных тестов
6. **Группировка:** Использовать `@group` для маркировки тестов

### Структура теста по группам

```
tests/
└── unit/
    └── models/
        ├── TagsTest.php         # Основные операции тегов
        ├── TagsSearchTest.php   # Поиск и фильтрация
        └── contracts/           # Контракты
            ├── ContractsTest.php
            └── ContractStatesTest.php
```

### Примеры расширения

- **Модели:** Добавить тесты для каждой доменной модели (Comps, Techs, Services...)
- **Контроллеры:** Тесты действий контроллеров (create, update, delete, view)
- **API:** Расширить покрытие REST эндпоинтов
- **Интеграция:** Тесты взаимодействия с внешними системами (LDAP, OpenAI)

---

## Ссылки

- [Документация Codeception](https://codeception.com/docs)
- [Yii2 Testing](https://www.yiiframework.com/doc/guide/2.0/en/test-overview)
- [PHPUnit Documentation](https://phpunit.readthedocs.io/)
