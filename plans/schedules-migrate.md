# Выделение блока Schedules: анализ и план

*Дата: 2026-03-04 · Версия: 4.0*

---

## 1. Постановка задачи

### Контекст

Блок Schedules в ARMS вырос до значительного объёма (~50 файлов) и стал сложен
для навигации, понимания и тестирования. При этом он достаточно автономен по логике,
чтобы выделить его как отдельную структурную единицу внутри проекта.

### Цели

1. **Структурно выделить** блок Schedules для упрощения навигации и понимания кода
2. **Обеспечить отдельную документацию** блока
3. **Обеспечить независимое тестирование** блока

### Зафиксированные ограничения

- Блок остаётся **внутри monorepo** — вынос в отдельный репозиторий или Composer-пакет не рассматривается
- Переиспользование блока в других проектах **не требуется**
- Устранять зависимости между блоком и остальным приложением **не нужно** —
  связи с `Services`, `Acls`, `MaintenanceJobs`, `Attaches` остаются как есть
- Микросервисная архитектура **не рассматривается**

### Что это означает на практике

Задача сводится к **структурной реорганизации внутри монолита**: переместить файлы
в выделенную директорию, добавить документацию и настроить изолированный запуск тестов.
Это не требует создания `arms-base` как отдельного пакета и не требует разрыва
существующих зависимостей.

---

## 2. Список файлов блока

### Модели — 7 файлов
- `models/Schedules.php`
- `models/SchedulesEntries.php`
- `models/SchedulesSearch.php`
- `models/SchedulesEntriesSearch.php`
- `models/SchedulesHistory.php`
- `models/SchedulesEntriesHistory.php`
- `models/SchedulesAclSearch.php`

### Трейты — 2 файла
- `models/traits/SchedulesModelCalcFieldsTrait.php`
- `models/traits/ScheduleEntriesModelCalcFieldsTrait.php`

### Helpers — 1 файл
- `helpers/TimeIntervalsHelper.php`

### Контроллеры — 3 файла
- `controllers/SchedulesController.php`
- `controllers/SchedulesEntriesController.php`
- `controllers/ScheduledAccessController.php`

### Views — 22+ файлов
- `views/schedules/` — 16 файлов
- `views/schedules/week/` — 3 файла
- `views/schedules-entries/` — 9 файлов

### Миграции — 9 файлов
- `m200317_040048_create_table_schedules.php`
- `m200317_043845_alter_services_table.php`
- `m210614_063518_create_table_schedules.php`
- `m210614_150516_alter_table_schedules.php`
- `m210825_125020_create_table_access.php`
- `m220402_185406_alter_table_schedules.php`
- `M240225074103HistoryJournalsAcls.php`
- `M231226142737CreateTableJobs.php`
- `m230513_125905_create_table_attaches.php`

### Конфигурация
- `config/params.php` — параметр `schedulesTZShift`

---

## 3. Зависимости блока

### Блок зависит от приложения:
- `ArmsModel`, `ArmsBaseController` — базовые классы
- Трейты: `LinkerBehavior`, `ManyMultimapBehavior`
- Yii2-компоненты: `user`, `db`, `formatter`
- RBAC: `spo0okie\yii2-rbac-plus`

### Приложение зависит от блока:
- `models/Services.php` — `providing_schedule_id`, `support_schedule_id`
- `models/Acls.php` — `schedules_id`
- `models/MaintenanceJobs.php` — `schedules_id`
- `models/Attaches.php` — `schedules_id`
- Search-модели: `ServicesSearch`, `AclsSearch`, `MaintenanceJobsSearch`

> Обе группы зависимостей **остаются без изменений**. Задача — не разорвать связи,
> а физически собрать файлы блока в одном месте.

---

## 4. Предлагаемая структура

```
modules/
└── schedules/
    ├── Module.php                    # Точка входа Yii2-модуля
    ├── README.md                     # Документация блока
    ├── CHANGELOG.md                  # История изменений
    ├── models/
    │   ├── Schedules.php
    │   ├── SchedulesEntries.php
    │   ├── SchedulesSearch.php
    │   ├── SchedulesEntriesSearch.php
    │   ├── SchedulesHistory.php
    │   ├── SchedulesEntriesHistory.php
    │   ├── SchedulesAclSearch.php
    │   └── traits/
    │       ├── SchedulesModelCalcFieldsTrait.php
    │       └── ScheduleEntriesModelCalcFieldsTrait.php
    ├── helpers/
    │   └── TimeIntervalsHelper.php
    ├── controllers/
    │   ├── SchedulesController.php
    │   ├── SchedulesEntriesController.php
    │   └── ScheduledAccessController.php
    ├── views/
    │   ├── schedules/
    │   └── schedules-entries/
    ├── migrations/
    │   └── (9 файлов миграций блока)
    └── tests/
        ├── phpunit.xml
        ├── unit/
        └── fixtures/
```

---

