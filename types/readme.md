# Types

В этой папке живут классы типов атрибутов моделей.

Идея:

- тип - это отдельный класс, который знает, как
  - рендерить input
  - рендерить output
  - как описывать себя в API
  - как генерировать данные (для ModelFactory)
  - custom GridColum (если есть)
- модели в `attributeData()` ссылаются на класс типа ( `'typeClass' => StringType::class`), а не держат логику типов у себя;
- метаданные конкретного атрибута (label/hint и т.п.) остаются в модели и не смешиваются с типом.

Это уменьшает `switch/case`, локализует знания о типе и упрощает расширение типов.

## Текущие типы

- integer
- float
- string
- text
- urls
- ips
- macs
- json
- boolean
- date
- datetime
- link
- string[]

## Добавить

- schedule: строка, формат расписания на день
- inv-num: строка, формат инвентарного номера
- color: строка, формат цвета HEX

## Текущие rules

```php
Array
(
    [string] => Array
        (
            [count] => 116
            [sample] => app\models\AccessTypes->notepad
        )

    [integer] => Array
        (
            [count] => 40
            [sample] => app\models\AccessTypes->is_app
        )

    [each] => Array
        (
            [count] => 16
            [sample] => app\models\AccessTypes->children_ids
        )

    [filter] => Array
        (
            [count] => 12
            [sample] => app\models\Aces->ips
        )

    [validateRequireOneOf] => Array
        (
            [count] => 2
            [sample] => app\models\Aces->services_ids
        )

    [required] => Array
        (
            [count] => 42
            [sample] => app\models\Comps->name
        )

    [default] => Array
        (
            [count] => 15
            [sample] => app\models\Comps->sandbox_id
        )

    [safe] => Array
        (
            [count] => 24
            [sample] => app\models\Comps->updated_at
        )

    [unique] => Array
        (
            [count] => 22
            [sample] => app\models\Comps->domain_id
        )

    [exist] => Array
        (
            [count] => 29
            [sample] => app\models\Comps->arm_id
        )

    [number] => Array
        (
            [count] => 5
            [sample] => app\models\Contracts->total
        )

    [boolean] => Array
        (
            [count] => 6
            [sample] => app\models\Contracts->is_successor
        )

    [validateRecursiveLink] => Array
        (
            [count] => 5
            [sample] => app\models\Contracts->parent_id
        )

    [ip] => Array
        (
            [count] => 3
            [sample] => app\models\NetIps->text_addr
        )

    [validateVlanRange] => Array
        (
            [count] => 1
            [sample] => app\models\NetVlans->vlan
        )

    [match] => Array
        (
            [count] => 1
            [sample] => app\models\Tags->color
        )

    [closures] => Array
        (
            [count] => 23
            [sample] => Array
                (
                    [0] => Array
                        (
                            [0] => app\models\Aces->ips
                        )

                    [1] => Array
                        (
                            [0] => app\models\Comps->arm_id
                        )

                    [2] => Array
                        (
                            [0] => app\models\LicItems->descr
                        )

                    [3] => Array
                        (
                            [0] => app\models\MaintenanceReqs->includes_ids
                        )

                    [4] => Array
                        (
                            [0] => app\models\MaintenanceReqs->included_ids
                        )

                    [5] => Array
                        (
                            [0] => app\models\Places->parent_id
                        )

                    [6] => Array
                        (
                            [0] => app\models\Ports->link_ports_id
                        )

                    [7] => Array
                        (
                            [0] => app\models\Scans->scanFile
                        )

                    [8] => Array
                        (
                            [0] => app\models\Tags->name
                        )

                    [9] => Array
                        (
                            [0] => app\models\TechModels->contain_back_rack
                        )

                    [10] => Array
                        (
                            [0] => app\models\TechModels->contain_front_rack
                        )

                    [11] => Array
                        (
                            [0] => app\models\Techs->ip
                        )

                    [12] => Array
                        (
                            [0] => app\models\Techs->num
                        )

                    [13] => Array
                        (
                            [0] => app\models\Techs->num
                        )

                    [14] => Array
                        (
                            [0] => app\models\Techs->arms_id
                        )

                    [15] => Array
                        (
                            [0] => app\models\Techs->installed_id
                        )

                    [16] => Array
                        (
                            [0] => app\models\Users->Login
                        )

                    [17] => Array
                        (
                            [0] => app\modules\schedules\models\Schedules->start_date
                        )

                    [18] => Array
                        (
                            [0] => app\modules\schedules\models\SchedulesEntries->date
                        )

                    [19] => Array
                        (
                            [0] => app\modules\schedules\models\SchedulesEntries->schedule
                        )

                    [20] => Array
                        (
                            [0] => app\modules\schedules\models\SchedulesEntries->date
                        )

                    [21] => Array
                        (
                            [0] => app\modules\schedules\models\SchedulesEntries->date
                        )

                    [22] => Array
                        (
                            [0] => app\modules\schedules\models\SchedulesEntries->date
                        )

                )

        )

)
```

