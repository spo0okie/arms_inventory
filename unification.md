# Унификационные механизмы проекта ARMS

Ниже список механизмов унификации, найденных в коде и документации проекта. Список предназначен для последующей оценки степени фактического применения каждого механизма.

## Модели и метаданные

- **Базовая модель `ArmsModel`** — единая точка для правил, метаданных, истории, синхронизации и общих соглашений модели. Файл: `models/ArmsModel.php`.
- **Система метаданных атрибутов через `attributeData()`** — единый формат описания полей (label/hint/view/index/api, type, placeholder, наследование, join/filter и т.д.). Файл: `models/traits/AttributeDataModelTrait.php`.
- **Единая схема связей `linksSchema` + LinkerBehavior** — декларативное описание связей, авто-подключение many-to-many поведения и загрузчиков. Файл: `models/traits/AttributeLinksModelTrait.php`.
- **Генерация OpenAPI-аннотаций на основе метаданных** — единый способ описания схем API. Файл: `models/traits/AttributeAnnotationModelTrait.php`.
- **Единый механизм внешних ссылок/JSON-связей** — работа с `external_links` и связями во внешних ИС. Файл: `models/traits/ExternalDataModelTrait.php`.
- **История изменений через `HistoryModel` и `{Model}History`** — единый базовый класс журнала и стандартный набор history-моделей. Файлы: `models/HistoryModel.php`, `models/*History.php`.
- **Search-модели по шаблону `{Model}Search`** — унифицированный слой поиска/фильтрации. Файлы: `models/*Search.php`.
- **Набор трейтов вычисляемых полей (`*CalcFieldsTrait`)** — унификация расчётных полей по доменам. Файлы: `models/traits/*CalcFieldsTrait.php`.
- **Теги через `TaggableTrait` и `TagsLinks`** — единый механизм тегирования и связей. Файлы: `models/traits/TaggableTrait.php`, `models/links/TagsLinks.php`.
- **Модели связующих таблиц (many-to-many)** — типовые AR-модели для таблиц связей. Файлы: `models/links/*.php`.
- **UI-настройки таблиц (колонки/ширины)** — единое хранение пользовательских настроек таблиц. Файл: `models/ui/UiTablesCols.php`.

## Контроллеры и доступ

- **Базовый контроллер `ArmsBaseController`** — универсальный CRUD, доступы, рендер (обычный/AJAX), поиск, архивирование, fallback layout/view. Файл: `controllers/ArmsBaseController.php`.
- **Обработка ошибок через `ArmsErrorAction`** — единый механизм маппинга error-страниц по коду. Файл: `components/actions/ArmsErrorAction.php`.
- **REST API через `BaseRestController` + автосоздание контроллеров** — унифицированные CRUD/поиск/фильтры/доступы для API. Файлы: `modules/api/controllers/BaseRestController.php`, `modules/api/Rest.php`.

## Формы и UI

- **Базовая форма `ArmsForm`** — единый режим AJAX-валидации, динамические плейсхолдеры, автогенерация validationUrl. Файл: `components/Forms/ArmsForm.php`.  
  Оценка применения: ~87% (40 из 46 файлов `*_form.php` используют `ArmsForm::begin`).  
  Пропуски: `views/arms/_form.php`, `views/attaches/_inline_form.php`, `views/places/map/_form.php`, `views/prov-tel/_form.php`, `views/scans/_form.php`, `views/user-groups/_form.php`.  
  `ASSUMPTION`: все `*_form.php` должны использовать `ArmsForm`, кроме спец-форм/legacy.
- **Кастомный `ActiveField`** — единые label/hint/tooltip правила и стандартные методы полей (`select2`, `textAutoresize`, `classicHint`). Файл: `components/Forms/ActiveField.php`.  
  Оценка применения: ~87% (используется через `ArmsForm::$fieldClass` во всех формах на `ArmsForm`).  
  Явное использование: `views/aces/_form_layout.php`.
- **Select2 как стандарт для ссылочных/справочных полей** — через `ActiveField::select2()`, `FieldsHelper::Select2Field()`, либо `Select2::widget()`.  
  Оценка применения: ~52% форм (24 из 46 `*_form.php` содержат `select2`/`Select2`).  
  `ASSUMPTION`: Select2 нужен только там, где поля действительно ссылочные/списочные.
