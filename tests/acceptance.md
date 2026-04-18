# Acceptance тесты проекта ARMS (генеративные)

Документ описывает **как писать и поддерживать** `tests/acceptance/PageAccessCest.php`.

Смежный документ: `generation/readme.md` — описывает генерацию моделей (ModelFactory, roles, seed, linksSchema).  
Этот файл фокусируется на контракте сценариев контроллеров и правилах запуска acceptance.

---

## Идея и pipeline

Задача теста убедиться что UI часть приложения работает корректно и не ломается при изменениях.
Для этого проверяется что при передаче корректных GET/POST параметров, страница возвращает ожидаемый HTTP-код (200, 302, 400, 404 и т.д.).
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

## Где описывать сценарии

Для action, который участвует в автопроверке, в контроллере должен быть метод:

```php
public function testXxx(): array
```

Пример: для `actionView()` нужен `testView()`.

Важно:
- если action попадает в тестовый обход и `testXxx()` отсутствует, прогон падает с `Calling unknown method ...::testXxx()`;
- action можно исключить из автопроверки через `disabledTests()` или вернуть skip-сценарий.

---

## Формат сценария

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

---

## Базовые сценарии ArmsBaseController

Если контроллер наследуется от `ArmsBaseController`, то доступны сценарии описанные в нем:
Если в классе потомке был перекрыт/добавлен новый метод `actionXxx()`, то для него нужно описать `testXxx()`.

---

## Связь с generation/readme.md

`generation/readme.md` дополняет этот документ:
- откуда берутся валидные модели и атрибуты (`ModelFactory::create`);
- как работают `role`, `overrides`, `seed`, `maxDepth`;
- как учитывать `linksSchema()` и self-reference;
- где применять бизнес-логику (`afterGenerate`, `ValidationGenerationTrait`).

Практическое правило:
- в `tests/acceptance.md` описываем **контракт сценариев и HTTP-ожидания**;
- в `generation/readme.md` описываем **механизм подготовки валидных данных** для этих сценариев.

---

## Запуск

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

- В контроллере есть action из `accessMap()`, но нет соответствующего `testXxx()`.
- Для сценария не хватает обязательных GET/POST параметров.
- Ожидаемый HTTP-код не соответствует фактическому (например, 400/404 вместо 200).
- Сценарий зависит от внешнего состояния (файлов, сервисов, сложной инициализации) и не помечен как skip.

Этот документ и `generation/readme.md` синхронизированы по формату сценариев и pipeline генерации.