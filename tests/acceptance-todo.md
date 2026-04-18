# TODO по Acceptance-покрытию UI-контроллеров

Источник: `tests/acceptance.md` и аудит controller/action/test-методов в UI-контроллерах.  
Скоуп: `controllers/*Controller.php` + `modules/schedules/controllers/*Controller.php`.  
Исключено: `modules/api/controllers/*` (REST API).

## Текущее состояние дыр

- Полностью отключены через `disabledTests('*')`: 0 контроллеров.
- Отсутствуют `testXxx()` при существующем `actionXxx`: 0 кейсов.
- Есть `testXxx()`, но сейчас только `skipScenario(...)` (кроме `editable`): 24 кейса.
- `testEditable()` везде возвращает skip: 51 контроллер.

Статус на 2026-04-18:
- `D1` (`UserGroupsController`) закрыт как `N/A` (контроллер удалён как deprecated).
- `D2`, `D3`, `D4` закрыты (`UiTablesCols`, `Sms`, `History`).
- `M1`, `M2`, `M3` закрыты (`testJournal`, `testSend`, `testSet` реализованы).

## Группа 1. Снять полное отключение `disabledTests('*')` (закрыто)

Общее решение: убрать wildcard в `disabledTests()`, включить тесты для релевантных action, нерелевантные inherited action отключать точечно через `disabledActions()`.

- `app\controllers\HistoryController` (done)
- `app\controllers\SmsController` (done)
- `app\controllers\UiTablesColsController` (done)

## Группа 2. Добавить отсутствующие `testXxx()` (закрыто)

- `app\controllers\HistoryController::testJournal()` для `actionJournal` (done)
- `app\controllers\SmsController::testSend()` для `actionSend` (done)
- `app\controllers\UiTablesColsController::testSet()` для `actionSet` (done)

## Группа 3. Заменить `skip` на реальные сценарии (не `editable`)

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

## Группа 4. Закрыть `testEditable()` (51 контроллер)

Общее решение: сделать единый acceptance-паттерн POST для Kartik Editable и затем включить реальные `testEditable()` в каждом контроллере, где `editable` реально используется.

- `app\controllers\AccessTypesController`
- `app\controllers\AcesController`
- `app\controllers\AclsController`
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
- `app\controllers\UsersController`
- `app\modules\schedules\controllers\ScheduledAccessController`
- `app\modules\schedules\controllers\SchedulesController`
- `app\modules\schedules\controllers\SchedulesEntriesController`

## Порядок закрытия дыр по трудозатратам (от простого к сложному)

1. Добавить отсутствующие `testXxx()` в `HistoryController`, `SmsController`, `UiTablesColsController`.
2. Убрать `disabledTests('*')` в `UiTablesColsController`, `SmsController`, `HistoryController`.
3. Закрыть простые `skip` (без файлов/внешних интеграций): `Manufacturers`, `Site` (`api-json`, `error`), `Ports::port-list`.
4. Закрыть `skip`, требующие связанных данных/файлов: `Attaches`, `Scans`, `Places`, `Services`, `TechModels`, `Techs`.
5. Реализовать общий `editable`-паттерн и массово снять skip в `testEditable()`.
6. Выполнить стабилизацию полным прогоном acceptance.

## Детальный план закрытия каждого кейса (для новичка/LLM)

### 0) Базовая подготовка

1. Открыть `tests/acceptance.md` и принять формат сценариев (`name`, `GET/POST`, `response`, `skip`, `reason`).
2. Проверить, что локально поднимается тестовое приложение (`config/test-web.php`, `entryUrl` из `tests/acceptance.suite.yml`).
3. Перед каждым запуском учитывать, что `PageAccessCest` сам переинициализирует БД через `tests/_data/arms_demo.sql`.
4. Использовать быстрый прогон:
`php vendor/bin/codecept run tests/acceptance/PageAccessCest.php --verbose`
5. После каждой правки гонять полный acceptance:
`php vendor/bin/codecept run tests/acceptance`

### 1) Кейсы снятия `disabledTests('*')` (4 контроллера)

#### Кейс D1: `app\controllers\UserGroupsController`

1. `N/A`: контроллер помечен deprecated и удалён из проекта.

#### Кейс D2: `app\controllers\UiTablesColsController`

