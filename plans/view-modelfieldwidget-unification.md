# Унификация вывода атрибутов через ModelFieldWidget (view/card/header)

Статус: черновик от 2026-07-09. Продолжение «этапа 4в» правила
«атрибут — всегда ModelFieldWidget» (unification.md, ui-sources.md §0.1).

## Цель

Все атрибуты моделей в карточках просмотра (`view.php` / `card.php` /
`header.php` и рендерящиеся из них партиалы) выводятся ТОЛЬКО через
`ModelFieldWidget` (`::widget` / `::renderFieldValue` / `::renderFieldTitle` /
`::renderFieldRow` / `::detailAttribute`). Это даёт единый заголовок с иконкой
«?» (тултип-подсказка + источник значения + переходы в документацию) и единую
точку подключения `renderOutput()` типов.

## Как проведён аудит

Просмотрены `view.php`/`card.php`/`header.php` и их атрибутные партиалы во всех
~45 модельных вьюхах (5 параллельных проходов). Исключены по правилу: `item.php`,
`ttip.php`, `*_form.php`, `index.php`, `_search.php`, grid-колонки.

## Принцип классификации

`getAttributeTypeClass()` (`AttributeDataModelTrait`) резолвит тип так:
1. явный `attributeData['typeClass']`;
2. связь/загрузчик → `LinkType`;
3. конвенции по имени: `is_*`→Boolean; `id`/`count`→Integer; `ip(s)`→Ips;
   `mac(s)`→Macs; `links`/`url`/`urls`→**Urls**; `name`/`comment`/`fqdn`/…→String;
   `created_at`/`updated_at`→Datetime; `date`→Date;
4. по правилу валидации (`string`/`integer`/`number`/`boolean`);
5. хвостовые конвенции для calc-полей: `*Count`→Integer, `*Name`/`*Names`→String;
6. иначе — **бросает исключение** «Не удалось определить тип атрибута».

Отсюда:
- **Тривиально** = атрибут уже резолвится (явный тип ИЛИ конвенция) И текущий
  кастомный рендер сводится к 1:1-замене без изменения раскладки. Делается сразу.
- **Нетривиально** = нужен новый тип, новые метаданные/alias для computed-поля,
  композиция связанных объектов или значение вплетено в человекочитаемую фразу.
  Такие сгруппированы ниже по общему решению.

---

## Тривиальный батч (выполняется в этой итерации)

Замены 1:1, тип уже резолвится, раскладка сохраняется; заодно ручной `<h4>` над
значением заменяется на `renderFieldTitle` — появляется иконка «?».

| Файл | Что | Замена | Статус |
|------|-----|--------|--------|
| `views/tech-models/view.php` | `<h4>Ссылки:</h4>` + `UrlListWidget(list=$model->links)` (UrlsType явный) | `renderFieldTitle('links',label='Ссылки:')` + `renderFieldValue('links')` | ✅ сделано |
| `views/lic-types/card.php` | то же для `links` (UrlsType по конвенции имени) | то же | ✅ сделано |
| `views/soft/card.php` | то же для `links` (UrlsType по конвенции) | то же | ✅ сделано |
| `views/manufacturers/card.php` | `Html::encode($model->full_name)`, `Html::encode($model->comment)` | `renderFieldValue('full_name'/'comment')` | ✅ сделано |
| `views/access-types/card.php` | `Html::encode($model->comment)` | `renderFieldValue('comment')` | ✅ сделано |
| `views/services/view.php` | `<h4>Записная книжка:</h4>` над `renderFieldValue('notebook')` | `renderFieldTitle('notebook',label='Записная книжка:')` | ✅ сделано |
| `views/user-groups/card.php` | `<h4>Синхронизируется с группой AD: …renderFieldValue('ad_group')</h4>` | инлайн-подпись в h4 со скобкой «(последняя синхронизация…)» — фиддлово, риск раскладки | ⏳ отложено (см. ниже) |

Проверено: `php -l` на всех изменённых файлах — чисто. Осталось: прогон
`PageAccessCest` (908) в тихом окне общей `arms_test` (memory test-db-shared-collisions)
как страховка на резолв типов; визуальная сверка не делалась (memory: launch.json
docroot-ловушка).