- **Авто‑resize текстовых полей** — `ActiveField::textAutoresize()` + `TextAutoResizeWidget`. Файл: `components/formInputs/TextAutoResizeWidget.php`.  
  Оценка применения: ~30% форм (14 из 46 `*_form.php`).  
  `ASSUMPTION`: применимо только к длинным/многострочным полям.
- **Подсказки в label** — `ActiveField::classicHint()` и `hintTipOptions()` для tooltip.  
  Оценка применения: ~17% форм (8 из 46 `*_form.php` используют `classicHint`).  
  `ASSUMPTION`: остальное подтягивается из метаданных `attributeData()` и поэтому не обязательно явно в формах.
- **Rich‑text/Wiki поля** — `DokuWikiEditor`, `MarkdownEditor`. Файлы: `components/formInputs/DokuWikiEditor.php`, зависимости Kartik Markdown.  
  Оценка применения: точечно (2–3 файла в `views/*.php`, <1% всех view-файлов).  
  `ASSUMPTION`: количество таких полей невелико, поэтому низкий процент допустим.
- **Набор UI‑виджетов** — унифицированные представления и элементы интерфейса (см. `components/*Widget.php`).  
  Оценка применения: ~30% view‑файлов используют `*Widget` (146 из 494 файлов в `views/**`).  
  `ASSUMPTION`: не все views обязаны использовать виджеты, часть — простая разметка.

  **Список UI‑виджетов и назначение (где применять):**
  - `Alert` — вывод единых alert‑сообщений. Использовать для уведомлений/предупреждений вместо ручной разметки.
  - `StripedAlertWidget` — вариант alert с полосой/акцентом. Использовать для статусных сообщений.
  - `StripedRowWidget` — стилизованные строки (например, в списках/карточках). Использовать для табличных/списочных элементов.
  - `CollapsableCardWidget` — сворачиваемые карточки. Использовать для блоков с большим количеством опций/деталей.
  - `ExpandableCardWidget` — разворачиваемые карточки. Использовать для “подробностей по требованию”.
  - `TabsWidget` — унифицированные вкладки. Использовать для view‑страниц с несколькими логическими блоками.
  - `RackWidget` — визуализация стойки. Использовать на страницах стоек/оборудования.
  - `RackConstructorWidget` — конструктор/редактор стойки. Использовать в формах/настройках стойки.
  - `DynaGridWidget` — унифицированные таблицы с настройкой колонок. Использовать в `index.php` и табличных блоках view.
  - `HistoryWidget` — показ истории изменений. Использовать в view‑страницах моделей с журналированием.
  - `IsHistoryObjectWidget` — индикатор/метка “есть история”. Использовать рядом с объектами, имеющими журнал.
  - `IsArchivedObjectWidget` — индикатор статуса архива. Использовать в view/item/ttip где нужен статус.
  - `ShowArchivedWidget` — переключатель показа архивных. Использовать на индекс‑страницах.
  - `ItemObjectWidget` — стандартный рендер имени объекта. Использовать в списках/вставках.
  - `LinkObjectWidget` — стандартная ссылка на объект (с корректным названием/tooltip). Использовать в связях.
  - `ListObjectsWidget` — список связанных объектов. Использовать для M2M/1‑N в view.
  - `DeleteObjectWidget` — стандартная кнопка удаления. Использовать вместо кастомных delete‑кнопок.
  - `UpdateObjectWidget` — стандартная кнопка редактирования. Использовать вместо кастомных edit‑кнопок.
  - `TagsWidget` — отображение/редактирование тегов. Использовать для моделей с `TaggableTrait`.
  - `ModelFieldWidget` — рендер одного атрибута по `attributeData(type)`. Использовать в view/item/ttip как базовый способ вывода.
  - `AttributeHintWidget` — отображение подсказок атрибутов. Использовать рядом с полями где нужны подсказки.
  - `SearchFieldWidget` — единый поиск/фильтр для таблиц/списков. Использовать на index/поисковых формах.
  - `TableTreePrefixWidget` — визуальные префиксы для дерева в таблицах. Использовать в иерархических списках.
  - `TextFieldWidget` — унифицированный рендер текстовых полей. Использовать в view/item/ttip при ручном выводе текста.
  - `UrlListWidget` — рендер списка URL/ссылок. Использовать для атрибута `links`.
  - `UrlParamSwitcherWidget` — переключатель URL‑параметров. Использовать на страницах, где нужно быстро менять режимы/фильтры.
  - `UrlListWidget` — рендер списка URL/ссылок. Использовать для атрибута `links`.
  - `WikiPageWidget` — вывод Wiki‑страницы. Использовать для вики‑контента.
  - `WikiTextWidget` — рендер Wiki‑разметки. Использовать для текстов с wiki‑синтаксисом.
  - `LinkObjectWidget`/`ListObjectsWidget`/`ItemObjectWidget` — базовый набор для связей и карточек, использовать везде где выводятся объекты‑ссылки.
