# Генеративный механизм ARMS

Система динамической генерации валидных моделей.
Используется для генерации тестовых данных для автоматического тестирования UI.

## Быстрый старт

```bash
# Запуск acceptance-тестов с генерацией моделей
vendor/bin/codecept run acceptance PageAccessCest --verbose
```

---

## Архитектура

### Pipeline генерации моделей

```text
ModelFactory::create()
    └── createOnce() [c retry при ошибках]
            ├── instantiate model
            ├── generateAttributes() → AttributeContext → тип атрибута → generate()
            ├── applyPreset()       [бизнес-сценарии через Model::roles()]
            ├── applyOverrides()    [явные переопределения]
            ├── model->afterGenerate() [бизнес-логика из ValidationGenerationTrait]
            ├── validate() [c retry при ошибках]
            └── save() [c retry при ошибках]
```

В настоящий момент генерация работает с 1 попыткой валидации и сохранения (без retry), так как генерация достаточно стабильная. Но механизм retry оставлен на всякий случай раз уж он уже реализован.

### Разделение ответственности

```text
Типы атрибутов (types/*Type)     → тупая генерация значений по правилам
Model::afterGenerate()           → бизнес-логика модели 
Model::roles()                   → умный preset с бизнес-сценариями
ModelFactory                     → оркестратор процесса
```

---

## ModelFactory

**Файл:** [`ModelFactory.php`](ModelFactory.php)

### Использование

```php
// Базовое создание
$model = ModelFactory::create(Model::class);

// Пустая модель (не-required атрибуты пустые)
$model = ModelFactory::create(Model::class, ['empty' => true]);

// С переопределениями
$model = ModelFactory::create(Model::class, ['overrides' => ['name' => 'test']]);

// С ролью (preset)
$model = ModelFactory::create(Techs::class, ['role' => 'pc']);

// С указанием seed (детерминизм)
$model = ModelFactory::create(Model::class, ['seed' => 42]);

// Без сохранения в БД
$model = ModelFactory::create(Model::class, ['save' => false]);

// С указанием глубины связей
$model = ModelFactory::create(Model::class, ['maxDepth' => 7]);
```

### Опции

| Опция             | Тип         | По умолчанию | Описание                       |
| ----------------- | ----------- | ------------ |------------------------------- |
| `empty`           | bool        | `false`      | Генерировать nullable значения |
| `role`            | string/null | `null`       | Preset из `Model::roles()`     |
| `overrides`       | array       | `[]`         | Явные значения атрибутов       |
| `save`            | bool        | `true`       | Сохранять в БД                 |
| `seed`            | int/null    | `null`       | Seed для детерминизма          |
| `maxDepth`        | int         | `4`          | Максимальная глубина связей    |
| `validateRetries` | int         | `1`          | Retry при валидации            |
| `saveRetries`     | int         | `1`          | Retry при сохранении           |

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
        public readonly bool         $required = false, // обязательная связь для текущей роли
    ) {}

    public function generatorConfig(): array    // конфиг генерации из attributeData
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

- Глубина генерации связей ограничивается через `depth` / `maxDepth`
- При достижении `maxDepth` создаются только обязательные связи
- Self-reference поддерживается только как **nullable** связи

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

Preset — это бизнес-сценарий создания модели через `Model::roles()`.
Callback роли получает модель и **её текущий `GenerationContext`**:
`empty`, `seed`, `depth`, `maxDepth` и RNG сохраняются в рамках исходной генерации.
Роль применяется после генерации атрибутов, перед overrides, `afterGenerate()` и валидацией.

Например, локальный VLAN должен иметь L2-домен, хотя для глобального резерва домен необязателен:

```php
// NetVlans
public static function roles(): array
{
    return [
        'local' => static function (self $model, GenerationContext $context): void {
            // Заполняем только нужную связь с исходными seed и глубиной.
            ModelFactory::generateAttribute(new AttributeContext(
                attribute: 'domain_id',
                model: $model,
                generationContext: $context,
                empty: false,
                required: true,
            ));
        },
    ];
}
```

```php
// Явный выбор роли при создании VLAN.
$vlan = ModelFactory::create(NetVlans::class, ['role' => 'local']);

// Выбор роли связанной модели в Networks::$linksSchema.
public $linksSchema = [
    'vlan_id' => [NetVlans::class, 'networks_ids', 'role' => 'local'],
];
```