1. Убрать `['*']` из `disabledTests()`.
2. Добавить `testSet()` (см. кейс M3 ниже).
3. Для action, которые не должны существовать в этом контроллере, явно задать `disabledActions()`.
4. Критерий готовности: `set` имеет реальный тест-сценарий, inherited шум отключен точечно.

#### Кейс D3: `app\controllers\SmsController`

1. Убрать `['*']` из `disabledTests()`.
2. Добавить `testSend()` (см. кейс M2 ниже).
3. Не допускать реальной отправки SMS в acceptance: использовать GET и/или невалидный POST, не вызывающий `send()`.
4. Критерий готовности: `send` покрыт безопасно, без внешних отправок.

#### Кейс D4: `app\controllers\HistoryController`

1. Убрать `['*']` из `disabledTests()`.
2. Добавить `testJournal()` (см. кейс M1 ниже).
3. Если часть inherited action нерелевантна, отключить их через `disabledActions()`, а не wildcard.
4. Критерий готовности: `journal` покрыт и стабилен на тестовом дампе.

### 2) Кейсы отсутствующих `testXxx()` (3 кейса)

#### Кейс M1: `HistoryController::testJournal()`

1. Файл: `controllers/HistoryController.php`.
2. Учесть сигнатуру action: `actionJournal(string $class, int $id)`.
3. Добавить минимум 3 сценария:
- `existing-history`: валидные `class` и `id` из тестового дампа, `response=200`.
- `missing-class`: несуществующий класс, `response=404`.
- `wrong-class-type`: существующий класс, не `HistoryModel`, `response=400/500` (зафиксировать фактический код и использовать его в тесте).
4. Для валидного сценария использовать подход из `tests/acceptance/HistoryPagesCest.php` (поиск master_id в history-таблицах).
5. Критерий готовности: `testJournal()` возвращает не-skip сценарии и стабильно проходит.

#### Кейс M2: `SmsController::testSend()`

1. Файл: `controllers/SmsController.php`.
2. Учесть сигнатуру action: `actionSend()`.
3. Добавить безопасные сценарии:
- `get-form`: GET без параметров, `response=200`.
- `get-prefilled`: GET с `phone` и `text`, `response=200`.
- `post-invalid`: POST с невалидными данными формы, `response=200` (рендер формы без вызова отправки).
4. Не добавлять сценарий, который вызывает `SmsForm::send()` в тестовой среде.
5. Критерий готовности: проверяется доступность формы и валидационное поведение без внешних side effects.

#### Кейс M3: `UiTablesColsController::testSet()`

1. Файл: `controllers/UiTablesColsController.php`.
2. Учесть сигнатуру action: `actionSet(string $table, string $column, int $user, string $value)`.
3. Добавить сценарии:
- `create-setting`: GET с новой комбинацией `table/column/user`, `response=200`.
- `update-setting`: GET для существующей записи (повторный вызов), `response=200`.
- `invalid-user`: невалидный `user` (если ожидается 400/404, зафиксировать и проверить фактический код).
4. После запроса в acceptance дополнительно проверить в БД факт сохранения/обновления записи (через helper или прямой запрос).
5. Критерий готовности: `set` проверяет и создание, и обновление настройки.

### 3) Кейсы, где сейчас `skipScenario(...)` (24 кейса)

#### Кейс S1: `AttachesController::testCreate()`

1. Подготовить файловую фикстуру в доступном test-path.
2. Сформировать POST как multipart (поля формы + файл).
3. Проверить код ответа и факт создания записи/файла.

#### Кейс S2: `AttachesController::testDelete()`

1. Подготовить attach-запись с физическим файлом.
2. Вызвать delete-сценарий.
3. Проверить удаление записи и ожидаемое поведение файла (удален/помечен).

#### Кейс S3: `CompsController::testDupes()`

1. Подготовить минимум 2 `Comps` с одинаковым `name`.
2. Вызвать action `dupes`.
3. Проверить `200` и наличие дубликатов в выдаче.

#### Кейс S4: `CompsController::testTtipHw()`

1. Создать `Comps` с заполненным HW-контекстом.
2. Вызвать `ttip-hw` с `id`.
3. Проверить `200` и что шаблон tooltip рендерится без ошибок.

#### Кейс S5: `ManufacturersController::testItemByName()`

1. Подготовить запись производителя и заполнить `ManufacturersDict`/кэш, как ожидает поиск.
2. Вызвать `item-by-name` с валидным `name`.
3. Проверить `200` и корректную карточку.

