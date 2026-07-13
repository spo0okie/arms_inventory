# Issue #141: Цветовые маркеры

## Задача

Уйти от ручной CSS-раскраски объектов (классы по `code`/имени + рукописные файлы
`state-colors.css`, `codes.private.css`) к справочнику «Маркеры», выбираемому в
статусах/сегментах/L2-доменах/типах оборудования и т.п. Учесть сценарии рендера:
общий фон, тёмный фон шапки, тултип, карточки.

## Текущее состояние (контекст)

- `tech_states.code` → классы `item_status state_*` (state-colors.css) + вариант
  «уголок» `span.unit-status` (диагональный градиент) в компактных карточках.
- `segments.code` — «Код CSS» → классы `segment_*` (codes.private.css,
  per-инсталляционный) с ручными оверрайдами под тёмную шапку (`.nav-header ...`)
  и карточки (`div.arms-card ...`).
- L2-домены: класс генерится из ИМЕНИ (`net-domain-<name>`) — переименование
  теряет цвет.
- `contracts_states.code` → классы `state_*` (state-colors.css, блок документов).
- `tech_types.code` — «может использоваться для генерации CSS классов».
- У `Tags` уже правильная схема: колонка `color` (HEX, ColorType) + автоконтраст
  текста (getTextColor), рендер инлайн-стилем.

## Принятое решение

Маркер = до трёх цветов, из них обязателен один:

- `color` — фон, ОБЯЗАТЕЛЬНЫЙ и универсальный канал. Любой рендерер (бейдж,
  ячейка IPAM, уголок unit-status, смешение композитных маркеров) обязан уметь
  рендерить фон; смысл маркера должен читаться по одному фону.
- `border_color` + `border_style` (solid/dashed) — необязательный уточняющий
  канал (семантика DMZ-пунктиров). Правило деградации: где рамку рендерить
  негде (уголок, полоски смешения) — она молча отбрасывается.
- `text_color` — nullable-переопределение; по умолчанию автоконтраст от фона
  (кейс guest_dmz: тёмный фон + оранжевый текст).

НЕ делаем: штриховку как свойство маркера (единственный кейс segment_mgmt —
переводится на сплошной цвет или позже на композицию), произвольный CSS-эскейп.

Рендер: инлайн CSS-переменные `--marker-bg`, `--marker-fg`, `--marker-border`
на элементе + один базовый CSS, потребляющий переменные. Так маркер задаёт и
фон, и цвет текста, и одинаково читается на белом фоне, в тёмной шапке, в
тултипе и карточках — без пер-маркерных оверрайдов.

## Итерации

### 1. Справочник «Маркеры»

- Миграция `markers`: name, color (NOT NULL), text_color (NULL), border_color
  (NULL), border_style (NULL, solid/dashed), comment, archived; ArmsMigration
  (коллация utf8mb4_unicode_ci автоматически).
- Модель `Markers` (ArmsModel): титулы, ColorType на цветах, fieldList на
  border_style, getTextColor()/getStyle() (инлайн-переменные), без истории.
- `MarkersController extends ArmsBaseController` (generic CRUD).
- Вьюхи: `views/markers/item.php` (самопоказ маркера), `columns.php`, `_form.php`
  (ColorInput на три цвета, dropdown стиля рамки), `card.php`.
- Пункт меню (блок справочников), MarkersSearch.

### 2. Рендер: ItemObjectWidget + CSS

- `Markers::getStyle()` → `--marker-bg:...;--marker-fg:...;--marker-border...`.
- `ItemObjectWidget`: параметр `marker` (или автоопределение `$model->marker`) —
  добавляет класс `marked-item` и инлайн-переменные к span.
- Базовый CSS (web/css/markers.css): `.marked-item` (фон/текст/рамка/радиус от
  переменных), `.unit-status.marked-item` (уголок от `--marker-bg`, рамка
  отброшена), совместимость с тултипом/шапкой/карточками.
- `unit-status`/`item_status` вьюхи (techs/card, techs/compact, map/arm-row,
  map/tech-row) — берут маркер статуса при наличии, легаси-класс как fallback.

### 3. marker_id в справочниках + сид

- Миграция: `marker_id` (int, NULL, индекс) в `tech_states`, `segments`,
  `net_domains`, `tech_types`, `contracts_states`.
- Модели: linksSchema/attributeData/relation getMarker; формы: select2.
- item.php вьюх справочников: маркер при наличии, легаси CSS-класс — fallback.
- Data-миграция: сеет маркеры по фактической палитре state-colors.css +
  codes.private.css и проставляет marker_id по `code` (домены — по имени).
- Смена смысла `segments.code`: hint больше не «Код CSS».

### 4. ColorType

- `renderInput()` → kartik ColorInput (перенос из views/tags/_form.php),
  формы тегов/маркеров получают пикер из типа.

### Тесты

- Юнит: контраст (getTextColor), сборка стиля (getStyle) со всеми комбинациями
  полей, fallback-логика.
- Сторожа: ModelTitlesTest (титулы Markers), CollationConsistencyTest (миграция
  через ArmsMigration), генеративные PageAccessCest подхватят контроллер сами.

## Статус

- [x] Итерация 1 — справочник (`Markers`+Search+Controller, вьюхи, admin-меню)
- [x] Итерация 2 — рендер (`ItemObjectWidget::$marker` c автоопределением,
      `web/css/markers.css`, `MarkerOwnerTrait::markerClass/markerStyle` для
      ручных span/td; попутно починен потерянный `display:none` архивных
      в ItemObjectWidget — результат appendToDelimitedString не присваивался)
