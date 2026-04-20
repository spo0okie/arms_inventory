# REST acceptance — TODO-трекер

Контракт и запуск — см. [tests/rest.md](../rest.md).
Раннер — `tests/rest/RestAccessCest.php` (генеративный, аналог `PageAccessCest`).

## Пробелы в покрытии (нет testXxx() провайдера → auto-skip)

- `CompsController::actionPush` — upsert по имени, нужен отдельный провайдер.
- `LoginJournalController::actionPush` — нужен отдельный провайдер.
- `NetIpsController::actionFirstUnused` — GET с обязательным `text_addr`.
- `PhonesController::actionSearchByNum` / `actionSearchByUser` — поиск по
  номеру/пользователю.
- `ScansController::actionUpload` / `actionDownload` — multipart upload и бинарное
  скачивание (нужен отдельный helper).
- `SchedulesController::actionStatus` / `actionMetaStatus` / `actionNextMeta` /
  `actionDaysSchedules` — зависят от seed'а графиков.
- `TechsController::actionSearchByMac` / `actionSearchByUser`.
- `UsersController::actionWhoami` — требует авторизацию.

## Известные провалы (500) — реальные баги production-кода

Все три failure ниже — не проблемы теста, а подсвеченные генеративным раннером
дефекты. После фикса соответствующие сценарии пройдут автоматически.

### `contracts/search` и `contracts/filter` → 500 `Column 'name' in where clause is ambiguous`

`Contracts::find()` джойнит `contracts_in_techs → techs`, `partners_in_contracts →
partners`, `users_in_contracts → users`, у всех есть столбец `name`. Базовый
`BaseRestController::searchFilter()` генерирует `WHERE name=?` без префикса
таблицы, MySQL кидает 1052.

**Fix:** либо в `searchFilter()` использовать
`{tableName}.{attr}` (`getAttributeFilter()` уже умеет), либо в
`ContractsController` переопределить `$searchFields` с явными префиксами.

### `lic-items/create` → 500 `Column 'created_at' cannot be null`

`LicItems` не заполняет `created_at` в `beforeSave()`/`behaviors()`, а в БД колонка
NOT NULL. REST CreateAction полагается на модель. В UI работает потому, что
форма подсовывает `created_at` через скрытое поле.

**Fix:** добавить в `LicItems` `TimestampBehavior` с `createdAtAttribute`, либо
`beforeSave()` с `if ($insert) $this->created_at = date(...);`.

## Домены: коллизия URL-правил

`config/web.php` содержит `'api/domains/<id:[\.\w-]+>' => 'api/domains/view'`,
которое матчится раньше общих `api/<controller>/search|filter` и
`OPTIONS api/<controller>/<action>`. В итоге для Domains три action'а недоступны:
- `domains/search` → view с id='search' → 404;
- `domains/filter` → view с id='filter' → 404;
- `OPTIONS domains/index` → view с id='index' → 405 MethodNotAllowed.

Пока `DomainsController::disabledTests()` скрывает search/filter/preflight.

**Fix:** либо переставить `search|filter` правила перед fqdn-правилом Domains,
либо ограничить regex id для Domains так, чтобы он не матчил зарезервированные
`search|filter` (например, `[\.\w-]+(?<!search)(?<!filter)`).

## Легенда

- Расширяя покрытие, добавляйте провайдеры testXxx() в соответствующий
  контроллер (см. tests/rest.md).
- Skip-сценарий помечайте `'skip' => true` + `'reason' => 'TODO: ...'`,
  чтобы было видно в отчёте.
