# Идея генеративного механизма создания моделей

## Цель

Перейти от:

```text
SQL dump + фикстуры
```

к:

```text
динамическая генерация валидных моделей → проверка UI (200 / 302)
```

---

## Общая архитектура

## Pipeline (актуальный) ✅ РЕАЛИЗОВАНО

```text
create()

for retry:
    createOnce()

createOnce():
1. instantiate model ✅
2. generate attributes (incl. relations) ✅
3. apply presets (role) ✅
4. apply overrides ✅
5. ModelResolver::afterGenerate() ✅
6. validate ✅
7. save ✅
```

**Реализация:** [`ModelFactory.php`](../generation/ModelFactory.php)

---

## Основные компоненты

---

### Attribute Generators ✅

#### Интерфейс

```php
interface GeneratorInterface
{
    public function generate(AttributeContext $context): mixed;
}
```

---

#### Принципы

```text
- не static
- stateless
- не обращаются к БД
- не знают про модель целиком
- не содержат бизнес-логики
```

---

### Attribute Types ✅

Типы атрибутов (`app\types\*Type`) отвечают за:

```text
- рендеринг input/output
- API schema
- генерацию данных
- Grid column классы
```

Каждый тип реализует `AttributeTypeInterface` который включает `GeneratorInterface`.

---

### GeneratorResolver ❌ УДАЛЕН

Раньше существовал отдельный класс `GeneratorResolver` который выбирал генератор по типу атрибута.

**Теперь:** ✅
- Типы атрибутов сами содержат логику генерации
- `ModelFactory` напрямую использует `getAttributeTypeForGeneration()` 
- Выбор типа происходит по аналогии с `getAttributeType()` но бросает исключение если тип не определён
- Каждый тип атрибута реализует `AttributeTypeInterface` который включает `GeneratorInterface`

**Реализация:** [`types/`](../types/) — все типы атрибутов содержат методы генерации

---

### ValidationGenerationTrait ✅ РЕАЛИЗОВАНО

Бизнес-логика генерации вынесена в трейт модели [`ValidationGenerationTrait`](../models/base/traits/ValidationGenerationTrait.php).

**Методы:**
- `afterGenerate()` — главный метод, вызываемый после генерации атрибутов
- `applyRequireOneOfRules()` — обрабатывает правила `validateRequireOneOf`
- `exist` в генерации не применяется: FK создаются через relations (`linksSchema`)

**Принцип работы:**
```text
ModelFactory::createOnce():
  → generateAttributes()
  → applyPreset()
  → applyOverrides()
  → model->afterGenerate() ✅ БИЗНЕС-ЛОГИКА МОДЕЛИ
  → validate()
  → save()
```

**Преимущества:**
- Бизнес-логика находится в модели (SRP)
- ModelFactory только координирует процесс
- Модели могут переопределять `afterGenerate()` для кастомной логики
- Улучшенная тестируемость

---

### Тестирование ✅ ЧАСТИЧНО

```text
GeneratorResolverTest: → TypeGenerationTest ❌ УДАЛЕН (вместе с GeneratorResolver)
- все атрибуты имеют typeClass ✅
- все typeClass могут генерировать значения ✅
```

---

## Context (ключевая часть)

---

### GenerationContext ✅

```php
class GenerationContext
{
    public function __construct(
        public readonly bool $empty = false,
        public readonly int $seed = 0,
        public readonly int $depth = 0,
        public readonly int $maxDepth = 2,
    ) {}
}
```

---

### AttributeContext ✅

```php
class AttributeContext
{
    public function __construct(
        public readonly string $attribute,
        public readonly bool $empty = false,
        public readonly ArmsModel $model,
        public readonly GenerationContext $generationContext,
    ) {}

    public function generatorConfig(): array
    public function isNullable(): bool
}

```

---

### Преимущества context

```text
- типобезопасность
- расширяемость без breaking changes
- нет "магических массивов"
- единая точка передачи состояния
```

---

## ModelFactory ✅ РЕАЛИЗОВАНО

**Файл:** [`ModelFactory.php`](ModelFactory.php)

---

### Использование

```php
$model = ModelFactory::create(Model::class);

$model = ModelFactory::create(Model::class, [], [
    'empty' => true,
]);

$model = ModelFactory::create(Model::class, [], [
    'overrides' => ['name' => 'test'],
]);

$model = ModelFactory::create(Techs::class, [], [
    'role' => 'pc',
]);
```

---

### Возможности ✅ РЕАЛИЗОВАНО

```text
✔ генерация атрибутов по типам ✅
✔ presets (roles) ✅
✔ overrides ✅
✔ детерминизм (seed) ✅
✔ retry через полную регенерацию ✅
✔ подготовка к relations (depth) ✅
```