---

## Соответствие типов (typeClass)

Данная секция описывает соответствие между правилами валидации Yii2 и классами типов атрибутов.

### Существующие typeClass

| typeClass | Файл | Описание |
|-----------|------|----------|
| `BooleanType` | [`types/BooleanType.php`](types/BooleanType.php) | Логический тип (true/false) |
| `DateType` | [`types/DateType.php`](types/DateType.php) | Дата |
| `DatetimeType` | [`types/DatetimeType.php`](types/DatetimeType.php) | Дата и время |
| `FloatType` | [`types/FloatType.php`](types/FloatType.php) | Число с плавающей точкой |
| `IntegerType` | [`types/IntegerType.php`](types/IntegerType.php) | Целое число |
| `IpsType` | [`types/IpsType.php`](types/IpsType.php) | IP-адрес (IPv4/IPv6) |
| `JsonType` | [`types/JsonType.php`](types/JsonType.php) | JSON-данные |
| `LinkType` | [`types/LinkType.php`](types/LinkType.php) | Связь с другой моделью (FK) |
| `MacsType` | [`types/MacsType.php`](types/MacsType.php) | MAC-адрес |
| `StringArrayType` | [`types/StringArrayType.php`](types/StringArrayType.php) | Массив строк |
| `StringType` | [`types/StringType.php`](types/StringType.php) | Строка |
| `TextType` | [`types/TextType.php`](types/TextType.php) | Многострочный текст |
| `UrlsType` | [`types/UrlsType.php`](types/UrlsType.php) | Список URL |

### Соответствие правил валидации typeClass

#### Правила, определяющие тип

| Правило валидации | Кол-во | typeClass | Примечание |
|-------------------|--------|-----------|------------|
| `string` | 116 | `StringType` | Строковое значение |
| `integer` | 40 | `IntegerType` | Целое число |
| `exist` | 29 | `LinkType` | Внешний ключ (FK) на связанную модель |
| `each` | 16 | `StringArrayType` | Массив строк (foreach по элементам) |
| `boolean` | 6 | `BooleanType` | Логическое значение |
| `number` | 5 | `FloatType` | Число с плавающей точкой |
| `ip` | 3 | `IpsType` | IP-адрес |
| `match` | 1 | `StringType` | ASSUMPTION: регулярное выражение может указывать на специализированный тип |

#### Правила, не влияющие на тип

| Правило валидации | Кол-во | Причина |
|-------------------|--------|---------|
| `required` | 42 | Только обязательность, не тип данных |
| `safe` | 24 | Только массовое присваивание, не тип данных |
| `unique` | 22 | Только уникальность в БД, не тип данных |
| `filter` | 12 | Только фильтрация входных данных |
| `default` | 15 | Только значение по умолчанию |
| `validateRequireOneOf` | 2 | Бизнес-правило взаимозависимости |
| `validateRecursiveLink` | 5 | Специальная проверка иерархических связей |
| `validateVlanRange` | 1 | Специальная проверка диапазона VLAN |

### Closures

Ниже приведено ТЗ для каждой closure-валидации (список атрибутов из секции `[closures]`):

