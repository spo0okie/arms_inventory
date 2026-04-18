# TODO по Acceptance-покрытию UI-контроллеров

Источник: `tests/acceptance.md` и аудит controller/action/test-методов в UI-контроллерах.
Скоуп: `controllers/*Controller.php` + `modules/schedules/controllers/*Controller.php`.
Исключено: `modules/api/controllers/*` (REST API).

## Группа 1. Снять полное отключение `disabledTests('*')`

Общее решение: убрать wildcard в `disabledTests()`, оставить только реально нерелевантные action через `disabledActions()`, а для бизнес-action добавить/актуализировать `testXxx()` сценарии.

- `app\controllers\HistoryController` (12 action сейчас полностью исключены из `PageAccessCest`)
- `app\controllers\SmsController` (12 action сейчас полностью исключены из `PageAccessCest`)
- `app\controllers\UiTablesColsController` (12 action сейчас полностью исключены из `PageAccessCest`)
- `app\controllers\UserGroupsController` (11 action сейчас полностью исключены из `PageAccessCest`)

## Группа 2. Добавить отсутствующие методы `testXxx()`

Общее решение: реализовать недостающие data-provider методы в контроллерах (формат из `tests/acceptance.md`: `name`, `GET/POST`, `response`).

- `app\controllers\ArmsController::testContracts()` для action `contracts`
- `app\controllers\ArmsController::testUpdateApply()` для action `update-apply`
- `app\controllers\ArmsController::testUpdhw()` для action `updhw`
- `app\controllers\ArmsController::testRmhw()` для action `rmhw`
- `app\controllers\HistoryController::testJournal()` для action `journal`
- `app\controllers\SmsController::testSend()` для action `send`
- `app\controllers\UiTablesColsController::testSet()` для action `set`

## Группа 3. Заменить `skip`-тесты на реальные сценарии (не `editable`)

Общее решение: вместо `skipScenario(...)` вернуть рабочие сценарии с подготовкой данных в тестовой БД/фикстурах.

- `app\controllers\AttachesController::testCreate()` [action `create`]
- `app\controllers\AttachesController::testDelete()` [action `delete`]
- `app\controllers\CompsController::testDupes()` [action `dupes`]
- `app\controllers\CompsController::testTtipHw()` [action `ttip-hw`]
- `app\controllers\ManufacturersController::testItemByName()` [action `item-by-name`]
- `app\controllers\OrgInetController::testTtip()` [action `ttip`]
- `app\controllers\OrgInetController::testView()` [action `view`]
- `app\controllers\PlacesController::testMapDelete()` [action `map-delete`]
- `app\controllers\PlacesController::testMapSet()` [action `map-set`]
- `app\controllers\PortsController::testPortList()` [action `port-list`]
- `app\controllers\ScansController::testThumb()` [action `thumb`]
- `app\controllers\ServicesController::testCard()` [action `card`]
- `app\controllers\ServicesController::testCardMaintenanceReqs()` [action `card-maintenance-reqs`]
- `app\controllers\SiteController::testApiJson()` [action `api-json`]
- `app\controllers\SiteController::testError()` [action `error`]
- `app\controllers\SiteController::testPasswordSet()` [action `password-set`]
- `app\controllers\SiteController::testRackTest()` [action `rack-test`]
- `app\controllers\SiteController::testView()` [action `view`]
- `app\controllers\TechModelsController::testRenderRack()` [action `render-rack`]
- `app\controllers\TechsController::testDocs()` [action `docs`]
- `app\controllers\TechsController::testInvNum()` [action `inv-num`]
- `app\controllers\TechsController::testPortList()` [action `port-list`]
- `app\controllers\TechsController::testRackUnit()` [action `rack-unit`]
- `app\controllers\TechsController::testRackUnitValidate()` [action `rack-unit-validate`]

## Группа 4. Реализовать покрытие `editable` для всех UI-контроллеров

Общее решение: один общий механизм для acceptance POST-сценария inline-редактирования (Kartik Editable), затем убрать `skip` в `testEditable()` (или переопределить `testEditable()` в контроллерах, где action реально используется).

Нужно закрыть 52 `testEditable()` в контроллерах:

