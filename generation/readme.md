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

## Pipeline (актуальный)

```text
create()

for retry:
    createOnce()

createOnce():
1. instantiate model
2. generate attributes
3. apply relations (WIP)
4. apply presets (role)
5. apply overrides
6. model resolver
7. validate
8. save (опционально)
```

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

### GeneratorResolver ✅

Отвечает за выбор генератора:

```text
1. generator.class из attributeData
2. fallback по type
```

Особенности:

```text
- кэширует экземпляры генераторов
- генераторы обязаны быть stateless
```

---

### Тестирование

```text
GeneratorResolverTest:
- все типы покрыты генераторами
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
        public readonly array $attributeData,
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

## ModelFactory ✅

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

### Возможности

```text
✔ генерация атрибутов по типам
✔ presets (roles)
✔ overrides
✔ детерминизм (seed)
✔ retry через полную регенерацию
✔ подготовка к relations (depth)
```

---

## ⚠️ Ограничения текущей реализации

```text
- генераторы используют mt_srand (глобальное состояние)
- нет защиты от циклов (visited)
```

---

## 🔗 Relations (в разработке)

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

### Принципы

```text
- генераторы НЕ создают модели
- ModelFactory управляет связями
- используется depth для ограничения
```

---

### Планируемый этап pipeline

```text
generateAttributes
→ applyRelations
→ presets
→ overrides
→ ModelResolve
```

---

### Ограничения (важно)

```text
- нет сложных графов
- нет bidirectional sync
- нет visited (пока)
```

---

## 🎭 Presets (roles)

---

## Назначение

```text
создают бизнес-сценарии
```

---

### Пример

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

---

### Требования

```text
✔ идемпотентность
✔ возвращает валидную модель
✔ не ломает генерацию
```

---

## 📊 Признак "заполняемого" атрибута

Используется:

```text
$model->safeAttributes()
```

Фильтры:

```text
- не system fields (id, timestamps)
- не readOnly
```

---

## 🎲 Random / Seed

---

### Правила

```text
- seed задаётся на уровне ModelFactory
- логируется
- используется для детерминизма генераторов
```

---

### Формула

```text
seed + hash(model + attribute)
```

---

## 🧪 Стратегия тестирования

---

### Structural tests

```text
- все safe атрибуты имеют type/generator
- все типы покрыты генераторами
```

---

### Generation tests

```text
ModelFactory::create() != null
```

---

### Persistence tests

```text
model->save() успешно
```

---

### Acceptance tests

```text
controller → 200 / 302
```

---

## 🚀 Следующие шаги

---

### Relations (обязательно)

```text
- linksSchema
- depth
- applyRelations()
```

---

### Ограничение циклов

```text
visited (позже)
```

---

### ModelResolver

```text
Должен контролировать что при генерации соблюдены инварианты модели
```

---

## 🧠 Ключевая идея системы

```text
генерация — тупая
preset — умный
factory — orchestrator
retry — через пересоздание
```

---

## 📌 Итог

Текущая система уже:

```text
✔ воспроизводимая
✔ расширяемая
✔ готова к связям
```
