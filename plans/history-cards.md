# Issue #194: Механизм рендера изменений (карточки изменений в журнале истории)

## Задача

Табличный режим журнала истории (`/history/journal`) сырой: широченный GridView со
снапшотами всех полей. Нужен механизм, который на каждую запись истории отображает
«карточку изменений»:

- дата/время;
- автор;
- список изменений:
  - для полей со значениями: старое → новое;
  - для полей с множественными значениями (множественные ссылки, JSON):
    выбывшие значения, добавленные значения.

## Как устроено хранение (контекст)

- Каждая запись `*_history` — полный снапшот строки мастера (HistoryModel::fillRecord).
- Множественные ссылки (`*_ids`) хранятся CSV-строкой ID (simplifyField).
- `changed_attributes` — CSV имён изменившихся атрибутов (сравнение с предыдущей
  записью уже сделано при журналировании); `object_deleted` — запись об удалении.
- Историческое разрешение ссылок: `fetchJournalRecord($id, $updated_at)`.

## Решение

### 1. Diff-API в HistoryModel (тестируемое ядро)

- `getPreviousRecord()` / `setPreviousRecord()` — предыдущая по id запись журнала
  этого master_id (лениво; в списке карточек соседняя запись подсовывается без
  запроса). Не пересекается с `$previous` (тот живёт в механике записи журнала).
- `isDeletionRecord()` — `changed_attributes === DELETED_FLAG`.
- `changedAttributesList()` — атрибуты для карточки: из `changed_attributes`,
  только существующие и журналируемые (защита от устаревших имён в старых записях).
- `attributeIsMultiValue($attr)` — множественные ссылки `_ids`, JsonType,
  StringArrayType: diff отображается как выбывшие/добавленные.
- `attributeValueSet($attr)` — значение как множество: ссылки → ID,
  JSON → элементы «ключ: значение» (не распарсился — одно значение целиком),
  списки строк → explode(',').
- `attributeSetDiff($attr)` — `['added'=>[], 'removed'=>[]]` против предыдущей записи.
- `fetchLinkOnRecordDate($attr,$id)` — объект-ссылка в состоянии на дату записи.

### 2. Вьюхи

- `views/history/card.php` — карточка одной записи: шапка (время, автор через
  ModelWidget, бейджи «Объект удалён»/«Первая запись журнала»), пояснение
  (updated_comment), список изменений list-group:
  - скаляры: старое (text-muted) → новое, рендер значений через
    `ModelFieldWidget::renderFieldValue` на history-записи (типы и историческое
    разрешение ссылок работают штатно);
  - множественные: `+ добавленные` (text-success), `− выбывшие` (text-danger);
    ссылки рендерятся объектами (ListObjectsWidget) на дату соответствующей записи,
    прочее — текстом;
  - подписи атрибутов — `ModelFieldWidget::renderFieldTitle` от мастер-инстанса
    (тултипы-«?» и help-mode как на карточке объекта).
- `views/history/journal.php` — переключатель `viewMode`: `cards` (по умолчанию) |
  `table` (прежний DynaGrid со всеми columnsMode). В списке карточек предыдущая
  запись сеется из соседней в выборке, для последней на странице — ленивый запрос.
  Пагинация — bootstrap5 LinkPager.

### 3. Тесты

- `tests/unit/models/HistoryCardDiffTest.php`:
  - set-diff множественных ссылок (CompsHistory.services_ids);
  - set-diff JSON (TechModelsHistory.front_rack_layout), fallback кривого JSON;
  - первая запись (нет предыдущей) — всё в added;
  - классификация attributeIsMultiValue (скаляр/одиночная ссылка — нет);
  - changedAttributesList: фильтрация несуществующих/служебных;
  - isDeletionRecord;
  - цепочка getPreviousRecord на реальных записях (Sandboxes, транзакция+rollback).
- Приёмочные: HistoryPagesCest уже ходит на `/history/journal` без параметров —
  теперь это режим карточек.

## Статус

- [x] Diff-API в HistoryModel
- [x] card.php + journal.php (viewMode)
- [x] Unit-тесты (HistoryCardDiffTest, 8 тестов)
- [x] Прогон тестов: unit-сьюта целиком зелёная (634 теста), страницы проверены
      вживую (CompsHistory#4 с пагинацией, TechsHistory#15/#20, табличный режим)

## Follow-up: подсветка изменённых ячеек в табличном режиме

В табличном режиме скалярные колонки не подсвечивали изменённые ячейки
(table-warning): `DefaultColumn::renderDataCell` отфильтровывал contentOptions
по свойствам ModelFieldWidget ДО того, как вклеить `class` в cardClass ячейки,
и класс подсветки терялся. Ссылочные колонки не страдали - у них в columns.php
задан свой `value`, они остаются штатной kartik-колонкой; `name` рендерится
ItemColumn, который contentOptions доносит корректно.

Фикс: класс ячейки забирается из contentOptions до фильтрации
(components/gridColumns/DefaultColumn.php). Регрессионный тест -
tests/unit/components/DefaultColumnContentOptionsTest.php. Побочный эффект
(намеренный): ячейки DefaultColumn снова получают дефолтный класс `<attr>_col`
(на него рассчитан CSS вроде `td.usage_col > div.progress`).
