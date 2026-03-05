# Schedules — модуль расписаний

## Назначение

Модуль управляет временными графиками работы различных сущностей системы ARMS. Расписание отвечает на вопрос «когда?» — когда предоставляется сервис, когда действует доступ, когда выполняется регламентная работа. Поддерживается иерархическое наследование расписаний (через `parent_id`) и механизм временных перекрытий (через `override_id`). Расписание на конкретный день формируется динамически с учётом дней недели, конкретных дат-исключений и периодов работы/отключения.

## Структура модуля

```
modules/schedules/
├── Module.php                          — точка входа Yii2-модуля
├── controllers/
│   ├── SchedulesController.php         — CRUD расписаний (providingMode: providing/support/job/working)
│   ├── SchedulesEntriesController.php  — CRUD записей расписания (дни/периоды)
│   └── ScheduledAccessController.php  — CRUD расписаний доступа (providingMode: acl)
├── models/
│   ├── Schedules.php                   — основная модель расписания
│   ├── SchedulesEntries.php            — модель записи расписания (день/период)
│   ├── SchedulesSearch.php             — поисковая модель для расписаний
│   ├── SchedulesEntriesSearch.php      — поисковая модель для записей
│   ├── SchedulesHistory.php            — модель истории изменений расписания
│   ├── SchedulesEntriesHistory.php     — модель истории изменений записей
│   ├── SchedulesAclSearch.php          — поисковая модель для расписаний доступа
│   └── traits/
│       ├── SchedulesModelCalcFieldsTrait.php        — вычисляемые поля и бизнес-логика Schedules
│       └── ScheduleEntriesModelCalcFieldsTrait.php  — вычисляемые поля SchedulesEntries
├── helpers/
│   └── TimeIntervalsHelper.php         — математика временных интервалов (слияние, вычитание, пересечение)
├── views/
│   ├── schedules/                      — шаблоны для SchedulesController
│   ├── schedules-entries/              — шаблоны для SchedulesEntriesController
│   └── scheduled-access/              — шаблоны для ScheduledAccessController
└── migrations/                         — 9 миграций БД (история создания таблиц)
```

## Модели

| Модель | Таблица БД | Назначение |
|--------|-----------|-----------|
| [`Schedules`](models/Schedules.php) | `schedules` | Заголовок расписания: название, период действия, иерархия, перекрытия |
| [`SchedulesEntries`](models/SchedulesEntries.php) | `schedules_entries` | Запись расписания: день недели / конкретная дата / период с временными интервалами |
| [`SchedulesSearch`](models/SchedulesSearch.php) | `schedules` | Поиск и фильтрация расписаний в GridView |
| [`SchedulesEntriesSearch`](models/SchedulesEntriesSearch.php) | `schedules_entries` | Поиск записей расписания |
| [`SchedulesAclSearch`](models/SchedulesAclSearch.php) | `schedules` | Поиск расписаний доступа (фильтр по `providingMode = acl`) |
| [`SchedulesHistory`](models/SchedulesHistory.php) | `schedules_history` | Журнал изменений расписаний |
| [`SchedulesEntriesHistory`](models/SchedulesEntriesHistory.php) | `schedules_entries_history` | Журнал изменений записей расписания |

## Связи с остальным приложением

### Модуль зависит от приложения

| Класс приложения | Как используется |
|-----------------|-----------------|
| `\app\models\ArmsModel` | Базовый класс для всех моделей модуля |
| `\app\controllers\ArmsBaseController` | Базовый класс для всех контроллеров модуля |
| `\app\models\Services` | Связь через `providing_schedule_id` и `support_schedule_id` |
| `\app\models\Acls` | Связь через `schedules_id` |
| `\app\models\MaintenanceJobs` | Связь через `schedules_id` |

### Приложение зависит от модуля

| Класс приложения | Поле / метод | Описание |
|-----------------|-------------|---------|
| `models/Services.php` | `providing_schedule_id` | FK → `schedules.id` — расписание предоставления сервиса |
| `models/Services.php` | `support_schedule_id` | FK → `schedules.id` — расписание поддержки сервиса |
| `models/Acls.php` | `schedules_id` | FK → `schedules.id` — расписание доступа |
| `models/MaintenanceJobs.php` | `schedules_id` | FK → `schedules.id` — график регламентных работ |
| `models/Users.php` | `getScheduledAccess()` | Получение расписаний доступа пользователя через `acls` |
| `models/traits/ServicesModelCalcFieldsTrait.php` | — | Использует `Schedules` для вычисления описаний сервиса |
| `modules/api/controllers/SchedulesController.php` | — | REST API для расписаний |

