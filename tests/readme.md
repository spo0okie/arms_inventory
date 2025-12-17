# Тесты проекта ARMS

## Структура тестов

Проект использует **Codeception** для автоматизированного тестирования на базе Yii2.

## Типы тестов

### Unit тесты (`unit/`)
Модульные тесты для изолированного тестирования моделей и компонентов.

**Примеры:**
- [`TagsTest.php`](unit/models/TagsTest.php) - тесты модели Tags (создание, валидация, slug, цвета)
- [`TagsSearchTest.php`](unit/models/TagsSearchTest.php) - тесты поиска и фильтрации тегов

### Functional тесты (`functional/`)
Функциональные тесты контроллеров и взаимодействия компонентов.

**Примеры:**
- [`TagsControllerCest.php`](functional/TagsControllerCest.php) - тесты контроллера Tags (CRUD операции, валидация)

### Acceptance тесты (`acceptance/`)
Функциональные тесты доступности страниц через браузер.

**Основные тесты:**
- [`PageAccessCest.php`](acceptance/PageAccessCest.php) - автоматическая проверка всех маршрутов приложения (GET/POST)
- [`HistoryPagesCest.php`](acceptance/HistoryPagesCest.php) - проверка страниц истории изменений моделей

**Extra тесты:** (`acceptance-extra/`)
Дополнительные acceptance тесты с расширенной конфигурацией.

### REST API тесты (`rest/`)
Тестирование REST API эндпоинтов.

### Миграционные тесты (`migrations/`)
Проверка корректности миграций БД.

## Конфигурация

### Test Suites
- `unit.suite.yml` - модульные тесты (использует `config/test-console.php`)
- `functional.suite.yml` - функциональные тесты (использует `config/test-web.php`)
- `acceptance.suite.yml` - основные acceptance тесты
- `rest.suite.yml` - API тесты
- `migrations.suite.yml` - тесты миграций
- `acceptance-extra.suite.yml` - расширенные acceptance тесты

### Helpers (`_support/Helper/`)
- `Acceptance.php` - хелпер для acceptance тестов
- `Database.php` - управление тестовой БД
- `Yii2.php` - инициализация Yii2 приложения
- `Rest.php` - хелпер для REST API
- `Migrations.php` - хелпер для миграций

## Запуск тестов

```bash
# Все тесты
vendor/bin/codecept run

# Unit тесты
vendor/bin/codecept run unit

# Functional тесты
vendor/bin/codecept run functional

# Acceptance тесты
vendor/bin/codecept run acceptance

# REST API тесты
vendor/bin/codecept run rest

# Конкретный тест
vendor/bin/codecept run unit models/TagsTest
```

## Тестовые данные

- `_data/arms_demo.sql` - демо-дамп БД для тестов
- `_data/get-routes-data.php` - конфигурация маршрутов для тестирования

## Особенности

- Автоматическое создание/удаление тестовой БД
- Проверка всех контроллеров и actions
- Валидация форм и моделей
- Тестирование истории изменений