Мелочь-хвост: в `tech-models/view.php`, `lic-types/card.php`, `soft/card.php` остался
неиспользуемый `use …UrlListWidget` — безвреден (PHP не ругается), можно вычистить.

`user-groups/card.php` отложен: значение уже идёт через `renderFieldValue('ad_group')`,
но подпись — инлайн внутри `<h4>` с парентетической строкой синхронизации; чистая
замена на `renderFieldTitle` требует аккуратной перевёрстки (риск визуала) — в Группу 2/5.

---

## Нетривиальные проблемы, сгруппированные по решению

### Группа 1. Ввести `MoneyType` (сумма + валюта + опц. НДС) — ✅ СДЕЛАНО

**Реализовано (2026-07-09):**
- `types/MoneyType.php` — extends `FloatType` (наследует ввод/валидацию/генерацию),
  переопределяет `renderOutput`: `number_format(value, decimals, '.', ' ')` + символ валюты.
  Параметры attributeData: `decimals` (по умолч. 2), `currencyPath` (объект валюты с
  `->symbol`, по умолч. `currency`; для org-inet/phones — `service.currency`).
  Нулевая/пустая сумма → '' (совпадает с обёртками `if($model->cost)` в карточках).
- Модели (cost/charge/total → typeClass MoneyType + decimals + currencyPath):
  Services (cost/charge + новые `sumTotals`/`sumCharge` calc, decimals 0),
  Contracts (total/charge, 2), Materials (cost/charge, 2),
  OrgPhones (cost/charge, 0, currencyPath `service.currency`),
  OrgInet (cost/charge, 0, `service.currency`).
- Карточки: services/contracts/materials/org-phones/org-inet — `number_format(...).symbol`
  заменены на `renderFieldValue`; guard'ы `if(...)` и обёртки (badge, «(в т.ч. НДС: …)»,
  «/мес») сохранены. Мелкая унификация вида: symbol теперь с пробелом и форматированием
  (materials раньше был без number_format).
- Проверка: php -l чисто; unit 618 (та же 1 pre-existing HelpLinksTest) — MoneyType прошёл
  контракты типов; **PageAccessCest 908/0fail/7skip ЗЕЛЁНЫЙ** (рендер всех денежных карточек
  подтверждён).

Исходная формулировка проблемы:

**Проблема.** Дословно повторяющийся кастом:
`number_format(value,…).$currency->symbol` + условная приписка «(в т.ч. НДС: …)».

**Где:**
- `views/services/card.php` — Стоимость (`sumTotals`/`sumCharge`/`currency`), Долг
  (`totalUnpaid`/`firstUnpaid` — мультивалютный агрегат).
- `views/materials/card.php:68-70` — `cost`/`charge`/`currency`.
- `views/org-inet/card.php:47-49` — `cost`/`charge`.
- `views/org-phones/card.php:52-54` — `cost`/`charge`.
- `views/contracts/card.php:42-44` — `total`/`charge`/`currency`.

**Решение.** Ввести `types/MoneyType.php` (нет ни MoneyType, ни CurrencyType).
`renderOutput` форматирует значение и подтягивает символ валюты (валютное поле —
параметром в attributeData). Объявить `cost`/`charge`/`total` с `MoneyType`.
Тогда `ModelFieldWidget::renderFieldValue`/`renderFieldRow`. Мультивалютный «Долг»
(`totalUnpaid`) — отдельный под-случай: либо агрегирующий рендер, либо оставить
композицией (решение владельца).

### Группа 2. Объявить `attributeData(type)` для computed-полей вне конвенций

Часть уже резолвится (см. принцип п.3/5) и лишь ждёт вывода виджетом; произвольные
computed — бросают исключение и требуют явного `typeClass`/alias.

**2a. Производные `*Recursive` (services). ✅ ЧАСТИЧНО СДЕЛАНО.**
Механизм наследования метаданных УЖЕ есть: `getAttributeData` при суффиксе `Recursive`
отдаёт данные базового атрибута. Но резолв типа работает, только если у базового
атрибута есть `typeClass` ИЛИ `ref` (link/loader резолвятся по ТОЧНОМУ имени, поэтому
recursive-вариант link-атрибута без метаданных — бросает исключение).
- `linksRecursive` (card.php:153) → `renderFieldValue` — links имеет typeClass UrlsType,
  вывод 1:1 (UrlsType.renderOutput = тот же UrlListWidget). **Сделано.**
