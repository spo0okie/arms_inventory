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
→ Tests: 877, Assertions: 3137, Skipped: 8, Errors: 0, Failures: 0, пик ~506 MB.

## Остаточные skip-сценарии

1. `ArmsBaseController::testItemByName` — автоматический skip `item by name empty`
   для моделей, у которых `empty->getName()` возвращает пустую строку (7 контроллеров).
   Снимется сам, когда ModelFactory начнёт гарантировать непустое `name` для
   empty-моделей соответствующих классов.
2. `OrgInetController::testView` — skip `view empty`: шаблон view требует
   связанный `Services`, а `linksSchema` OrgInet не помечает `services_id` как
   required. Закроется после приведения `OrgInet::linksSchema` к новому формату.

## Открытые задачи

### Legacy acceptance-Cests (этап 6 `generation/tests.md`)

- `tests/acceptance/AuthorizationModesCest.php` — **оставить**. Покрывает режимы
  авторизации (useRBAC/localAuth/authorizedView), не относится к генеративному
  покрытию UI-страниц, живёт как отдельный специализированный suite.
- `tests/acceptance/HistoryPagesCest.php` — **оставить до тех пор, пока тесты
  `HistoryController` не покроют все модели с историей в том же объёме, что
  сейчас делает `HistoryPagesCest`**. После этого — удалить.
