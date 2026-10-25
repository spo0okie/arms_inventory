# Тесты проекта ARMS

Документация по тестовой инфраструктуре системы автоматизированного тестирования ARMS.

---

## 1. Обзор тестовой инфраструктуры

### Фреймворк

- **Codeception** — основан на PHPUnit
- Версия PHP: >=8.1
- Точка входа: [`test.bat`](../test.bat) (Windows) или `php tests/bin/run.php` (Linux/macOS)

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

`test.bat` запускает `tests/bin/run.php`: скрипт поднимает отдельный PHP HTTP-сервер
на свободном порту, выполняет все наборы Codeception и завершает свой сервер.
Apache на :8081 для этого запуска не нужен. Аргументы передаются Codeception:
`test.bat unit`, `test.bat --xml=report.xml`, `test.bat --debug`.

По умолчанию дочерние процессы работают без Xdebug; явно заданный `XDEBUG_MODE`
сохраняется (например, `XDEBUG_MODE=coverage` для покрытия). На HTTP-сервере
включён OPcache; `web/index-test.php` инвалидирует `params-test.php` после смены
режима авторизации. Ошибки PHP, включая deprecated, не подавляются.

Для уже запущенного сервера задайте `ARMS_TEST_SERVER_URL`, например
`http://127.0.0.1:8081`: скрипт использует его и не останавливает. Прямой запуск
`php vendor/bin/codecept run` также поддерживается, но требует готового сервера
(по умолчанию `http://127.0.0.1:8081`).

В тестовом web-конфиге выключены toolbar и SQL backtrace; сессии хранятся в
`runtime/test-sessions`, опубликованные ресурсы — в `web/assets-test`. Это
исключает конфликты прав с файлами рабочего Apache. Схема БД не кэшируется:
наборы пересоздают `arms_test`, а migrations проверяет пустую БД.

Большие тела упавших HTTP-ответов остаются в `.fail.html`/`.fail.json`; расширение
`Helper\ResponseReport` выводит в консоли путь к полному ответу. Это не меняет
assertions, статусы и число падений: REST-модуль иначе добавляет весь HTML в
отчёт, и форматирование сотен страниц может длиться дольше самих тестов.
Короткие тела (до 4096 байт) остаются в консольном отчёте.

Обычные тесты работают в открытом режиме: `useRBAC=false`,
`authorizedView=false`. Для web/unit/acceptance/REST значения задаются в
`config/params-test.php`; консольный тестовый конфиг также явно отключает
наследование боевого режима авторизации из `params-local.php`.
`AuthorizationModesCest` временно меняет оба флага для своих HTTP-проверок и
восстанавливает исходный файл в `_after()`. Unit-проверки матрицы доступа
временно меняют `Yii::$app->params` и возвращают значения в `finally`.

### Основные команды

