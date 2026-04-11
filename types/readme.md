# Types

Классы из `types/` описывают **тип атрибута** и инкапсулируют:

- генерацию тестовых значений (`generate()`)
- базовую типовую валидацию (`rules()`)
- API-схему (`apiSchema()`)
- рендер ввода/вывода (`renderInput()` / `renderOutput()`)
- примеры (`samples()`)

Метаданные конкретного поля (`label`, `hint`, `required`, бизнес-ограничения) остаются в модели.

---

## Базовые контракты

### `AttributeTypeInterface`

Каждый тип реализует:

- `public static function name(): string`
- `public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed`
- `public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed`
- `public function apiSchema(): array`
- `public function gridColumnClass(): ?string`
- `public function samples(): array`
- `public function generate(AttributeContext $context): mixed`
- `public function rules(AttributeRuleContext $context): array`

### `RuleDefinition`

`RuleDefinition` — обёртка над правилом валидации типа.

- строковый валидатор (`'string'`, `'integer'`, `'match'` и т.д.) превращается в обычный Yii rule
- closure-валидатор получает доступ к модели через обёртку

### `AttributeRuleContext`

Контекст для генерации правил типа:

- `ArmsModel $model`
- `string $attribute`

---

## Текущий набор типов

| Тип (`name()`) | Класс | Наследование | Ключевая валидация |
|---|---|---|---|
| `string` | `StringType` | - | `string` |
| `text` | `TextType` | - | `string` |
| `integer` | `IntegerType` | - | `integer` |
| `float` | `FloatType` | - | `number` |
| `boolean` | `BooleanType` | - | `boolean` |
| `date` | `DateType` | - | `string + match YYYY-MM-DD` |
| `datetime` | `DatetimeType` | - | `string + match YYYY-MM-DD HH:MM[:SS]` |
| `ip` | `IpType` | - | `NetIps::validateInput + filterInput` |
| `ips` | `IpsType` | `extends IpType` | наследует правила `IpType` |
| `ipNet` | `IpNetType` | `extends IpType` | наследует правила `IpType` |
| `vlan` | `VlanType` | `extends IntegerType` | custom closure (число/диапазон, ограничения create/update) |
| `link` | `LinkType` | - | `integer` |
| `string[]` | `StringArrayType` | - | `each => string` |
| `json` | `JsonType` | `extends TextType` | наследует `string` от `TextType` |
| `soft-list` | `SoftListType` | `extends TextType` | `string + custom closure` (JSON-объекты ПО через запятую, без массива) |
| `hw-list` | `HwListType` | `extends TextType` | `string + custom closure` (JSON-объекты железа через запятую, без массива) |
| `urls` | `UrlsType` | `extends TextType` | наследует `string` от `TextType` |
| `macs` | `MacsType` | `extends TextType` | `string + filter(MacsHelper::fixList)` |
| `schedule` | `ScheduleType` | - | `string + custom validateSchedule()` |
| `schedule-day` | `ScheduleDayType` | - | `string(max=10) + день недели/def/дата` |
| `inv-num` | `InvNumType` | - | `string(max=16) + custom syntax + filter(formatInvNum)` |
| `color` | `ColorType` | - | `string(max=7) + match HEX` |
| `email` | `EmailType` | - | `string` |
| `hostname` | `HostnameType` | - | `string + filter(Domains::validateHostname)` |
| `phone` | `PhoneType` | - | `string` |
| `serial-number` | `SerialNumberType` | `extends StringType` | наследует `string` от `StringType` |

---

## Генерация

Все типы генерируют значения напрямую внутри `generate()`.

Отдельные generator-классы/`GeneratorResolver` для типов не используются.

Общий паттерн:

1. если `$context->empty === true` → вернуть пустое значение с учётом nullable
2. взять детерминированный RNG через `$context->randomizer()`
3. сгенерировать значение в формате типа

---

## Типовые правила vs бизнес-правила

В `types/*` должны находиться только правила формата/типа.

- ✅ формат даты, IP, расписания, MAC, inventory number
- ✅ нормализующие `filter`
- ❌ правила предметной области (уникальность в контексте бизнеса, межполевые зависимости модели, сценарные ограничения бизнес-процесса)

Бизнес-валидация остаётся в `rules()` конкретной модели.

---

## Пример интеграции в модель

```php
public function attributeData(): array
{
    return [
        'name' => [
            'label' => 'Название',
            'typeClass' => \app\types\StringType::class,
        ],
        'ip' => [
            'label' => 'IP адрес',
            'typeClass' => \app\types\IpType::class,
        ],
    ];
}
```

---

## Примечания по актуальному состоянию

- В проекте уже используются дополнительные типы `IpType`, `IpNetType`, `VlanType`.
- Для `JsonType`, `UrlsType`, `SerialNumberType`, `IpsType`, `IpNetType` часть поведения берётся через наследование, а не через собственные `rules()` в каждом классе.
- `gridColumnClass()` пока везде возвращает `null`.

---

## Статус внедрения и дальнейшие шаги

### Что уже сделано

- `typeClass` массово используется в `attributeData()` моделей.
- Типы содержат генерацию (`generate()`) и типовые правила (`rules()`).
- Интерфейс уже включает рендер (`renderInput`/`renderOutput`) и описание для API (`apiSchema`).

### Что важно держать в фокусе

1. **Консистентность правил:**
   - специализированные типы должны по возможности иметь профильные валидаторы;
   - бизнес-ограничения остаются в моделях.

2. **Прозрачность наследования:**
   - документировать случаи, где `rules()` наследуются из базового типа.

3. **Покрытие тестами:**
   - тесты на формат `generate()`;
   - тесты на срабатывание `rules()`;
   - тесты/аудит покрытия `typeClass` по моделям.

Итог: система типов находится в рабочей фазе; дальнейшая работа — стабилизация, выравнивание правил и поддержание документации в актуальном состоянии.
