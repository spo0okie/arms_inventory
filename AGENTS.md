# AGENTS.md — Контекст для ИИ-агентов

## 1. Краткий обзор проекта

**ARMS** (Automated Resource Management System) — система инвентаризации ИТ-инфраструктуры предприятия.

**Назначение:** учёт оборудования, ПО, лицензий, сетевой инфраструктуры, ИТ-сервисов, контрактов, партнёров.

**Технологический стек:**
- PHP 8.1+ / Yii2 Framework
- MariaDB 10 / ActiveRecord
- Bootstrap 5 / SCSS / Kartik-v виджеты
- Codeception (тесты)
- LDAP/AD, OpenAI API, Swagger интеграции

**Точка входа:** [`web/index.php`](web/index.php)

---

## 2. Архитектура

**Стиль:** MVC (Model-View-Controller) с базовыми классами.

**Ключевые компоненты:**
```
ArmsModel (base) → Domain Models (Comps, Techs, Services...)
ArmsBaseController (base) → Domain Controllers
```

**Связность:**
- Все модели наследуют [`models/ArmsModel.php`](models/ArmsModel.php) (955 строк)
- Все контроллеры наследуют [`controllers/ArmsBaseController.php`](controllers/ArmsBaseController.php)
- Many-to-Many связи через `voskobovich/yii2-linker-behavior`
- RBAC через `spo0okie/yii2-rbac-plus`
- Автоматическое журналирование истории (`afterSave`, `afterDelete`)

**Критичные зоны:**
- [`models/ArmsModel.php`](models/ArmsModel.php) — базовая модель с 4 трейтами
- [`models/traits/`](models/traits) — метаданные, связи, внешние данные
- [`components/llm/LlmClient.php`](components/llm/LlmClient.php) — интеграция OpenAI
- Синхронизация с удалёнными системами (`console/commands/SyncController.php`)

---

## 3. Стандарты кодинга

**Соглашения:**
- Таблицы БД: `snake_case`, множественное число (`comps`, `services`)
- Классы: `PascalCase`
- Методы/переменные: `camelCase`
- PHPDoc для всех public методов

**Структура директорий:**
```
controllers/ → MVC контроллеры
models/     → ActiveRecord + traits
views/      → {model-name}/{action}.php
components/ → виджеты, LLM, формы
helpers/    → утилиты
modules/    → REST API
console/    → CLI команды
migrations/ → БД миграции
tests/      → Codeception
```

**Наследование:**
- Все модели → `ArmsModel`
- Все контроллеры → `ArmsBaseController`
- Все миграции → `ArmsMigration`

---

## 4. Ограничения для ИИ

**DO:**
- Следовать существующим паттернам наследования
- Использовать `Yii::$app->` для доступа к компонентам
- Сохранять структуру файлов Views (`index`, `view`, `_form`, `item`, `ttip`)
- Документировать REST API через `@OA\` аннотации

**DONT:**
- НЕ изменять базовые классы без явного запроса
- НЕ рефакторить бизнес-логику
- НЕ предлагать архитектурные изменения
- НЕ писать код, нарушающий конвенции Yii2
- НЕ использовать сторонние библиотеки без согласования

**Стиль взаимодействия:**
- Минимум болтовни, максимум diff-ов и фактов
- При предположениях — пометка `ASSUMPTION`
- Не додумывать бизнес-логику

---

## 5. Открытые вопросы

- Требования к уровню качества кода? (средний/высокий)
- Допустимость изменений в базовых трейтах моделей?
- Приоритет функциональных зон для доработки?