#### Кейс S6: `OrgInetController::testTtip()`

1. Подготовить контекст, требуемый интеграцией `OrgInet` (или тестовый стаб этого контекста).
2. Вызвать `ttip` с валидным `id`.
3. Проверить `200` и отсутствие ошибок интеграции.

#### Кейс S7: `OrgInetController::testView()`

1. Использовать тот же интеграционный контекст, что в S6.
2. Вызвать `view` с валидным `id`.
3. Проверить `200` и корректный рендер.

#### Кейс S8: `PlacesController::testMapSet()`

1. Подготовить place + floorplan + payload `MapItemForm`.
2. Отправить сценарий `map-set`.
3. Проверить сохранение map-элемента и `200/302` по факту.

#### Кейс S9: `PlacesController::testMapDelete()`

1. Подготовить place с уже размещенным map-элементом.
2. Вызвать `map-delete` с `id`, `item_type`, `item_id`.
3. Проверить удаление элемента и ожидаемый HTTP-код.

#### Кейс S10: `PortsController::testPortList()`

1. Подготовить связанные `Techs`/`Ports`.
2. Передать DepDrop POST payload, который ожидает `actionPortList()`.
3. Проверить формат JSON/HTML-ответа и код.

#### Кейс S11: `ScansController::testThumb()`

1. Подготовить scan-запись с физическим файлом превью.
2. Вызвать `thumb` с `id`, `link`, `link_id`.
3. Проверить корректный ответ и доступность миниатюры.

#### Кейс S12: `ServicesController::testCard()`

1. Подготовить `Service` с нужными связями для карточки.
2. Вызвать `card` с `id`.
3. Проверить `200` и успешный рендер виджетов карточки.

#### Кейс S13: `ServicesController::testCardMaintenanceReqs()`

1. Подготовить `Service` с связанными maintenance requirements.
2. Вызвать `card-maintenance-reqs`.
3. Проверить `200` и отображение блока заявок.

#### Кейс S14: `SiteController::testApiJson()`

1. Подготовить runtime-конфиг swagger scan.
2. Вызвать `api-json`.
3. Проверить валидный JSON и ожидаемый код.

#### Кейс S15: `SiteController::testError()`

1. Сгенерировать контролируемый exception context.
2. Вызвать `error` route.
3. Проверить ожидаемый код и рендер error-page.

#### Кейс S16: `SiteController::testPasswordSet()`

1. Подготовить валидного пользователя и контекст прав (admin/нужная роль).
2. Вызвать `password-set` (GET/POST).
3. Проверить код ответа и успешное обновление пароля (или ожидаемую валидационную ошибку).

#### Кейс S17: `SiteController::testRackTest()`

1. Подготовить rack-фикстуры и конфигурацию.
2. Вызвать `rack-test`.
3. Проверить `200` и отсутствие ошибок в рендере.

#### Кейс S18: `SiteController::testView()`

1. Проверить, нужен ли этот inherited action для `SiteController` вообще.
2. Если не нужен: перенести в `disabledActions()` и убрать skip в тесте как нерелевантный кейс.
3. Если нужен: определить источник данных и добавить рабочий сценарий.

#### Кейс S19: `TechModelsController::testRenderRack()`

1. Подготовить `TechModel` с rack-параметрами.
2. Вызвать `render-rack`.
3. Проверить `200` и корректный HTML рендер стойки.

#### Кейс S20: `TechsController::testDocs()`

1. Подготовить `Techs` + валидное значение `doc`.
2. Вызвать `docs` с `id`, `doc`.
3. Проверить код и ответ (страница/файл/редирект по факту).

#### Кейс S21: `TechsController::testInvNum()`

1. Подготовить выборку оборудования с разными фильтрами (`model_id`, `place_id`, `org_id`, `arm_id`, `installed_id`).
2. Добавить сценарии без фильтра и с фильтром.
3. Проверить `200` и консистентный ответ.

#### Кейс S22: `TechsController::testPortList()`

1. Подготовить связки `Techs`/`Ports`.
2. Передать POST payload depdrop для `actionPortList()`.
3. Проверить код и структуру ответа.

#### Кейс S23: `TechsController::testRackUnitValidate()`

1. Подготовить rack-конфигурацию.
2. Выполнить POST в `rack-unit-validate`.
3. Проверить результат валидации и код ответа.

#### Кейс S24: `TechsController::testRackUnit()`