### Режимы применения расписания (`providingMode`)

Расписание может использоваться в разных контекстах. Режим влияет на текстовые описания:

| Режим | Контекст |
|-------|---------|
| `providing` | Время предоставления сервиса |
| `support` | Время поддержки сервиса |
| `acl` | Расписание доступа к ресурсам |
| `job` | График регламентных работ |
| `working` | Рабочее время |

## Конфигурация

### Параметр `schedulesTZShift`

В `config/params.php` (или `config/params-local.php`) должен быть задан параметр:

```php
'schedulesTZShift' => 18000, // сдвиг в секундах (UTC+5 = 5*3600 = 18000)
```

Используется в методах [`getStatus()`](models/traits/SchedulesModelCalcFieldsTrait.php) и [`actionStatus()`](controllers/ScheduledAccessController.php:60) для определения текущего рабочего времени с учётом часового пояса. Это глобальный сдвиг для всей системы — все расписания работают в одном часовом поясе.

## Маршруты

Модуль подключается в конфигурации приложения как `schedules`. Маршруты:

| URL | Контроллер / Действие | Описание |
|-----|-----------------------|---------|
| `GET /schedules` | `SchedulesController::actionIndex` | Список расписаний |
| `GET /schedules/view?id=N` | `SchedulesController::actionView` | Просмотр расписания |
| `GET /schedules/create` | `SchedulesController::actionCreate` | Форма создания |
| `POST /schedules/create` | `SchedulesController::actionCreate` | Сохранение нового расписания |
| `GET /schedules/update?id=N` | `SchedulesController::actionUpdate` | Форма редактирования |
| `POST /schedules/update?id=N` | `SchedulesController::actionUpdate` | Сохранение изменений |
| `POST /schedules/delete?id=N` | `SchedulesController::actionDelete` | Удаление расписания |
| `GET /schedules-entries/create` | `SchedulesEntriesController::actionCreate` | Добавление записи расписания |
| `POST /schedules-entries/create` | `SchedulesEntriesController::actionCreate` | Сохранение записи |
| `GET /schedules-entries/update?id=N` | `SchedulesEntriesController::actionUpdate` | Редактирование записи |
| `POST /schedules-entries/delete?id=N` | `SchedulesEntriesController::actionDelete` | Удаление записи |
| `GET /scheduled-access` | `ScheduledAccessController::actionIndex` | Список расписаний доступа |
| `GET /scheduled-access/view?id=N` | `ScheduledAccessController::actionView` | Просмотр расписания доступа |
| `POST /scheduled-access/create` | `ScheduledAccessController::actionCreate` | Создание расписания доступа (+ ACL) |
| `GET /scheduled-access/status?id=N` | `ScheduledAccessController::actionStatus` | Текущий статус (1/0) |

### Параметры создания расписания

При создании через `GET /schedules/create` поддерживаются query-параметры для автоматической привязки:

| Параметр | Описание |
|---------|---------|
| `attach_service=N` | Привязать как расписание предоставления сервиса |
| `support_service=N` | Привязать как расписание поддержки сервиса |
| `attach_job=N` | Привязать к регламентной работе |
| `override_id=N` | Создать как перекрытие для расписания N |

## Тесты

Тесты модуля запускаются через Codeception из корня проекта:

```bash
# Все unit-тесты
vendor/bin/codecept run unit

# Тесты миграций модуля
vendor/bin/codecept run migrations

# REST API тесты (если реализованы)
vendor/bin/codecept run rest
```

Или через bat-скрипты в корне проекта:

```bash
test-unit.bat    # unit-тесты
test-mig.bat     # тесты миграций
test-api.bat     # REST API тесты
```

> **Примечание:** На данный момент unit-тесты для модуля Schedules отсутствуют. Рекомендуется добавить тесты для [`TimeIntervalsHelper`](helpers/TimeIntervalsHelper.php), граничных случаев расписаний (переход через полночь, иерархия, перекрытия) и интеграционные тесты формирования расписания на день.

## Подробная документация

- [`docs/database.md`](docs/database.md) — схема таблиц БД
- [`../../docs/SCHEDULES.md`](../../docs/SCHEDULES.md) — детальная документация алгоритмов и бизнес-логики
