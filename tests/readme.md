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

```text
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
| ---------- | ---------- | ------- |
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
php vendor/bin/codecept run

# Конкретный тип тестов
php vendor/bin/codecept run unit
php vendor/bin/codecept run acceptance
php vendor/bin/codecept run acceptance-extra
php vendor/bin/codecept run functional
php vendor/bin/codecept run rest
php vendor/bin/codecept run migrations

# Конкретный тест
php vendor/bin/codecept run unit models/TagsTest
php vendor/bin/codecept run acceptance PageAccessCest
php vendor/bin/codecept run rest CompsCest

# С детализацией
php vendor/bin/codecept run unit --debug

# Сокращенный вывод (удобно для ИИ. только ошибки)
php vendor/bin/codecept run --ext dotReporter
php vendor/bin/codecept run acceptance --ext dotReporter

# Покрытие кода
php vendor/bin/codecept run --coverage
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