`AttributeContext::$required` делает связь обязательной **только для этого вызова генератора**,
не меняя `rules()` модели. Как обычная required-связь, она создаётся и на границе `maxDepth`;
вложенная модель получает увеличенную глубину и исходный лимит, поэтому её необязательные
связи на предельной глубине не разворачиваются. Уже заполненная связь сохраняется.
Сам `empty: false` без `required: true` не отменяет ограничение `maxDepth`.

**Требования:**

- Идемпотентность
- Приводит модель к валидному состоянию (callback возвращает `void`)
- Не ломает генерацию
- Для связанных атрибутов использует текущий контекст, не начинает независимую генерацию с новым seed и глубиной

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

Сложная бизнес-логика, которая проверяется нетривиальными правилами в `rules()`, должна быть реализована в `afterGenerate()`: через вызов методов для соответствующих правил (например, `applyRequireOneOfRules()`).

```php
// Вызывается после генерации атрибутов, перед валидацией
public function afterGenerate(GenerationContext $context, array $options): void
{
    // Пример: applyRequireOneOfRules()
}
```

---

## Acceptance-тесты

### Структура

```text
tests/acceptance/PageAccessCest.php  →  Сканирует все контроллеры
       ↓
controllers/*Controller.php          →  Извлекает соответствующие action методы
       ↓
action*()                            →  Для каждого action ищет test*() метод
       ↓
test*()                              →  Возвращает массив тестовых сценариев
```

### Формат сценария

```php
public function testIndex(): array
{
    return [
        [//один из сценариев. их может быть несколько для одного action
            'name'     => 'default',                  //пояснение сценария   (например, для разных сценариев)
            'route'    => '{controller}/{action}',    //тестовый маршрут     (по умолчанию '{controller}/{action}')
            'GET'      => [],                         //GET параметры        (по умолчанию [] → без параметров)
            'POST'     => null,                       //POST параметры       (по умолчанию null → не отправлять POST)
            'response' => [404,200],                  //ожидаемый код ответа (по умолчанию 200)
            'skip'     => false,                      //пропустить сценарий  (по умолчанию false)
            'reason'   => '',                         //причина пропуска     (только для skip=true, по умолчанию пустая строка)
        ],
    ];
}
```

Для того чтобы подготовить данные для тестов используется getTestData() метод, который возвращает массив с данными для тестов.
Этот метод возвращает массив моделей, часть из которых может быть сохранена в БД для использования ссылок на них через параметры, а часть может быть просто сгенерирована без сохранения для получения их атрибутов.

```php
public function getTestData() {
    $class=$this->modelClass;
    if (empty(static::$testDataCache[$class])) {
        //пустая модель для проверки отображения при отсутствии данных
        static::$testDataCache[$class]['empty']=        ModelFactory::create($class,['empty'=>true]);
        //полностью заполненная модель для проверки отображения всех данных
        static::$testDataCache[$class]['full']=         ModelFactory::create($class,['empty'=>false]);
        //какую модель обновлять
        static::$testDataCache[$class]['update']=       ModelFactory::create($class,['empty'=>true]);
        static::$testDataCache[$class]['update-data']=  ModelFactory::create($class,['empty'=>false,'save'=>false]);
        //какую модель удалять
        static::$testDataCache[$class]['delete']=       ModelFactory::create($class,['empty'=>true]);
        //данные для теста создания модели
        static::$testDataCache[$class]['create']=       ModelFactory::create($class,['empty'=>false,'save'=>false]);
        //данные для теста валидации модели
        static::$testDataCache[$class]['validate']=     ModelFactory::create($class,['empty'=>true]);
        static::$testDataCache[$class]['validate-data']=ModelFactory::create($class,['save'=>false]);
    }
    return static::$testDataCache[$class];
}
```

---

## Тесты

```bash

# Проверка детерминизма генерации моделей
vendor/bin/codecept run unit models/DeterministicGenerationTest --verbose

# Запуск всех тестов генерации типов атрибутов
vendor/bin/codecept run unit types/AttributeTypeForGenerationTest --verbose

# Запуск тестов успешной генерации моделей
vendor/bin/codecept run unit models/ModelGenerationTest --verbose

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