- [x] Итерация 3 — marker_id×5 + сид из легаси CSS + fallback в item-вьюхах;
      сети/IP наследуют маркер сегмента (networks/item, net-ips/item)
- [x] Итерация 4 — ColorType: пикер в renderInput, контраст через ColorHelper
      (дубли из Tags/ColorType сведены в хелпер); ChoiceType для border_style

## Проверено

- Юниты: MarkersTest, ColorHelperTest + полный unit-набор (656 OK).
- Acceptance изолированно: markers (18 OK), tech-states/segments/net-domains/
  tech-types/contracts-states (88 OK, 2 штатных скипа), techs/contracts/
  networks/net-ips (102 OK).
- Браузером: список маркеров, статусы, сегменты (рамка DMZ), тёмная шапка
  карточки сети, уголок unit-status (деградация рамки), автоконтраст.
- `tests/_data/arms_demo.sql` регенерирован (CI не гоняет миграции — грузит
  дамп; попутно в дамп вошли эффекты ранее закоммиченных миграций владельца
  m260711 drop_user_groups и m260712 users_manager_id).

## Доделано вторым заходом (по замечаниям владельца)

- [x] **Производительность списков**: жадная загрузка `.marker` во всех
      местах, где строки списка красятся маркером — TechsSearch
      (`state.marker`, `netIps.network.segment.marker`), CompsSearch
      (`arm.state.marker`, `netIps.network.segment.marker`), NetworksSearch,
      NetIpsSearch (dataQuery), ContractsSearch, NetVlansSearch,
      SegmentsSearch, TechModelsSearch (`type.marker`), ServicesSearch
      (`segment/arms.state/techs.state`), PlacesController (armmap+depmap),
      NetworksController::actionIpam; generic-ветка
      `ArmsBaseController::actionIndex` (справочники без Search) делает
      `with('marker')`, если у модели есть связь.
- [x] **IPAM**: ячейки/полосы смешения красятся фоном маркера сегмента
      (`ipamCellPaint()`: класс `marked` + `--marker-bg`, легаси-код —
      fallback, `nocode` — белый); гайд guides/ipam.md и models/networks.md
      обновлены.
- [x] **Перцептивный порог контраста 0.6** (ColorHelper::contrastColor):
      на средних тонах (серый #808080, зелёный #5CB85C, синий #1DA7EE)
      белый текст читается лучше, хотя формальный контраст у чёрного чуть
      выше (эффект полярности); светлые фоны — по-прежнему чёрный.
      Порог 0.5 был «как в формуле», не тюнингом.
- [x] **Симметрия плашки**: фон inline-спана включает зону выносных
      элементов снизу («д», «у»), а сверху заглавные впритык — плашка
      выглядела смещённой вниз; `span.marked-item {padding-top:.12em}`
      расширяет фон вверх (на высоту строки не влияет).
- [x] **Сид сеет ВСЮ палитру** (32 маркера), а не только используемые коды:
      справочник — полная палитра для выбора; привязка по кодам — по-прежнему
      только к существующим записям. Маппинг покрывает весь легаси CSS:
      19 кодов сегментов (оба шлюза gw_*, все DMZ), 9 статусов техники,
      8 статусов документов, 6 доменов.
- [x] **Select2 маркера в целевой раскраске**: специализация в
      `ActiveField::select2()` — если атрибут ссылается на Markers, данные
      берутся из `Markers::fetchSelectData()` (option'ы получают
      data-marker-style), а templateResult/templateSelection —
      `formatSelect2MarkerItem` (select2hints.js): плашка marked-item +
      обычный ttip-хинт. Работает во всех формах автоматически (везде
      обычный `->select2()`), формы не менялись.
- [x] **Превью на странице просмотра маркера** (views/markers/card.php,
      только при !static_view — в тултип не попадает): образец текста на
      типовых фонах — общий фон, тёмная шапка, тултип, тёмная карточка,
      компактный уголок. Цвета подложек НЕ хардкодятся — превью-блоки несут
      классы самих контекстов (nav-header, tooltipster-shadow-yellow,
      users-view/arms-card), будущие темы оформления перекрасят и превью;
      обёртки — div (контексты в CSS объявлены элементно: div.nav-header).
- [x] **IPAM: контраст подписей** — цвет подписи ячейки подбирается к фону:
      маркер → его textColor, смесь → контраст к средневзвешенному цвету
      известных долей (`ipamMixTextColor`, легаси-доли в среднее не входят),
      тень-ореол противоположна тексту (`--ipam-fg`/`--ipam-shadow`);
      дефолт (серые occupied/empty, легаси) — прежний белый с тёмной тенью.
- [x] **VLAN** наследует маркер своего L2-домена (net-vlans/item.php,
      fallback `domainCode`).
- [x] Миграция M260702104735NormalizeCollation переведена на
      `dropIndexIfExists()` (`DROP INDEX IF EXISTS` — MariaDB-синтаксис,
      MySQL 9 падал).
- [x] `tests/bin/yii` починен (использует `config/test-console.php` вместо
      несуществующего `config/test_db.php`); `tests/_data/readme.md`
      актуализирован (arms_test, команда дампа, CRLF→LF, «CI не гоняет
      миграции»), ссылки на него — в tests/readme.md §6 и tests/database.md.

## Не в этом заходе

- Выпиливание легаси CSS (state-colors.css, codes.private.css) — после
  обкатки маркеров на живых инсталляциях.
- Композитные теги (смешение маркеров полосками) — фундамент готов
  (композиция определяется только над фонами; IPAM-полосы смешения уже
  работают по этому принципу).
