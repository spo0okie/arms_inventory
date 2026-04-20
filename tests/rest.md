# REST acceptance-тесты проекта ARMS (генеративные)

Документ описывает, как устроен и поддерживается `tests/rest/RestAccessCest.php` —
генеративный раннер acceptance-тестов для REST API (модуль `modules/api`).

Смежные документы:
- `tests/acceptance.md` — тот же подход для UI-контроллеров (`tests/acceptance/PageAccessCest.php`);
- `generation/readme.md` — механизм генерации валидных моделей (`ModelFactory`, roles, seed, linksSchema).

---

## Идея и pipeline

```text
modules/api/controllers/*Controller.php
  -> actionXxx()
  -> testXxx() (data provider для RestAccessCest)
  -> сценарии (method/route/GET/body + ожидаемый HTTP-код + optional assert)
  -> HTTP-запрос к тестовому серверу (index-test.php/api)
  -> проверка HTTP-кода и optional post-assert
```

Фактический раннер: `tests/rest/RestAccessCest.php`.

---

## Размещение сценариев и описаний

- Для каждого `actionXxx()` REST-контроллера должен существовать парный `testXxx(): array`.
- Базовые `actionIndex`/`View`/`Create`/`Update`/`Delete`/`Search`/`Filter`/`Preflight`
  имеют провайдеры по умолчанию в `BaseRestController` — их не нужно переопределять
  без необходимости.
- Кастомные action'ы (например, `actionPush`, `actionWhoami`, `actionStatus`) должны
  получить свой `testXxx()`. Пока провайдера нет — раннер автоматически помечает
  маршрут как skip с пометкой `TODO` (см. `tests/rest-todo.md`).
- Action можно исключить из тестирования через `disabledActions()`
  (отключает и в runtime) или `disabledTests()` (только скрыть тест, action жив).

---

## Формат сценария

Каждый элемент массива, возвращаемого из `testXxx()`:

```php
return [[
    'name'     => 'default',        // имя сценария в отчёте
    'method'   => 'GET',            // HTTP-метод (GET/POST/PUT/PATCH/DELETE/OPTIONS)
    'route'    => '{controller}',   // путь относительно /api, поддерживает {controller}
    'GET'      => [],               // query-параметры
    'body'     => [],               // тело запроса (для POST/PUT/PATCH)
    'headers'  => [],               // доп. HTTP-заголовки
    'response' => 200,              // int или [min,max] диапазон
    'assert'   => null,             // callable(ApiTester $I, array $scenario, string $route, string $url)
    'skip'     => false,            // пропустить сценарий
    'reason'   => '',               // причина skip
]];
```

- `{controller}` в route заменяется на slug контроллера (`comps`, `lic-groups`, …);
- `{id}` в route не раскрывается магически — если нужен id, впишите его напрямую
  из `$this->getTestData()['view']->id` при построении сценария;
- Content-Type `application/json` добавляется раннером автоматически.

### Пример: кастомный сценарий с assert

```php
public function testSearch(): array
{
    $scenarios = parent::testSearch();
    $scenarios[] = [
        'name' => 'search by demo name',
        'method' => 'GET',
        'route' => '{controller}/search',
        'GET' => ['name' => 'msk-esxi1'],
        'response' => 200,
        'assert' => static function (ApiTester $I) {
            $I->seeResponseIsJson();
            $I->seeResponseContainsJson(['name' => 'MSK-ESXi1']);
        },
    ];
    return $scenarios;
}
```

---

## Подготовка данных

Базовый `BaseRestController::getTestData()` создаёт через `ModelFactory`:

- `view`, `update`, `delete` — сохранённые модели (используются для GET/PUT/DELETE);
- `create-data`, `update-data` — сгенерированные, НЕ сохранённые (их атрибуты —
  валидный JSON-пейлоад).

Кешируется по `modelClass`, чтобы не плодить дубликатов.

Если контроллер работает с редкой моделью/сценарием — переопределите `getTestData()`
и/или соответствующий `testXxx()`.

---

## Запуск

```bash
# Полный REST suite (генеративный runner)
php vendor/bin/codecept run tests/rest

# Только RestAccessCest
php vendor/bin/codecept run tests/rest/RestAccessCest.php --verbose

# Точечный фильтр (аналог TEST_ROUTES из acceptance)
SET TEST_REST_ROUTES='comps,lic-groups/view,domains/search/search by demo name' \
  && php vendor/bin/codecept run tests/rest/RestAccessCest.php --verbose
```

`TEST_REST_ROUTES` поддерживает список через запятую:
- `comps` — все action/scenario контроллера;
- `lic-groups/view` — все сценарии action `view`;
- `domains/search/search by demo name` — конкретный сценарий.

Тестовый сервер (`web/index-test.php`) должен быть поднят на `http://localhost:8081`.

---

## Связь с generation/readme.md

Как и в UI-acceptance:
- `tests/rest.md` — контракт сценариев и HTTP-ожиданий;
- `generation/readme.md` — механизм подготовки валидных данных через `ModelFactory`.

---

## Типовые причины падений

- Контроллер добавил action, но `testXxx()` не написан — маршрут падает в skip TODO.
- Не тот HTTP-метод в сценарии (например, POST на update, которое требует PUT).
- `body` содержит лишние поля, которые модель не принимает через `load()` — обычно
  достаточно отдавать `$data['create-data']->attributes`.
- Тестовый сервер (`:8081`) не запущен.
- `Helper\Rest::_beforeSuite` пересоздаёт БД и затирает данные, засеянные в
  `routesProvider()` — поэтому он должен оставаться no-op.