- **DynaGrid + кастомные колонки** — стандарт табличных списков. Файлы: `components/DynaGridWidget.php`, `components/gridColumns/*.php`.  
  Оценка применения: ~64% (23 из 36 `views/**/index.php` содержат `DynaGridWidget`).  
  `ASSUMPTION`: часть `index.php` (например `views/site/index.php` или узкоспециализированные) может быть исключением.
- **Общие view‑шаблоны и layout‑фоллбеки** — единая структура views и базовые layouts для `index/view/create/update/ttip/item`. Файлы: `views/layouts/*.php`.  
  Оценка применения: `ASSUMPTION` ≈100% для контроллеров, наследующих `ArmsBaseController`, т.к. fallback встроен в базовый рендер.
- **View‑партиалы для компонентов** — стандартизированные шаблоны рендера UI‑компонентов. Файлы: `components/views/**`.  
  Оценка применения: `ASSUMPTION` — используется по мере подключения соответствующих виджетов.

## Миграции и БД

- **Базовая миграция `ArmsMigration`** — единый набор безопасных helper-методов и создание many-to-many таблиц. Файл: `migrations/arms/ArmsMigration.php`.

## Синхронизация и интеграции

- **Единый механизм синхронизации** — общий контроллер синхронизации и унифицированные поля/правила sync в `ArmsModel`. Файлы: `console/commands/SyncController.php`, `models/ArmsModel.php`.

## Хелперы

- **Набор helper-классов** — унифицированные утилиты для строк, массивов, SQL/REST, дат, wiki и т.д. Файлы: `helpers/*.php`.

## Документация стандартов

- **Свод правил и соглашений** — описывает обязательные базовые классы, структуру views и др. Файлы: `standards.md`, `structure.md`.

## План перехода к “attributeData = SSOT” для вывода и ввода

Цель: **везде** использовать `ModelFieldWidget` для вывода атрибутов и `ArmsForm`/`ActiveField` для ввода, опираясь на `attributeData` (особенно `type`).

### Ключевая идея

Все атрибуты, которые участвуют в UI (view/item/ttip/form), должны иметь корректный `type` в `attributeData()`.  
Отсутствие `type` должно выявляться автоматически при прогоне стандартных страниц.

### Пошаговый план

1. **Ввести режим строгости**  
   - виджеты бросают exception при отсутствии `type`.
2. **Централизовать ввод**  
   - Добавить/использовать универсальный метод рендера поля (например `ActiveField::autoField()` или `FieldsHelper::AutoField()`), который выбирает виджет по `type`.  
3. **Централизовать вывод**  
   - Перевести `view.php`, `item.php`, `ttip.php` на `ModelFieldWidget` для стандартных атрибутов.  
   - Исключения оставить только для специфичного UI.
4. **Автотесты стандартных страниц**  
   - Включить `attrDataStrict = true` на тестах.  
   - Прогонять `index/view/create/update/ttip/item` по ключевым моделям.  
   - Тесты дадут точный список атрибутов без `type` и мест, где вывод/ввод идёт не через виджеты.
5. **Метрики прогресса**  
   - Процент моделей с полным `attributeData(type)` для UI‑атрибутов.  
   - Количество исключений/логов “missing type” в мягком режиме.

### Риски и минимизация

- Резкое включение strict‑режима может “сломать” старые страницы.  
  Решение: сначала мягкий режим + отчет, затем постепенное включение.
- Не все атрибуты должны иметь `type` (служебные/виртуальные).  
  Решение: whitelist/blacklist на уровне модели или явная пометка в `attributeData` (например `ui=false`).

### Рекомендованные первые шаги

1. Ввести `attrDataStrict` и логирование.  
2. Собрать отчет по “missing type”.  
3. Начать миграцию view/item/ttip на `ModelFieldWidget` для самых активных моделей.