- `responsibleRecursive`/`supportRecursive`/`infrastructureResponsibleRecursive`/
  `infrastructureSupportRecursive` (card-support.php) → переведены на
  `renderFieldTitle`(span, labelOverride) + `renderFieldValue` (списки — glue=', ',
  lineBr=false), ушли от ModelWidget+foreach+implode. Потребовало добавить базовым
  атрибутам `responsible`/`support`/`infrastructureResponsible`/`infrastructureSupport`
  записи `attributeData` с `ref`=>Users (категория C, как `dependants`/`acls`), иначе
  recursive-вариант бросал бы исключение. **Сделано** (под прогон тестов — правки метаданных
  Services могут иметь побочки в API/grid, хотя следуют устоявшемуся ref-паттерну).
*Отложено (Группа 5, композиция с действиями):* `providingScheduleRecursive`
(card.php: предупреждение про общее расписание + ссылки «создать расписание»),
`supportScheduleRecursive` (`<strong>` + renderItem), `segmentRecursive` (inline в h4).

**2b. Числовые агрегаты-счётчики (уже резолвятся `*Count`→Integer). ✅ СДЕЛАНО (lic-groups).**
`views/lic-groups/usage.php` — 5 счётчиков переведены на `renderFieldRow`.
- LicGroups.attributeData: добавлены `totalCount`/`activeCount`/`directUsedCount`/
  `itemsUsedCount`(новый геттер `= usedCount - directUsedCount`)/`usedCount`/`freeCount`
  (label+hint, `readOnly`; тип — IntegerType по конвенции `*Count`).
- **Правка ядра ModelFieldWidget:** `typeRenderedValue` глушил значение через `empty()`
  ДО типа, из-за чего счётчик `0` рендерился пусто. Теперь для Integer/Float пустотой
  считается только `null`/`''` (числовой ноль выводится). Blast-radius мал (в карточках
  через виджет integer-атрибуты почти не выводятся), но это изменение общего поведения —
  под прогон тестов.
- `renderFieldRow` получил 5-й параметр `$labelOverride` (для нестандартных подписей).
*Осталось (отложено):* `views/lic-items/stat.php` — `status`/`datePart` тривиальны, но
`count($arms_ids)`/`count($comps_ids)`/… — это счётчики СВЯЗЕЙ (не атрибуты); нужен либо
calc-поле `*Count`, либо оставить композицией. Ближе к Группе 5.

**2c. Строковые computed без конвенции.**
- readable-* (networks) — **✅ СДЕЛАНО.** Оказалось, все `readable*` уже имеют
  attributeData (явный StringType или alias на `mask`/`text_router`/`dhcp`), т.е.
  правок модели не потребовалось. `networks/calc.php` (таблица значений) и
  `networks/card.php` (Шлюз) переведены на `renderFieldValue`. Все типы наследуют
  `BaseType.renderOutput` (plain encode) — вывод 1:1.
  *Отложено:* `networks/card.php` DHCP (`text_dhcp`, `asNtext`) — потенциально
  многострочный список, plain-encode потеряет переносы; оставлен как есть. Ячейка-подпись
  в `calc.php` осталась на `AttributeHintWidget` (grid-режим) — это ок.
- **chain — ОТДЕЛЬНОЕ РЕШЕНИЕ (не тип, а виджет), см. ниже «Группа 2c-chain».**
  Согласовано с владельцем: `ChainType` отвергнут — визуальная цепочка в карточке
  пользователя (`Организация / подразделение / дочернее / должность`) склеена из ТРЁХ
  разных атрибутов (`org` / `orgStruct`-chain / `Doljnost`), тип на один атрибут её
  собрать не может.

### Группа 2c-chain. Единый `ChainWidget` + режим ModelFieldWidget «значение + скрытая ?» — ✅ СДЕЛАНО

