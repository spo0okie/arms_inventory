# Acceptance тесты проекта ARMS (генеративные)

Документ описывает **как писать и поддерживать** `tests/acceptance/PageAccessCest.php`.

Смежный документ: `generation/readme.md` — описывает генерацию моделей (ModelFactory, roles, seed, linksSchema).  
Этот файл фокусируется на контракте сценариев контроллеров и правилах запуска acceptance.

---

## 1) Идея и pipeline

Acceptance-тесты собираются динамически из контроллеров:

```text
controllers/*Controller.php
  -> actionXxx()
  -> testXxx() (data provider для PageAccessCest)
  -> сценарии (GET/POST + ожидаемый HTTP-код)
  -> проверка доступности маршрута
```

Фактический раннер: `tests/acceptance/PageAccessCest.php`.

---

## 2) Где описывать сценарии

Для action, который участвует в автопроверке, в контроллере должен быть метод:

```php
public function testXxx(): array
```

Пример: для `actionView()` нужен `testView()`.

Важно:
- методы `test*()` в текущей реализации — **обычные instance-методы**, не `static`;
- если action попадает в тестовый обход и `testXxx()` отсутствует, прогон падает с `Calling unknown method ...::testXxx()`;
- action можно исключить из автопроверки через `disabledTests()` или вернуть skip-сценарий.

---

## 3) Формат сценария

Сценарий — элемент массива, который возвращает `testXxx()`:

```php
return [
    [
        'name' => 'default',
        'GET' => [],
        'response' => 200,
    ],
];
```

Поддерживаемые поля:
- `name` (`string`) — идентификатор сценария;
- `route` (`string`) — кастомный маршрут (иначе `{controllerId}/{action}`);
- `GET` (`array`) — GET параметры;
- `POST` (`array`) — POST параметры (если есть, выполняется POST);
- `response` (`int|array`) — ожидаемый код или диапазон, например `[200, 302]`;
- `skip` (`bool`) — пропуск сценария;
- `reason` (`string`) — причина skip;
- `saveModel` (`array`) — сохранить модель в контекст для следующих шагов;
- `dropReverseLinks` (`array`) — очистить обратные связи перед шагом.

---

## 4) Макросы параметров

Поддерживаемые макросы (в соответствии с `PageAccessCest`):
- `{anyId}`
- `{otherId}`
- `{anyName}`
- `{anyModelParams}`
- `{otherModelParams}`
- `{<alias>ModelParams}`

Примечание: макросы `{replacedModelParams}` и `{deletedModelParams}` не являются базовым контрактом текущего `PageAccessCest` и не должны использоваться как обязательные.

---

## 5) Базовые сценарии ArmsBaseController

Если контроллер наследуется от `ArmsBaseController`, обычно доступны:
- `testIndex()`
- `testView()`
- `testCreate()`
- `testUpdate()`
- `testDelete()`
- `testItem()`
- `testItemByName()`
- `testTtip()`
- `testValidate()`
- `testAsyncGrid()`

Если конкретный action требует сложной подготовки (файлы, внешние сервисы, depdrop POST и т.д.), рекомендуется:
- либо сделать минимально валидный сценарий,
- либо явно вернуть `self::skipScenario('default', 'reason')`.

---

## 6) Связь с generation/readme.md

`generation/readme.md` дополняет этот документ:
- откуда берутся валидные модели и атрибуты (`ModelFactory::create`);
- как работают `role`, `overrides`, `seed`, `maxDepth`;
- как учитывать `linksSchema()` и self-reference;
- где применять бизнес-логику (`afterGenerate`, `ValidationGenerationTrait`).

Практическое правило:
- в `tests/acceptance.md` описываем **контракт сценариев и HTTP-ожидания**;
- в `generation/readme.md` описываем **механизм подготовки валидных данных** для этих сценариев.

---

## 7) Запуск

Основные команды:

```bash
# Полный acceptance suite
php vendor/bin/codecept run tests/acceptance

# Только PageAccessCest
php vendor/bin/codecept run tests/acceptance/PageAccessCest.php --verbose

# Фильтр по модели контроллера
TEST_CLASS_FILTER=Comps php vendor/bin/codecept run tests/acceptance/PageAccessCest.php --verbose
```

---

## 8) Типовые причины падений

1. В контроллере есть action из `accessMap()`, но нет соответствующего `testXxx()`.
2. Для сценария не хватает обязательных GET/POST параметров.
3. Ожидаемый HTTP-код не соответствует фактическому (например, 400/404 вместо 200).
4. Нет нужных данных в тестовой БД (не найдена модель по id/name).
5. Сценарий зависит от внешнего состояния (файлов, сервисов, сложной инициализации) и не помечен как skip.

Этот документ и `generation/readme.md` синхронизированы по формату сценариев и pipeline генерации.