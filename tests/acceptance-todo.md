# TODO по Acceptance-покрытию UI-контроллеров

Источник: `tests/acceptance.md` и аудит controller/action/test-методов в UI-контроллерах.
Скоуп: `controllers/*Controller.php` + `modules/schedules/controllers/*Controller.php`.
Исключено: `modules/api/controllers/*` (REST API).

## Текущее состояние покрытия

- `disabledTests('*')`: не используется ни в одном контроллере.
- Отсутствующих `testXxx()` при существующем `actionXxx`: нет.
- `testXxx()` только со `skipScenario(...)` (кроме `editable`): нет.
- `testEditable()` закрыт единым базовым `ArmsBaseController::testEditable()`
  (сценарий `missing payload`, AJAX POST без `hasEditable` → HTTP 200 +
  JSON Kartik). Контроллеры без editable в UI отключают action через
  `disabledActions(['editable'])`.

Прогон 2026-04-19: `php vendor/bin/codecept run tests/acceptance/PageAccessCest.php`
→ Tests: 884, Assertions: 3162, Skipped: 7, Errors: 0, Failures: 0, пик ~510 MB.

## Остаточные skip-сценарии

1. `ArmsBaseController::testItemByName` — автоматический skip `item by name empty`
   для моделей, у которых `empty->getName()` возвращает пустую строку (7 контроллеров).
   Снимется сам, когда ModelFactory начнёт гарантировать непустое `name` для
   empty-моделей соответствующих классов.

## Содержательные перекрытия базовых test-методов

Базовые `testIndex/testAsyncGrid/testItem/testItemByName/testTtip/testView/testValidate/
testCreate/testUpdate/testDelete/testEditable` в `ArmsBaseController` закрывают стандартный
CRUD-контракт. Ниже — места, где контроллер обоснованно перекрывает базовый метод:

- `AttachesController::testCreate/testDelete` — файловые fixture-assertions.
- `NetIpsController::testItemByName`, `NetworksController::testItemByName` — поиск
  идёт по `text_addr`, а не по стандартному `name`.
- `TechModelsController::testItemByName` — поиск по `name` + `manufacturer`.
- `TechsController::testItemByName` — поиск по `num`.
- `ScheduledAccessController::testView`, `SchedulesController::testView` — обход
  redirect'а `actionView` на оригинал расписания при выставленном `override_id`.
- `SchedulesController::testDelete` — Schedules- и ScheduledAccess-контроллеры
  делят общий `testDataCache` (оба используют `Schedules::class`); запись `'delete'`
  удаляется одним, второй вынужден создавать свою через ModelFactory.
- `SchedulesEntriesController::testCreate` — create-шаблон требует предзаполненный
  `SchedulesEntries[schedule_id]`, иначе 500.
- `SchedulesEntriesController::testUpdate` — `fillForm(update-data)` даёт 500 на
  бизнес-правилах формы; оставлен только сценарий `form open`.
- `SiteController::testIndex` — у SiteController нет `modelClass`, базовый
  `getTestData()` не работает.

## Открытые задачи

### Legacy acceptance-Cests (этап 6 `generation/tests.md`)

- `tests/acceptance/AuthorizationModesCest.php` — **оставить**. Покрывает режимы
  авторизации (useRBAC/localAuth/authorizedView), не относится к генеративному
  покрытию UI-страниц, живёт как отдельный специализированный suite.
- `tests/acceptance/HistoryPagesCest.php` — **оставить до тех пор, пока тесты
  `HistoryController` не покроют все модели с историей в том же объёме, что
  сейчас делает `HistoryPagesCest`**. После этого — удалить.