- `app\controllers\AccessTypesController`
- `app\controllers\AcesController`
- `app\controllers\AclsController`
- `app\controllers\ArmsController`
- `app\controllers\AttachesController`
- `app\controllers\CompsController`
- `app\controllers\ContractsController`
- `app\controllers\ContractsStatesController`
- `app\controllers\DepartmentsController`
- `app\controllers\DomainsController`
- `app\controllers\HistoryController`
- `app\controllers\HwIgnoreController`
- `app\controllers\LicGroupsController`
- `app\controllers\LicItemsController`
- `app\controllers\LicKeysController`
- `app\controllers\LicTypesController`
- `app\controllers\LoginJournalController`
- `app\controllers\MaintenanceJobsController`
- `app\controllers\MaintenanceReqsController`
- `app\controllers\ManufacturersController`
- `app\controllers\ManufacturersDictController`
- `app\controllers\MaterialsController`
- `app\controllers\MaterialsTypesController`
- `app\controllers\MaterialsUsagesController`
- `app\controllers\NetDomainsController`
- `app\controllers\NetIpsController`
- `app\controllers\NetVlansController`
- `app\controllers\NetworksController`
- `app\controllers\OrgInetController`
- `app\controllers\OrgPhonesController`
- `app\controllers\OrgStructController`
- `app\controllers\PartnersController`
- `app\controllers\PlacesController`
- `app\controllers\PortsController`
- `app\controllers\SandboxesController`
- `app\controllers\ScansController`
- `app\controllers\SegmentsController`
- `app\controllers\ServicesController`
- `app\controllers\SmsController`
- `app\controllers\SoftController`
- `app\controllers\SoftListsController`
- `app\controllers\TagsController`
- `app\controllers\TechModelsController`
- `app\controllers\TechsController`
- `app\controllers\TechStatesController`
- `app\controllers\TechTypesController`
- `app\controllers\UiTablesColsController`
- `app\controllers\UserGroupsController`
- `app\controllers\UsersController`
- `app\modules\schedules\controllers\ScheduledAccessController`
- `app\modules\schedules\controllers\SchedulesController`
- `app\modules\schedules\controllers\SchedulesEntriesController`

## Порядок закрытия дыр по трудозатратам (от простого к сложному)

1. Низкие трудозатраты: добавить отсутствующие `testXxx()` без сложной подготовки данных.
`ArmsController::testContracts/testUpdateApply/testUpdhw/testRmhw`, `HistoryController::testJournal`, `SmsController::testSend`, `UiTablesColsController::testSet`.

2. Низкие-средние: снять `disabledTests('*')` в контроллерах с минимальной кастомной логикой и включить базовые тесты `ArmsBaseController` (index/view/item/ttip/create/update/delete/validate).
Начать с `UserGroupsController`, затем `UiTablesColsController`, `SmsController`, `HistoryController`.

3. Средние: заменить `skip` в точечных action, где достаточно корректного seed/fixture и простого `GET`/`POST`.
В первую очередь: `ManufacturersController::testItemByName`, `OrgInetController::testTtip/testView`, `PortsController::testPortList`, `ScansController::testThumb`, `SiteController::testApiJson/testError`.

4. Средние-высокие: закрыть `skip` в action, требующих связанной доменной модели/файлов/преднастроек.
`AttachesController::testCreate/testDelete`, `CompsController::testDupes/testTtipHw`, `PlacesController::testMapSet/testMapDelete`, `ServicesController::testCard/testCardMaintenanceReqs`, `TechModelsController::testRenderRack`, `TechsController::testDocs/testInvNum/testPortList/testRackUnit/testRackUnitValidate`, `SiteController::testPasswordSet/testRackTest/testView`.

5. Высокие: реализовать единый рабочий acceptance-паттерн для `editable` (Kartik Editable) и затем массово убрать `skip` в `testEditable()` во всех UI-контроллерах.

6. Финал: прогон полного набора и стабилизация.
`php vendor/bin/codecept run tests/acceptance/PageAccessCest.php --verbose`, затем `php vendor/bin/codecept run tests/acceptance`; падения переводить в явные сценарии/фикс данных, а не в wildcard-disable.