**Проблема.** Цепочка положения в дереве собирается вручную и по-разному:
`users/card.php:40-44` (`org` / `orgStruct` chain=true / `Doljnost`, склейка ` / `),
`org-struct/card.php:17-22` (partner + `$model->chain` через `$item->name` — плоский
текст БЕЗ ссылок/тултипов, склейка ` → `). Канонический разворот предков уже есть в
`org-struct/item.php` (chain-ветка).

**Решение (согласовано):**
1. **ModelFieldWidget — режим значения со скрытой подсказкой** (`value` + `AttributeTooltip::icon`
   с классом-модификатором `.attr-hint-icon--onlyhelp`): иконка «?» скрыта, проступает
   только в `body.help-mode`. CSS: `.attr-hint-icon--onlyhelp{display:none} body.help-mode
   .attr-hint-icon--onlyhelp{display:inline}`. Примитив переиспользуемый (Группа 5 —
   голые значения в вёрстке).
2. **`ChainWidget`** — упорядоченный список сегментов `{model, field, chain?:bool, label?}`;
   каждый сегмент рендерится режимом из п.1; `chain=>true` разворачивает предков узла
   (через `node->chain`/обход `parent`), тем же разделителем; одна «?» на сегмент-атрибут;
   пустые сегменты отбрасываются; склейка — `glue` (дефолт ` / `, кастомизируемый; по сути
   через `ListObjectsWidget`). В help-mode: `Организация(?) / подразделение / дочернее(?)
   / должность(?)`.
3. Применить в `users/card.php` (org / orgStruct-chain / Doljnost) и `org-struct/card.php`
   (partner / self-chain). Переиспользуемо для прочих древовидных моделей.
*Замечание:* внутренний разделитель разворота и разделитель между сегментами — один
глиф, иначе не читается единой цепочкой (сейчас ` → ` vs ` / ` рассинхронены).

**Реализовано (2026-07-09):**
- `components/ChainWidget.php` — сегменты `{model,field}` / `{model,field,chain:true}` /
  `{node,chain:true}` / `{object}` / `{text}`; пустые отбрасываются; `glue` дефолт ` / `,
  кастомизируемый. Разворот предков — `node->chain`(getChain) либо обход `parent`; узлы
  через `renderItem` (ссылки/тултипы/samePage — в отличие от прежнего плоского `->name`).
- `ModelFieldWidget::renderFieldValueHinted()` — значение + `AttributeTooltip::icon($t,true)`.
- `AttributeTooltip::icon($tooltip, $onlyHelp=true)` — добавляет класс `attr-hint-icon--onlyhelp`.
- `web/css/qtip.css` — `.attr-hint-icon--onlyhelp{display:none}` +
  `body.help-mode .attr-hint-icon--onlyhelp{display:inline}`.
- `Users.attributeData` — `org` (новый, ref→Partners, label+hint) и `orgStruct`
  (обогащён label 'Подразделение'+hint, ref сохранён) → у сегментов появились «?».
- Применено: `views/users/card.php` (org / orgStruct-chain / Doljnost),
  `views/org-struct/card.php` (partner / self-chain, glue по умолчанию ` / `).
- Прочих `'chain'=>true` в вьюхах не осталось; ручной `->chain` только в
  `org-struct/breadcrumbs.php` (трейл крошек — другая подсистема, вне скоупа).
- php -l чисто; визуальная проверка (в т.ч. help-mode) НЕ проводилась (по просьбе — без
  тестов; плюс launch.json docroot-ловушка).

### Группа 3. Композиция связанных объектов — ✅ СДЕЛАНО (заголовки документированы)

Реализовано (2026-07-09, PageAccessCest 908/0/7): заголовки блоков связей переведены на
`renderFieldTitle` (одиночная связь) / `renderCompositeTitle` (мультисвязь), значения-foreach
оставлены (унификация значения для карточек-объектов не требуется — рендер идёт через
renderItem/ModelWidget). Сделано: net-ips («Привязан к» comps/techs/users), networks
(orgInets), org-inet (networks/place/account), lic-types (licGroups), materials
(contracts_ids/place/itStaff/parent/children/usages), comps/lics_list и techs/attached/lics
(мультисвязь licGroups/licItems/licKeys → composite-title).
Осознанные исключения (объектная композиция / вложенные пути / коллекции — не вывод
одного атрибута): `aces/list.php` (коллекция Aces, единого $model нет), `lic-keys/card.php`
и `lic-items/card.php` (вложенные пути licItem.licGroup, объектные заголовки),
`maintenance-reqs/view.php` (флаги is_backup/spread_* — иконки в `<h1>` с собственными
`qtip_ttip`, уже самодокументируемы).