## 5. Рекомендуемый вариант

### Yii2-модуль внутри monorepo

Выделение в стандартный Yii2-модуль (`modules/schedules/`) без изменения инфраструктуры
и без разрыва зависимостей.

**Что делается:**
- Файлы блока физически перемещаются в `modules/schedules/`
- Обновляются namespace'ы: `app\models\Schedules` → `app\modules\schedules\models\Schedules`
- Модуль регистрируется в конфигурации Yii2
- В директории модуля появляются документация и тесты

**Что не делается:**
- Базовые классы (`ArmsModel`, `ArmsBaseController`) остаются на месте — выносить их не нужно
- Связи с `Services`, `Acls`, `MaintenanceJobs` не трогаются
- Никаких новых репозиториев и Composer-пакетов

**Почему именно модуль, а не просто папка:**
Yii2-модуль даёт стандартный механизм регистрации маршрутов, изоляцию namespace'ов
и понятную точку входа (`Module.php`) — без какой-либо дополнительной инфраструктуры.

---

## 6. Документация блока

### Состав

```
modules/schedules/
├── README.md       # Обязательно
├── CHANGELOG.md    # Обязательно
└── docs/
    ├── models.md   # Описание моделей и их связей
    ├── database.md # Схема таблиц
    └── testing.md  # Как запустить тесты
```

### Что должен содержать README.md

- Назначение блока (3–5 предложений)
- Список моделей с кратким описанием каждой
- Описание связей с остальным приложением (какие внешние модели используют schedules и как)
- Схема таблиц БД (кратко) или ссылка на `docs/database.md`
- Как запустить тесты блока

---

## 7. Тестирование блока

Тестов сейчас нет — пишем с нуля.

### Принцип изоляции

Тесты блока не должны требовать поднятия всего приложения. Фикстуры создают
только таблицы `schedules` и `schedules_entries`. Зависимости от внешних моделей
(`Services`, `Acls` и др.) в тестах блока не задействуются.

### Что тестируем

| Компонент | Тип | Примечание |
|---|---|---|
| `TimeIntervalsHelper` | Unit | Чистая математика, никаких зависимостей |
| `SchedulesModelCalcFieldsTrait` | Unit | Мокать не нужно |
| `ScheduleEntriesModelCalcFieldsTrait` | Unit | Мокать не нужно |
| `Schedules`, `SchedulesEntries` | Integration | Фикстуры только schedules-таблиц |
| `SchedulesController` | Integration | Тестовая БД + тестовый пользователь |
| `ScheduledAccessController` | Integration | Требует `Acls` — тестировать в контексте приложения |

### Запуск

```bash
# Только тесты блока schedules
./vendor/bin/phpunit -c modules/schedules/tests/phpunit.xml

# Все тесты приложения (включая schedules)
./vendor/bin/phpunit
```

---

## 8. План действий

### Этап 0: Подготовка

- [ ] Инвентаризация всех мест в приложении, где используются классы schedules
- [ ] Зафиксировать список импортов, которые потребуют обновления namespace
- [ ] Создать ветку `feature/schedules-module`

### Этап 1: Перенос файлов

- [ ] Создать структуру директорий `modules/schedules/`
- [ ] Создать `modules/schedules/Module.php`
- [ ] Переместить модели, трейты, helpers, контроллеры, views, миграции
- [ ] Обновить namespace'ы во всех перемещённых файлах
- [ ] Обновить импорты во всех файлах основного приложения, ссылающихся на schedules
- [ ] Зарегистрировать модуль в `config/web.php`
- [ ] Убедиться, что приложение запускается и работает корректно

### Этап 2: Документация

- [ ] Написать `modules/schedules/README.md`
- [ ] Описать связи с внешними моделями
- [ ] Добавить `docs/database.md` со схемой таблиц

### Этап 3: Тесты

- [ ] Настроить `modules/schedules/tests/phpunit.xml`
- [ ] Написать unit-тесты для `TimeIntervalsHelper`
- [ ] Написать unit-тесты для трейтов
- [ ] Написать интеграционные тесты для `Schedules`, `SchedulesEntries`
- [ ] Проверить изолированный запуск тестов блока

### Этап 4: Ревью и стабилизация

- [ ] Code review
- [ ] Проверка RBAC-прав
- [ ] Проверка миграций в тестовой среде
- [ ] Merge в основную ветку


---

## 9. Риски

| Риск | Вероятность | Влияние | Митигация |
|---|---|---|---|
| Пропущенные импорты при смене namespace | Высокая | Низкое | `grep -r "app\\models\\Schedules"` до и после переноса |
| Маршруты перестают работать после регистрации модуля | Средняя | Среднее | Проверить все роуты schedules вручную после Этапа 1 |
| Миграции конфликтуют при применении | Низкая | Среднее | Тестовая среда перед применением на продакшне |

---

*Версия: 4.0 · Обновлено: 2026-03-04*