| # | Модель → Атрибут | Что проверяет (ТЗ) | Примечание |
| - | ---------------- | ------------------ | ---------- |
| 1 | `Aces → ips` | Каждое значение в списке IP-адресов является валидным IPv4/IPv6. Невалидные адреса отклоняются с сообщением об ошибке. | Перенести в тип |
| 2 | `Comps → arm_id` | ОС не может одновременно быть привязана к АРМ (`arm_id`) и к облачной платформе (`platform_id`). Если оба поля заполнены — ошибка: «ОС не может работать на оборудовании и предоставляться услугой одновременно». | Бизнес-логика модели |
| 3 | `LicItems → descr` | Описание закупки (`descr`) уникально в рамках одной группы лицензий (`lic_group_id`). Дубликаты отклоняются с сообщением: «Такая закупка уже существует в этой группе лицензий». | Почему не обычный unque? |
| 4 | `MaintenanceReqs → includes_ids` | Проверка рекурсивных ссылок: выбранные требования (`includes_ids`) не должны содержать циклических зависимостей (требление самого себя или зависимость через другие требования). | Есть же отдельная валидация рекурсии |
| 5 | `MaintenanceReqs → included_ids` | Аналогично `includes_ids` — проверка отсутствия циклических зависимостей в обратной связи (что это требование не включено в само себя). | Есть же отдельная валидация рекурсии |
| 6 | `Places → parent_id` | Проверка иерархии местоположений: текущее место не может быть родителем самого себя, не может быть предком своего предка (зацикливание). | Есть же отдельная валидация рекурсии |
| 7 | `Ports → link_ports_id` | Значение должно быть: числовым ID существующего порта, NULL, или строкой формата `create:N` (директива создания нового порта с именем N). Любое другое значение — ошибка: «Неверный порт устройства». | Рассмотреть подробнее |
| 8 | `Scans → scanFile` | *(это `file`-валидатор, не closure)* — Загружаемый файл должен иметь допустимое расширение из списка: `png, jpg, jpeg, pdf, gif, bmp, tiff`. Файл обязателен при создании (`skipOnEmpty => false`). | как вообще генерировать атрибуты-файлы? |
| 9 | `Tags → name` | *(в коде — trim + unique slug)* — Название тега после `trim()` не превышает 32 символа. Slug генерируется автоматически из имени и проверяется на уникальность. | может переделать его в code как везде и оставить только unique ? |
| 10 | `TechModels → contain_back_rack` | Если оборудование двухстороннее (`back_rack_two_sided = true`) и указано размещение спереди (`contain_front_rack = true`), то обратное размещение (`contain_back_rack`) также должно быть `true`. | бизнес-логика модели. к типу отношения не имеет |
| 11 | `TechModels → contain_front_rack` | Аналогично: если `front_rack_two_sided = true` и `contain_back_rack = true`, то `contain_front_rack` тоже `true`. | бизнес-логика модели. к типу отношения не имеет |
| 12 | `Techs → ip` | Аналогично `Aces → ips`: каждое значение в списке IP-адресов является валидным IPv4/IPv6. | можно вынести в тип |
| 13 | `Techs → num` (первая) | Формат инвентарного номера: токены через дефис соответствуют шаблону `<тип>-<серийный>`. | нужно создать InvNumType |
| 14 | `Techs → num` (вторая) | Инвентарный номер уникален для данного типа оборудования (`model_id`). Дубликаты отклоняются. | по сути аналог uniq |
| 15 | `Techs → arms_id` | Проверка рекурсивных ссылок: АРМ (`arms_id`) не может ссылаться на самого себя или создавать циклическую иерархию. | Есть же отдельная валидация рекурсии |
| 16 | `Techs → installed_id` | Проверка рекурсивных ссылок: место установки (`installed_id`) не может ссылаться на самого себя или создавать циклическую иерархию. | Есть же отдельная валидация рекурсии |
| 17 | `Users → Login` | Логин уникален. Исключение: если совпадает `uid` (ИНН) или `Ename` (ФИО) при включённом параметре `user.name_as_uid.enable`. | тут уместно |
| 18 | `Schedules → start_date` | *(сценарий override)* — Дата обязательна. Не должна пересекаться с периодами других override-расписаний того же родителя. | тут уместно |
| 19 | `SchedulesEntries → date` (сценарий day) | Значение должно быть: ключом из словаря дней недели (`1`–`7`, `def`) ИЛИ датой в формате `Y-m-d`. Дата не должна дублировать существующую запись расписания на этот день. | нужен отдельный тип ScheduleDayType |
| 20 | `SchedulesEntries → schedule` (сценарий day) | График обязателен (не пуст). Формат: `ЧЧ:ММ-ЧЧ:ММ` или несколько через запятую. Прочерк (`-`) — валидное значение (нерабочий день). | нужен отдельный тип ScheduleType |
| 21 | `SchedulesEntries → date` (сценарий period) | Дата начала и/или окончания периода. Если указаны обе — окончание позже начала. | тут все ок, бизнес-логика модели |
| 22 | `SchedulesEntries → date_end` (period) | Аналогично `date`: если период, то окончание не раньше начала. | тут все ок, бизнес-логика модели |
| 23 | `SchedulesEntries → date` (period, пересечения) | Период не должен пересекаться с другими периодами того же расписания. Пересечение — ошибка с указанием конфликтующего периода. | тут все ок, бизнес-логика модели, аналог unique |

---

### Планируемые typeClass

| typeClass | Описание | Приоритет |
|-----------|----------|-----------|
| `ScheduleType` | Строка, формат расписания | Высокий (есть в closures) |
| `ScheduleDayType` | Строка, формат даты/1-7/def | Высокий (есть в closures) |
| `InvNumType` | Строка, формат инвентарного номера | Средний |
| `ColorType` | Строка, формат цвета HEX (#RRGGBB) | Низкий |

### Принципы соответствия

1. **Правила валидации** (required, safe, unique, etc.) — **НЕ влияют** на typeClass
2. **Тип данных DB** (integer, string, boolean) — **влияет** на typeClass
3. **Формат данных** (ip, urls, macs) — **влияет** на typeClass
4. **Связи** (exist, FK) — **влияет** на typeClass (`LinkType`)
5. **Closures** — анализируются индивидуально, могут указывать на специализированный тип
