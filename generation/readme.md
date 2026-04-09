# Генеративные тесты ARMS

Система динамической генерации валидных моделей для автоматического тестирования UI.

## Быстрый старт

```bash
# Запуск всех unit-тестов генерации
vendor/bin/codecept run unit models/DeterministicGenerationTest --verbose

# Запуск всех unit-тестов генерации моделей
vendor/bin/codecept run unit models/ModelGenerationTest --verbose

# Запуск всех unit-тестов типов атрибутов
vendor/bin/codecept run unit types/AttributeTypeForGenerationTest --verbose

# Запуск acceptance-тестов
vendor/bin/codecept run acceptance PageAccessCest --verbose
```

---

## Архитектура

```
SQL dump + фикстуры  →  Динамическая генерация  →  Проверка UI (200/302)
```

### Pipeline

```text
ModelFactory::create()
    └── createOnce() [c retry при ошибках]
            ├── instantiate model
            ├── generateAttributes() → AttributeContext → тип атрибута → generate()
            ├── applyPreset()       [бизнес-сценарии через Model::roles()]
            ├── applyOverrides()    [явные переопределения]
            ├── model->afterGenerate() [бизнес-логика из ValidationGenerationTrait]
            ├── validate()
            └── save() [c retry при ошибках]
```

### Разделение ответственности

```
Типы атрибутов (types/*Type)     → тупая генерация значений по правилам
Model::roles()                   → умный preset с бизнес-сценариями
ModelFactory                     → оркестратор процесса
Model::afterGenerate()           → бизнес-логика модели
```

---

## ModelFactory

**Файл:** [`ModelFactory.php`](ModelFactory.php)

### Использование

```php
// Базовое создание
$model = ModelFactory::create(Model::class);

// Пустая модель (nullable атрибуты)
$model = ModelFactory::create(Model::class, [], ['empty' => true]);

// С переопределениями
$model = ModelFactory::create(Model::class, [], ['overrides' => ['name' => 'test']]);

// С ролью (preset)
$model = ModelFactory::create(Techs::class, [], ['role' => 'pc']);

// С указанием seed (детерминизм)
$model = ModelFactory::create(Model::class, [], ['seed' => 42]);

// Без сохранения в БД
$model = ModelFactory::create(Model::class, [], ['save' => false]);

// С ограничением глубины связей
$model = ModelFactory::create(Model::class, [], ['maxDepth' => 1]);
```

### Опции

| Опция | Тип | По умолчанию | Описание |
|-------|-----|--------------|----------|
| `empty` | bool | `false` | Генерировать nullable значения |
| `role` | string\|null | `null` | Preset из `Model::roles()` |
| `overrides` | array | `[]` | Явные значения атрибутов |
| `save` | bool | `true` | Сохранять в БД |
| `seed` | int\|null | `null` | Seed для детерминизма |
| `maxDepth` | int | `2` | Максимальная глубина связей |
| `validateRetries` | int | `1` | Retry при валидации |
| `saveRetries` | int | `1` | Retry при сохранении |

---

## Контекст генерации

### GenerationContext

```php
class GenerationContext
{
    public function __construct(
        public readonly bool  $empty,      // пустая модель
        public readonly int   $seed,       // детерминизм
        public readonly int   $depth,      // текущая глубина
        public readonly int   $maxDepth,   // максимальная глубина
    ) {}
}
```

### AttributeContext

```php
class AttributeContext
{
    public function __construct(
        public readonly string       $attribute,
        public readonly bool         $empty,
        public readonly ArmsModel    $model,
        public readonly GenerationContext $generationContext,
    ) {}

    public function generatorConfig(): array    // конфиг из rules
    public function isNullable(): bool          // можно ли null
    public function randomizer(): Randomizer    // изолированный RNG
}
```

### Изолированный RNG

Каждый атрибут получает **отдельный генератор случайных чисел**:

```php
$seed = $context->seed
    + crc32(get_class($model))
    + crc32($attribute)
    * ($context->depth + 1);

$randomizer = new Randomizer(new Mt19937($seed));
```

**Свойства:**
- Одинаковый seed → одинаковые модели
- Без глобального состояния (не использует `mt_srand`)
- Глубина влияет на seed (разные значения для вложенных моделей)

---

## Связи (Relations)

### linksSchema

Модели объявляют связи через `linksSchema()`:

```php
public static function linksSchema(): array
{
    return [
        'tech_type_id' => [
            'class' => TechType::class,
            // 'required' => false,  // по умолчанию
        ],
        'parent_id' => [
            'class' => self::class,        // self-reference
            'required' => false,           // ВАЖНО: только nullable!
        ],
    ];
}
```

### Принципы

- Генератор **не создаёт модели напрямую** — `ModelFactory` управляет связями
- Глубина ограничивается через `depth` / `maxDepth`
- При `maxDepth` обязательные связи берут существующую запись из БД
- Self-reference поддерживается только как **nullable** связи
- Циклы предотвращаются через `visited` tracking

### Self-reference

```php
// Допустимо: nullable self-reference
public function linksSchema(): array
{
    return [
        'parent_id' => [
            'class' => self::class,
            'required' => false,  // ✅ Рекурсия ограничена maxDepth
        ],
    ];
}

// Недопустимо: required self-reference → Exception
public function linksSchema(): array
{
    return [
        'parent_id' => [
            'class' => self::class,
            'required' => true,   // ❌ Вызовет ModelGenerationException
        ],
    ];
}
```

