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
| `soft-list` | `SwListType` | `extends TextType` | `string + custom closure` (JSON-объекты ПО через запятую, без массива) |
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

## Резолвинг типа и строгость (`getAttributeTypeClass`)

Тип атрибута определяет `ArmsModel::getAttributeTypeClass($attribute)` в порядке:

1. явный `typeClass` в `attributeData()`;
2. `alias` в `attributeData()` — тип берётся у целевого атрибута (`'num' => ['alias'=>'employee_id']`);
3. детерминированные правила резолвера — по имени и правилам валидации
   (`id/count → integer`, `ip(s)/mac(s)/urls`, `is_* → boolean`, `updated_at/created_at → datetime`,
   `*Count → integer`, `*Name(s) → string`, правила `integer/number/boolean/string` и т.п.);
4. если тип **не выводится** — бросает исключение.

**Дефолта нет намеренно:** невозможность вывести тип должна всплывать, а не молча превращаться
в `string`. Тест-энфорсер `tests/unit/types/ApiSchemaResolvableTest.php` перебирает все
API-атрибуты (read-поверхность `attributes() + extraFields()` и search-набор `$searchFields`
контроллеров) и падает со списком, если где-то тип не выводится — заставляя объявить
`typeClass`/`ref`/`linksSchema` явно (или убрать ошибочный атрибут).

---

## Три категории атрибутов: данные, ссылки, вычисляемые ссылки

Типы описывают **скалярные данные**. Связь с другой моделью — отдельная ось, **не тип**:

| Категория | Механизм | Схема API |
|---|---|---|
| **Данные (скаляр)** | `typeClass` в `attributeData` | `apiSchema()` типа |
| **Редактируемая ссылка** | `linksSchema` (FK-колонка / M2M) | `integer` / `array[integer]` (id) или объект через loader |
| **Read-only вычисляемая ссылка** | `ref` в `attributeData` | `object` / `array[object]` |

- **`linksSchema`** — персистентные редактируемые связи (FK, many-2-many, absorb, генерация форм).
- **`ref`** (`'ref' => TargetModel::class` `[, 'refMulti' => true]`) — атрибут-геттер, возвращающий
  объект(ы) при обращении: только вывод, без ввода/валидации/генерации. Обрабатывается в аннотации
  (трейт `AttributeAnnotationModelTrait`) и **не является типом** — деталей связи в классах `types/*` нет.

---

## API-схема (`apiSchema()`) — источник истины

`apiSchema()` типа — **единственный источник** схемы скалярного атрибута для OpenAPI:

- **read/write** (`generateRWAttributeAnnotation`) — база из `apiSchema()` + декорации
  (примеры/enum/описания) + структурные ветки `link`/`ref` в трейте;
- **search** (`generateSearchParameterAnnotation`) — та же `apiSchema()` + search-трансформации
  **в трейте, а не в типе** (search — операция, не грань типа): ссылка ищется как строка,
  `boolean` — как string-enum.

Старый строковый резолвер `getAttributeType()` на API-схеме больше **не используется**.

---

## Примечания по актуальному состоянию

- `apiSchema()` — источник истины для read+search API-схемы (строго, без fallback).
- `generate()` — используется `generation/ModelFactory` для тестовых данных.
- `rules()` типов — объявлены, но пока **не** подключены в `Model::rules()` (задел).
- `renderInput()` / `renderOutput()` — объявлены, но пока **не** используются формами/гридами
  (UI ещё на `getAttributeType()`).
- `gridColumnClass()` — пока везде возвращает `null`.
- Для `JsonType`, `UrlsType`, `SerialNumberType`, `IpsType`, `IpNetType` часть поведения — через наследование.

---

## Статус внедрения и дальнейшие шаги

### Сделано

- `typeClass` массово в `attributeData()`; резолвер **строгий**, покрыт энфорсером.
- `generate()` — генерация тестовых значений.
- `apiSchema()` — **источник истины** для read+search API-схемы (флип `switch → apiSchema` завершён).
- Категории ссылок разведены: `linksSchema` (редактируемые), `ref` (read-only вычисляемые).

### Дальше

1. **Рендеры:** `renderInput()` (формы, `ActiveField::autoInput`) и `renderOutput()` / грид (`DynaGridWidget`).
2. **Правила:** подключить `rules()` типов в валидацию модели (или отказаться от метода).
3. **`gridColumnClass()`:** наполнить и подключить в GridView.
4. **Убрать `getAttributeType()`** после переезда UI-потребителей
   (`ActiveField`, `DynaGridWidget`, `ModelFieldWidget`, `searchHint`, `ModelHelper`) на типы.