### Группа 3 (исходная формулировка). Композиция связанных объектов через `ModelFieldWidget(field=relation)`

**Проблема.** Связь выводится вручную (`foreach … ModelWidget(view='card')` или
`renderItem` + `implode`) с захардкоженным `<h4>`, а не через ModelFieldWidget
(у которого есть `itemViewPath`/label — как уже сделано для services comps/techs).

**Где:**
- `views/net-ips/card.php:32-46` — comps/techs/users «привязан к» (foreach+implode).
- `views/networks/card.php:52-53` — `orgInets` «Относится к вводу интернет».
- `views/org-inet/card.php:56` — `networks` «Подсети».
- `views/materials/card.php` — `contracts` (78-79), `children` (105-106), `usages`
  (115-116).
- `views/lic-types/card.php:52` — `licGroups` (foreach renderItem).
- `views/comps/lics_list.php` — `licGroups`+`licItems`+`licKeys` (смешанный список).
- `views/techs/attached/lics.php:23-30` — то же трио.
- `views/access-types/card.php:37-42` — `children` (ul/li renderItem).
- `views/aces/list.php` — `acl`/`acl->schedule` «Имеет доступ к:».
- `views/lic-keys/card.php`, `views/lic-items/card.php` — `licGroup`/`licItem`
  renderItem связанных.

**Решение.** Где связь — объявленный link/loader-атрибут: заменить на
`ModelFieldWidget::widget(['field'=>rel,'label'=>…,'itemViewPath'=>… если карточки,
'lineBr'/'glue'=>…])`. Требуется: (1) связь как link/loader; (2) выбор item- vs
card-рендера. **Смешанные списки** (`lics_list`, `attached/lics` — три связи в одном
блоке) ModelFieldWidget одним полем не покрывает: либо мульти-поле-режим виджета,
либо оставить осознанной композицией (кандидат в исключения).

### Группа 4. Дообъявить `typeClass` простым `links`/text-атрибутам (мелочь)

`lic-types.links`, `soft.links` — сейчас резолвятся в `UrlsType` по имени (работают
неявно). Для явности и защиты от переименований добавить `typeClass=UrlsType`.
Фактически покрыто конвенцией; низкий приоритет. (Вывод этих полей входит в
тривиальный батч.)

### Механизм для композитов: `renderCompositeTitle` (документирование, не унификация значения) — ✅ СДЕЛАН

Для блоков, собранных из НЕСКОЛЬКИХ атрибутов кастомной логикой (значение одним
типом не выразить), значение остаётся во вьюхе, а заголовок блока получает «?»,
перечисляющую участвующие атрибуты и их смысл. Так самодокументируемость
(element-locality) сохраняется и там, где унификация вывода невозможна.
- `AttributeTooltip::buildComposite($model,$fields,$title,$intro='Использованы атрибуты:')`
  — тултип со списком `Label: смысл` по каждому полю (переиспользует meaning()/title()).
- `ModelFieldWidget::renderCompositeTitle($model,$fields,$label,$tag='h4')` — чистый label
  + видимая «?» (tag='span' для инлайн-подписи внутри существующего заголовка).
Прототипы (php -l + PageAccessCest 908/0/7 зелёные): `net-ips/card.php` («Привязан к» над
comps/techs/users), `access-types/card.php` (флаги is_app/is_ip/is_phone/is_vpn).
**Это санкционированный способ для Группы 5 и слитных блоков Группы 3.**

### Группа 5. Условная проза / статусные композиты — ✅ ЧАСТИЧНО (composite-title там, где есть подпись)