---

## Presets (Roles)

Preset — это бизнес-сценарий создания модели через `Model::roles()`:

```php
public static function roles(): array
{
    return [
        'pc' => function($model) {
            // Создаём связанные модели с нужными параметрами
            $type = ModelFactory::create(TechType::class, [], [
                'overrides' => ['is_comp' => true]
            ]);
            $model->type_id = $type->id;
        },
    ];
}
```

```php
// Использование
$pc = ModelFactory::create(Techs::class, [], ['role' => 'pc']);
```

**Требования:**
- Идемпотентность
- Возвращает валидную модель
- Не ломает генерацию

---

## Типы атрибутов

Типы (`app\types/*Type`) реализуют `AttributeTypeInterface`, который включает `GeneratorInterface`:

```php
interface GeneratorInterface
{
    public function generate(AttributeContext $context): mixed;
}
```

**Принципы генераторов:**
- Stateless
- Не обращаются к БД
- Не знают про модель целиком
- Не содержат бизнес-логики

---

## Бизнес-логика генерации

[`ValidationGenerationTrait`](models/base/traits/ValidationGenerationTrait.php) предоставляет методы для бизнес-логики:

```php
// Вызывается после генерации атрибутов, перед валидацией
public function afterGenerate(GenerationContext $context, array $options): void
{
    // Пример: applyRequireOneOfRules()
    // Пример: заполнение вычисляемых полей
}
```

---

## Acceptance-тесты

### Структура

```
tests/acceptance/PageAccessCest.php  →  Сканирует все контроллеры
       ↓
controllers/*Controller.php            →  Описывает test*() сценарии
       ↓
action*()                              →  Соответствующие action методы
```

### Формат сценария

```php
public function testIndex(): array
{
    return [
        [
            'name'     => 'default',
            'GET'      => [],
            'response' => 200,
        ],
    ];
}
```

### Поля сценария

| Поле | Тип | Описание |
|------|-----|----------|
| `name` | string | Уникальный идентификатор |
| `route` | string | Маршрут (по умолчанию `{controller}/{action}`) |
| `GET` | array | GET параметры |
| `POST` | array | POST параметры (если есть → POST) |
| `response` | int\|array | Ожидаемый код (или диапазон `[404,200]`) |
| `skip` | bool | Пропустить сценарий |
| `reason` | string | Причина пропуска |
| `role` | string | Preset для генерации |
| `saveModel` | array | Сохранить модель в контекст |
| `dropReverseLinks` | array | Удалить обратные связи |

### Макросы

```text
{anyId}                  → id первой модели
{otherId}               → id второй модели
{anyName}               → name первой модели
{anyModelParams}        → все safe атрибуты первой модели
{otherModelParams}      → все safe атрибуты второй модели
```

### Базовые сценарии (ArmsBaseController)

```php
testIndex()      // Создать 3 модели, GET → 200
testView()       // GET с id → 200
testCreate()     // GET → 200, POST → 201
testUpdate()     // GET → 200, POST → 202
testDelete()     // POST с id пустой модели → 302
testItem()       // GET с id → 200
testItemByName() // GET с name → 200
testTtip()       // GET с id → 200
testValidate()   // POST данные → 200
testAsyncGrid()  // GET → [404,200] (TODO: убрать 404)
```

---

## Тесты

### Unit-тесты

| Файл | Что проверяет |
|------|--------------|
| `DeterministicGenerationTest.php` | Детерминизм: одинаковый seed → одинаковые модели |
| `ModelGenerationTest.php` | Все модели из `ModelHelper::getModelClasses()` создаются |
| `ModelTypeSafetyTest.php` | Все safe атрибуты имеют тип |
| `AttributeTypeForGenerationTest.php` | `getAttributeTypeForGeneration()` работает |

### Acceptance-тесты

```bash
# Все контроллеры
vendor/bin/codecept run acceptance PageAccessCest --verbose

# Конкретный контроллер
TEST_CLASS_FILTER=Comps vendor/bin/codecept run acceptance PageAccessCest --verbose
```

---

## Компоненты

| Компонент | Путь | Назначение |
|-----------|------|------------|
| ModelFactory | `generation/ModelFactory.php` | Фабрика моделей |
| GenerationContext | `generation/context/GenerationContext.php` | Контекст генерации |
| AttributeContext | `generation/context/AttributeContext.php` | Контекст атрибута |
| ModelGenerationResult | `generation/ModelGenerationResult.php` | Результат генерации |
| ModelGenerationException | `generation/exceptions/ModelGenerationException.php` | Исключения |
| ValidationGenerationTrait | `models/base/traits/ValidationGenerationTrait.php` | Бизнес-логика |

---

## Статус

| Компонент | Статус |
|-----------|--------|
| Генерация атрибутов | ✅ Готово |
| Детерминизм (seed) | ✅ Готово |
| Presets (roles) | ✅ Готово |
| Overrides | ✅ Готово |
| Связи (linksSchema) | ✅ Готово |
| Self-reference | ✅ Готово (nullable only) |
| Retry-механизм | ✅ Готово |
| Unit-тесты | ✅ Готово |
| Acceptance-тесты | ✅ Готово |

### Последний прогон

```
2026-04-06: php vendor/bin/codecept run acceptance PageAccessCest --fail-fast
  → OK, skipped: 90, assertions: 603
```
