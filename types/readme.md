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
