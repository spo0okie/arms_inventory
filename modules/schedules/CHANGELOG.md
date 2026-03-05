# Changelog

Все значимые изменения модуля `modules/schedules/` фиксируются в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/).

---

## [Unreleased]

### Changed
- Блок выделен в отдельный Yii2-модуль `modules/schedules/`
- Namespace изменён: `app\models\Schedules*` → `app\modules\schedules\models\Schedules*`
- Namespace хелпера изменён: `app\helpers\TimeIntervalsHelper` → `app\modules\schedules\helpers\TimeIntervalsHelper`
- Контроллеры перенесены: `app\controllers\Schedules*` → `app\modules\schedules\controllers\Schedules*`
- Миграции перенесены в `modules/schedules/migrations/`

---

## История до выделения в модуль

### 2024-02 — M240225074103HistoryJournalsAcls
- Добавлены таблицы истории для ACL-журналов

### 2023-12 — M231226142737CreateTableJobs
- Созданы таблицы `maintenance_jobs` и `maintenance_reqs`
- Добавлена связь `maintenance_jobs.schedules_id → schedules.id`

### 2023-05 — m230513_125905
- Добавлена поддержка вложений (attaches)

### 2022-04 — m220402_185406
- Добавлены поля `start_date`, `end_date`, `override_id` в таблицу `schedules`
- Поле `schedule` в `schedules_entries` расширено с VARCHAR(64) до VARCHAR(255)
- Реализован механизм перекрытий расписаний (`override_id`)

### 2021-08 — m210825_125020
- Созданы таблицы `acls`, `aces`, `access_types` и связующие таблицы
- Добавлена связь `acls.schedules_id → schedules.id`
- Поле `schedules.name` расширено до VARCHAR(255) NOT NULL

### 2021-06 — m210614_150516
- Добавлены поля `parent_id` (INT NULL) и `history` (TEXT) в таблицу `schedules`
- Реализовано иерархическое наследование расписаний

### 2021-06 — m210614_063518
- Создана таблица `schedules_entries` (записи расписания: дни, периоды)

### 2020-03 — m200317_043845
- Добавлены поля `providing_schedule_id` и `support_schedule_id` в таблицу `services`
- Добавлены FK: `services → schedules`

### 2020-03 — m200317_040048
- Создана таблица `schedules` (id, name, description)