Сделано (2026-07-09): access-types (флаги is_app/is_ip/is_phone/is_vpn → composite-title
«Категории»; `ip_params_def`, `children` → renderFieldTitle), users (`employee_id`+`Persg`
→ composite-title «Табельный №»; Doljnost/Login/work_phone — ранее), services расписания
(`providingScheduleRecursive`/`supportScheduleRecursive` — метки → renderFieldTitle, warning
и ссылки создания оставлены).
Осознанные исключения (чистая фраза-предложение без подписи, либо уже самодокументируемо):
users «Работает с/Уволен с DATE» (Uvolen/resign/employ — статус-фраза), materials-usages
(получено/израсходовано по знаку count), scans (format/humanFileSize в подсказках),
arms/arm-status (comment после статуса), contracts шапка (datePart+pay_id) и item-state
(undeliveredDescription), lic-keys/view (key_text в `<pre>`+Markdown), lic-items/stat
(status/datePart + счётчики СВЯЗЕЙ), networks DHCP (`text_dhcp` asNtext — возможен
многострочный список, plain-encode потерял бы переносы). Все они — либо композиция-проза
(значение из нескольких атрибутов в предложении), либо счётчики связей; при желании
документируются тем же composite-title/скрытым hint точечно.

### Группа 5 (исходная формулировка). Условная проза / статусные композиты

**Проблема.** Несколько атрибутов вплетены в человекочитаемую фразу/статус — это не
«вывод одного атрибута».

**Где:**
- `views/access-types/card.php:26-35` — флаги `is_app/is_ip/is_phone/is_vpn` списком
  + `ip_params_def` с подписью.
- `views/maintenance-reqs/view.php:38-42` — `is_backup/spread_comps/spread_techs`
  иконками в заголовке.
- `views/users/card.php` — `employee_id`+`Persg`; `Uvolen`/`resign_date`/`employ_date`
  («Работает с…/Уволен с…»); `Doljnost`, `Email` (asEmail), телефоны.
- `views/materials-usages/card.php:48` — направление по знаку `count`
  («получено/израсходовано»).
- `views/scans/preview.php`, `views/scans/thumb.php` — `format`+`humanFileSize`,
  `shortFname` в текстах ошибок/подсказок.
- `views/arms/arm-status.php:10` — `comment` после статуса.
- `views/contracts/card.php:33-35` — `datePart`+`pay_id` в шапке; `item-state.php:20`
  `undeliveredDescription` (implode) со статусной обёрткой.
- `views/lic-keys/view.php:43-45` — `key_text` в `<pre>` + `comment` через Markdown.
- `views/lic-items/stat.php` — статусные строки со `status`/`datePart`.

**Решение.** ModelFieldWidget не применяется. Варианты (решение владельца):
(a) оставить композицией, задокументировать как исключение (симметрично исключениям
в правиле); (b) где это по сути один атрибут в тонкой обёртке (например набор
booleans maintenance-reqs) — вывести каждый BooleanType виджетом, приняв изменение
UX (иконки → строки).

### Группа 6. Имя объекта в шапке — смежное правило (renderItem), вне этого скоупа

`Html::encode($model->name)`/`sname`/`fullName`/`title` в `<h1>/<h3>`:
`manufacturers` (name), `net-ips` (sname), `net-vlans` (sname), `org-struct` (name),
`places` (name/container), `ports` (fullName), `org-phones` (title), `tags`
(renderItem), заголовки `lic-keys`/`lic-items`.
Это правило «имя объекта — всегда `renderItem`/`LinkObjectWidget`», не
ModelFieldWidget. Проверить, что заголовок идёт через `LinkObjectWidget`; голый
`Html::encode($model->name)` в `<h1>` — нарушение СМЕЖНОГО правила, чинится там.

---

## Порядок исполнения (рекомендация)

1. Тривиальный батч (эта итерация) + `php -l` + `PageAccessCest`.
2. Группа 1 (MoneyType) — один тип закрывает 5 карточек.
3. Группа 2 — метаданные для computed (сначала 2b/2c как более локальные, затем 2a
   с механизмом наследования `*Recursive`).
4. Группа 3 — постранично, с решением item vs card по каждой связи.
5. Группы 5/6 — вынести решение владельцу (исключения vs доработка).

Каждый шаг — точечные диффы + прогон `PageAccessCest` (908) и unit (`ModelTitlesTest`
и пр.) в тихом окне общей `arms_test`.