```bash
# Все тесты (через test.bat)
test.bat

# Все тесты с отдельным HTTP-сервером (Linux/macOS)
php tests/bin/run.php

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
| [`config/test-web.php`](config/test-web.php) | Конфигурация веб-приложения: acceptance, functional, rest **и unit** (unit-тесты гоняют web-приложение из CLI — см. §7) |
| [`config/test-console.php`](config/test-console.php) | Конфигурация консольного приложения (suite `migrations`, `tests/bin/yii`) |

### Suite конфигурации

| Suite | Конфигурация | Применение |
|-------|--------------|------------|
| unit | [`unit.suite.yml`](unit.suite.yml) | `config/test-web.php` |
| functional | [`functional.suite.yml`](functional.suite.yml) | `config/test-web.php` |
| acceptance | [`acceptance.suite.yml`](acceptance.suite.yml) | `config/test-web.php` |
| acceptance-extra | [`acceptance-extra.suite.yml`](acceptance-extra.suite.yml) | `config/test-web.php` |
| rest | [`rest.suite.yml`](rest.suite.yml) | `config/test-web.php` |
| migrations | [`migrations.suite.yml`](migrations.suite.yml) | `config/test-console.php` |

### Особенности конфигурации test-web.php

- `error_reporting(E_ALL)` — deprecated НЕ подавляются: тестовое окружение
  не мягче боевого, иначе full/empty-сценарии PageAccessCest пропускают
  депрекейты (strlen(null) и т.п.), которые на dev/prod роняют страницы
- Тестовая БД: `arms_test` (mysql:host=127.0.0.1)
- Отключена CSRF валидация: `enableCsrfValidation => false`
- Debug toolbar отключён; ошибки и предупреждения остаются включены

---

## 5. Существующие тесты

### Unit тесты
 
| Тест | Файл | Описание |
|------|------|----------|
| TagsTest | [`unit/models/TagsTest.php`](unit/models/TagsTest.php) | Тесты модели Tags: создание, валидация, генерация slug, цвета |
| TagsSearchTest | [`unit/models/TagsSearchTest.php`](unit/models/TagsSearchTest.php) | Тесты поиска и фильтрации тегов |
| ModelTypeSafetyTest | [`unit/models/ModelTypeSafetyTest.php`](unit/models/ModelTypeSafetyTest.php) | Проверяет что все safe атрибуты моделей, унаследованных от ArmsModel, имеют явно указанный тип в метаданных. Важно для обеспечения корректной работы генераторов моделей в acceptance тестах. |
| ApiSchemaResolvableTest | [`unit/types/ApiSchemaResolvableTest.php`](unit/types/ApiSchemaResolvableTest.php) | Энфорсер строгого резолвинга типов: перебирает все API-атрибуты (read-поверхность и `$searchFields` контроллеров) и падает списком, если у какого-то атрибута тип не выводится. Пришёл на смену удалённому `GeneratorResolver` (см. [`generation/generators/readme.md`](../generation/generators/readme.md)). |
| HelpCoverageTest | [`unit/help/HelpCoverageTest.php`](unit/help/HelpCoverageTest.php) | Сторож покрытия документацией: modelDescription() у моделей, подсказка (hint или inputHint типа) у safe атрибутов; baseline [`coverage-baseline.txt`](unit/help/coverage-baseline.txt) строгий в обе стороны. |
| HelpOrphanTest | [`unit/help/HelpOrphanTest.php`](unit/help/HelpOrphanTest.php) | Сторож привязки docs/help к коду: осиротевшие MD-страницы моделей/атрибутов/типов (после переименований) валят тест списком. |
| HelpLinksTest | [`unit/help/HelpLinksTest.php`](unit/help/HelpLinksTest.php) | Относительные ссылки и картинки в docs/help резолвятся в существующие файлы. |
| AttributeTooltipTest | [`unit/components/AttributeTooltipTest.php`](unit/components/AttributeTooltipTest.php) | Единый сборщик тултипов (ui-sources.md §0.1): состав блоков по режимам, чистота getAttributeHint, приоритет явного searchHint. |

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
| [`_data/demo-seed/`](_data/demo-seed/) | Сиды демо-наполнения дампа (`apply.sh` + `NN-*.sql`) |

**Дамп канонический: CI не применяет миграции, а грузит дамп как есть.**
Добавили миграцию со схемой — обязательно регенерируйте дамп по инструкции
[`_data/readme.md`](_data/readme.md) (там же: как накатить миграции на
тестовую БД локально через `tests/bin/yii`).

Дамп импортируется один раз на набор, до data provider: unit и acceptance —
в bootstrap, REST — в провайдере, acceptance-extra — в bootstrap. Повторно
загружать его в отдельных тестах не нужно; обоснование сохранённых импортов
и правила изоляции — в [database.md](database.md).

Дамп — ещё и БД демо-сайта: данные под новые возможности сеются скриптами
`_data/demo-seed/*.sql`, а не правками в БД руками. Правила, состав и то, что
намеренно не сеется, — в том же [`_data/readme.md`](_data/readme.md).

### Helpers

| Helper | Файл | Назначение |
|--------|------|------------|
| Database | [`_support/Helper/Database.php`](_support/Helper/Database.php) | Управление тестовой БД |
| Yii2 | [`_support/Helper/Yii2.php`](_support/Helper/Yii2.php) | Инициализация Yii2 приложения |
| Rest | [`_support/Helper/Rest.php`](_support/Helper/Rest.php) | Хелпер для REST API тестов |
| Migrations | [`_support/Helper/Migrations.php`](_support/Helper/Migrations.php) | Хелпер для миграций |
| ModelData | [`_support/Helper/ModelData.php`](_support/Helper/ModelData.php) | Генерация тестовых данных моделей |

---

Полный замер всех наборов и ограничения интерпретации результатов:
[performance.md](performance.md).

## 7. Подводные камни

### Сессия в unit-тестах: `Yii::$app->user->login()` падает только в полном прогоне

Unit-suite поднимает **веб**-приложение (`config/test-web.php`) в CLI-процессе, поэтому
`Yii::$app->user` — обычный `yii\web\User` с сессией. `login()` через `switchIdentity()`
открывает PHP-сессию → `session_set_cookie_params()`, а в CLI это работает лишь пока
ничего не выведено в stdout. В полном прогоне вывод предыдущих тестов уже сделал
`headers_sent() = true`, и PHP выдаёт варнинг

```text
[Warning] session_set_cookie_params(): Session cookie parameters cannot be changed
after headers have already been sent at vendor/yiisoft/yii2/web/Session.php:425
```

который `error_reporting(E_ALL)` (см. §4) превращает в ошибку теста.

Коварство в том, что **при запуске одного класса тест зелёный** — вывода ещё нет.
Выглядит как флак, воспроизводится только через `codecept run unit` целиком.

Лечение — отключить сессию у компонента `user` на время теста, тогда от
`login()/logout()` остаётся ровно `setIdentity()` (смена текущего пользователя
в памяти — обычно только это тесту и нужно):

```php
protected function _before()
{
    $this->originalEnableSession = Yii::$app->user->enableSession;
    Yii::$app->user->enableSession = false;
}

protected function _after()
{
    Yii::$app->user->enableSession = $this->originalEnableSession;
}
```

Готовый пример — [`unit/components/integrations/IntegrationsSmsTest.php`](unit/components/integrations/IntegrationsSmsTest.php).

Мокать сессию в `config/test-web.php` **нельзя**: этот конфиг общий с
[`web/index-test.php`](../web/index-test.php) и acceptance/rest, где сессия настоящая
и нужна для логина.

Проверки `Yii::$app->user->isGuest` сессию не трогают (`renewAuthStatus()` смотрит
`getHasSessionId()`, которого в CLI нет) — падает именно `login()`.
