# Acceptance тесты проекта ARMS (генеративные)

## Идея
Тесты строятся **динамически** из описаний сценариев в контроллерах.

Pipeline:
```
контроллеры -> action* -> test* (DataProvider) -> сценарии -> тест
```

## Где описывать сценарии
В контроллере (или унаследовано из `ArmsBaseController`) должен быть отдельный метод:

```php
public static function testIndex(): array
```

Каждый `actionXxx()` соответствует `testXxx()`.

## Формат сценария
Сценарий — это массив с описанием запроса и ожиданий:

```php
return [
    [
        'name' => 'default',
        // route не обязателен: по умолчанию "{controllerId}/{action}"
        'GET' => [],
        'response' => 200,
    ],
];
```

Поддерживаемые поля:
- `name` (string) — идентификатор сценария
- `route` (string) — переопределение маршрута
- `GET` (array) — GET параметры
- `POST` (array) — POST параметры (если есть → POST)
- `response` (int|array) — ожидаемый код / диапазон
- `skip` (bool) — пропуск сценария
- `reason` (string) — причина пропуска
- `saveModel` / `dropReverseLinks` — контекст CRUD-конвейера

## Макросы параметров
- `{anyId}`, `{otherId}`, `{anyName}`
- `{anyModelParams}`, `{otherModelParams}`
- `{replacedModelParams}`, `{deletedModelParams}`

## Сортировка сценариев
Для корректного CRUD-конвейера сценарии сортируются внутри контроллера:
```
delete -> validate -> update -> create -> view
```

## Где лежит тест
- `tests/acceptance/PageAccessCest.php`