1. Подготовить `Techs`, который можно установить в стойку.
2. Вызвать `rack-unit` с `id`, `unit`, `front`.
3. Проверить ожидаемый результат (редирект/200) и изменение состояния в модели.

### 4) Кейсы `testEditable()` (51 отдельных кейсов)

#### Универсальный шаблон реализации (применить к каждому контроллеру из списка группы 4)

1. Проверить, используется ли `editable` в UI этого контроллера реально.
2. Если не используется: добавить `editable` в `disabledActions()` конкретного контроллера и убрать нерелевантный skip-кейс.
3. Если используется:
- подготовить запись модели через `getTestData()`/фикстуру;
- сформировать POST payload Kartik Editable (pk, attribute, value, formName);
- ожидать `200` или `302` по фактическому поведению;
- после запроса проверить, что атрибут реально обновился в БД.
4. Добавить/переопределить `testEditable()` в контроллере с реальными сценариями.
5. Прогнать `PageAccessCest` и зафиксировать стабильность.

#### Отдельные кейсы, где надо применить шаблон 1:1

- `app\controllers\AccessTypesController::testEditable()`
- `app\controllers\AcesController::testEditable()`
- `app\controllers\AclsController::testEditable()`
- `app\controllers\AttachesController::testEditable()`
- `app\controllers\CompsController::testEditable()`
- `app\controllers\ContractsController::testEditable()`
- `app\controllers\ContractsStatesController::testEditable()`
- `app\controllers\DepartmentsController::testEditable()`
- `app\controllers\DomainsController::testEditable()`
- `app\controllers\HistoryController::testEditable()`
- `app\controllers\HwIgnoreController::testEditable()`
- `app\controllers\LicGroupsController::testEditable()`
- `app\controllers\LicItemsController::testEditable()`
- `app\controllers\LicKeysController::testEditable()`
- `app\controllers\LicTypesController::testEditable()`
- `app\controllers\LoginJournalController::testEditable()`
- `app\controllers\MaintenanceJobsController::testEditable()`
- `app\controllers\MaintenanceReqsController::testEditable()`
- `app\controllers\ManufacturersController::testEditable()`
- `app\controllers\ManufacturersDictController::testEditable()`
- `app\controllers\MaterialsController::testEditable()`
- `app\controllers\MaterialsTypesController::testEditable()`
- `app\controllers\MaterialsUsagesController::testEditable()`
- `app\controllers\NetDomainsController::testEditable()`
- `app\controllers\NetIpsController::testEditable()`
- `app\controllers\NetVlansController::testEditable()`
- `app\controllers\NetworksController::testEditable()`
- `app\controllers\OrgInetController::testEditable()`
- `app\controllers\OrgPhonesController::testEditable()`
- `app\controllers\OrgStructController::testEditable()`
- `app\controllers\PartnersController::testEditable()`
- `app\controllers\PlacesController::testEditable()`
- `app\controllers\PortsController::testEditable()`
- `app\controllers\SandboxesController::testEditable()`
- `app\controllers\ScansController::testEditable()`
- `app\controllers\SegmentsController::testEditable()`
- `app\controllers\ServicesController::testEditable()`
- `app\controllers\SmsController::testEditable()`
- `app\controllers\SoftController::testEditable()`
- `app\controllers\SoftListsController::testEditable()`
- `app\controllers\TagsController::testEditable()`
- `app\controllers\TechModelsController::testEditable()`
- `app\controllers\TechsController::testEditable()`
- `app\controllers\TechStatesController::testEditable()`
- `app\controllers\TechTypesController::testEditable()`
- `app\controllers\UiTablesColsController::testEditable()`
- `app\controllers\UsersController::testEditable()`
- `app\modules\schedules\controllers\ScheduledAccessController::testEditable()`
- `app\modules\schedules\controllers\SchedulesController::testEditable()`
- `app\modules\schedules\controllers\SchedulesEntriesController::testEditable()`

### 5) Definition of Done для каждого кейса

1. В контроллере нет wildcard `disabledTests('*')` для закрываемого кейса.
2. Есть реальный `testXxx()` со сценариями без `skip`.
3. Запрос возвращает ожидаемый HTTP-код и (где нужно) подтвержден эффект в БД/файлах.
4. `PageAccessCest` проходит для измененного контроллера.
5. Полный `tests/acceptance` не деградирует.