**Реализация:** [`ModelFactory::create()`](ModelFactory.php#L58-L117), [`ModelFactory::generateAttributes()`](ModelFactory.php#L126-L217), [`ModelFactory::generateRelation()`](ModelFactory.php#L144-L238)

---

## ⚠️ Ограничения текущей реализации

```text
- генераторы используют mt_rand (глобальное состояние) ⚠️
- нет защиты от циклов (visited) ⚠️
- ссылки на внешние классы (не ArmsModel) не создаются ⚠️
- при maxDepth обязательные связи: берётся существующая запись, иначе ошибка ⚠️
```

**Файл:** [`ModelFactory.php`](ModelFactory.php#L422-L476)

---

## 🔗 Relations ✅ РЕАЛИЗОВАНО (БАЗОВО)

**Текущий статус:** Базовая реализация работает, есть ограничения ⚠️

---

### linksSchema

```php
public function linksSchema(): array
{
    return [
        'arm_id' => [
            'class' => Techs::class,
            'role' => 'pc',
        ],
    ];
}
```

---

### Принципы ✅ РЕАЛИЗОВАНО

```text
- генераторы НЕ создают модели ✅
- ModelFactory управляет связями ✅ (generateRelation)
- используется depth для ограничения ✅
- при maxDepth для обязательной связи берётся существующая запись (если нет — ошибка) ✅
```

**Реализация:** [`ModelFactory::generateRelation()`](../generation/ModelFactory.php#L144-L238), [`Techs::$linksSchema`](../models/Techs.php#L169-L202)

---

### Планируемый этап pipeline ✅ РЕАЛИЗОВАНО

```text
generateAttributes ✅
→ relations (generateAttribute) ✅
→ presets ✅
→ overrides ✅
→ validate ✅
→ save ✅
```

**Текущий pipeline:** [`ModelFactory::createOnce()`](../generation/ModelFactory.php#L422-L476)

---

### Ограничения (важно) ⚠️

```text
- нет сложных графов ⚠️
- нет bidirectional sync ⚠️
- нет visited (пока) ⚠️
```

**Что работает:**
- ✅ Простые связи (один-ко-многим) через `linksSchema`
- ✅ Many-to-many через `_ids` атрибуты
- ✅ Автоматическое создание связанных моделей
- ✅ Ограничение глубины через `depth` и `maxDepth`

**Что не работает:**
- ⚠️ Сложные графы связей (циклические зависимости)
- ⚠️ Двусторонняя синхронизация (bidirectional)
- ⚠️ Защита от зацикливания (отсутствует `visited` tracking)

---

## 🎭 Presets (roles) ✅ РЕАЛИЗОВАНО

**Принцип работы:** Presets определяют бизнес-сценарии создания моделей через метод `roles()` в моделях.

---

## Назначение

```text
создают бизнес-сценарии
```

---

### Пример ✅ РЕАЛИЗОВАНО

```php
public static function roles(): array
{
    return [
        'pc' => function($m) {
            $type = ModelFactory::create(TechType::class, [], [
                'overrides' => ['is_comp' => true]
            ]);

            $model = ModelFactory::create(TechModel::class, [], [
                'overrides' => ['tech_type_id' => $type->id]
            ]);

            $m->model_id = $model->id;
        }
    ];
}
```

**Примеры использования:** [`Techs::roles()`](../models/Techs.php) (требуется добавить в модель)

---

### Требования ✅ РЕАЛИЗОВАНО

```text
✔ идемпотентность ✅
✔ возвращает валидную модель ✅
✔ не ломает генерацию ✅
```

**Реализация:** [`ModelFactory::applyPreset()`](../generation/ModelFactory.php#L249-L265)

---

## 📊 Признак "заполняемого" атрибута ✅

**Механизм:**
```text
$model->safeAttributes() ✅
```

**Фильтры:**
```text
- не system fields (id, timestamps) ✅
- не readOnly ✅
```

**Реализация:** [`ModelFactory::generateAttributes()`](../generation/ModelFactory.php#L126-L217) использует `safeAttributes()` для определения атрибутов для генерации

---

## 🎲 Random / Seed ✅ РЕАЛИЗОВАНО

---

### Правила ✅ РЕАЛИЗОВАНО

```text
- seed задаётся на уровне ModelFactory ✅
- логируется ✅
- используется для детерминизма генераторов ✅
```

**Формула:**
```text
seed + hash(model + attribute) ✅
```

**Реализация:** [`ModelFactory::create()`](../generation/ModelFactory.php#L70-L78), [`ModelFactory::generateAttributes()`](../generation/ModelFactory.php#L196-L197)

---

## 🧪 Стратегия тестирования ✅ ЧАСТИЧНО

---

### Structural tests ⚠️ ТРЕБУЕТСЯ

```text
- все safe атрибуты имеют type/generator ⚠️
- все типы покрыты генераторами ⚠️
```

---

### Generation tests ✅ РЕАЛИЗОВАНО

```text
ModelFactory::create() != null ✅
```

**Тест:** [`AttributeTypeForGenerationTest.php`](../tests/unit/types/AttributeTypeForGenerationTest.php)

---

### Persistence tests ⚠️ ТРЕБУЕТСЯ

```text
model->save() успешно ⚠️
```

---

### Acceptance tests ⚠️ ТРЕБУЕТСЯ

```text
controller → 200 / 302 ⚠️
```

---

### Формат сценариев для контроллеров (генеративные acceptance)

**Правило:** для каждого `actionXxx()` есть отдельный `testXxx()` в контроллере (наследуется и может быть переопределён).

**Где описывать:** в самом контроллере (или в базовом `ArmsBaseController` для общих CRUD).

**Сигнатура:**
```php
public static function testIndex(): array
```

**Сценарий — ассоциативный массив**. Минимально:
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

**Поля сценария:**
```text
name            string   обязательный, уникальный в пределах метода
route           string   опционально, иначе "{controllerId}/{action}"
GET             array    GET параметры (опционально)
POST            array    POST параметры (если есть → будет POST)
response        int|array ожидаемый код/диапазон (по умолчанию 200)
skip            bool     пропустить сценарий
reason          string   причина пропуска
role            string   роль/режим (опционально, если используется)
saveModel       array    сохранить модель в контекст (storeAs/model)
dropReverseLinks array   удалить обратные связи перед delete
```

**Макросы в параметрах:**
```text
{anyId} {otherId} {anyName}
{anyModelParams} {otherModelParams}
{replacedModelParams} {deletedModelParams}
```

**Переопределение:** если дочерний контроллер объявляет `testXxx()`, он полностью заменяет базовый.

---

## 🚀 Следующие шаги

---

### Relations ✅ РЕАЛИЗОВАНО (БАЗОВО)

```text
- linksSchema ✅
- depth ✅
- generateRelation() ✅
```

**Текущее состояние:** Базовая реализация работает, есть ограничения для сложных графов

---

### Ограничение циклов ❌ НЕ РЕАЛИЗОВАНО

```text
visited (позже) ❌
```

**Проблема:** При генерации сложных графов возможны циклические зависимости

---

### ModelResolver ❌ НЕ РЕАЛИЗОВАНО

```text
Должен контролировать что при генерации соблюдены инварианты модели ❌
```

**Задача:** Реализовать валидацию бизнес-инвариантов после генерации атрибутов

---

## 🧠 Ключевая идея системы ✅ РЕАЛИЗОВАНО

```text
генерация — тупая ✅
preset — умный ✅
factory — orchestrator ✅
model → afterGenerate() — бизнес-логика ✅
retry — через пересоздание ✅
```

**Реализация:**
- Генерация атрибутов делегирована типам: [`types/`](../types/)
- Presets определяют бизнес-сценарии: `Model::roles()`
- Factory управляет процессом: [`ModelFactory.php`](ModelFactory.php)
- Retry через полную регенерацию с новым seed: [`ModelFactory::create()`](ModelFactory.php#L74-L105)

---

## 📌 Итог

**Текущая система:**

```text
✔ воспроизводимая ✅ (детерминизм через seed)
✔ расширяемая ✅ (новые типы через AttributeTypeInterface)
✔ готова к связям ✅ (базовая реализация relations)
```

**Компоненты:**
- ✅ [`GenerationContext`](context/GenerationContext.php) и [`AttributeContext`](context/AttributeContext.php) — контекст генерации
- ✅ [`ModelFactory`](ModelFactory.php) — фабрика моделей
- ✅ [`GeneratorInterface`](generators/GeneratorInterface.php) — интерфейс генераторов
- ✅ [`AttributeTypeInterface`](../types/AttributeTypeInterface.php) — типы атрибутов с генерацией
- ✅ [`ModelGenerationResult`](ModelGenerationResult.php) — результат генерации
- ✅ [`ModelGenerationException`](exceptions/ModelGenerationException.php) — исключения

**Статус:** Базовый функционал реализован ⚠️ (требуются доработки для сложных сценариев)

**Что работает:**
- ✅ Генерация атрибутов по типам
- ✅ Presets (roles) для бизнес-сценариев
- ✅ Overrides для переопределения значений
- ✅ Детерминизм через seed
- ✅ Retry через полную регенерацию
- ✅ Базовые связи (linksSchema, depth, generateRelation)

**Требует доработки:**
- ⚠️ ModelResolver для валидации инвариантов
- ⚠️ Защита от циклов (visited tracking)
- ⚠️ Расширенное тестовое покрытие
- ⚠️ Acceptance tests для контроллеров




