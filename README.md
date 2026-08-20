# БД для инвентаризации ИТ инфрастуктуры

## FEATURES/ВОЗМОЖНОСТИ

- [Учет оборудования](https://inventory.reviakin.net/techs/index)
  - [Учет используемых на предприятии моделей оборудования](https://inventory.reviakin.net/tech-models/index)
  - [Компоновка стоек и шкафов](https://inventory.reviakin.net/techs/view?id=18)
  - [Учет портов](https://inventory.reviakin.net/techs/view?id=12)
- [Учет операционных систем](https://inventory.reviakin.net/comps/index)
- [Компоновка рабочих мест](https://inventory.reviakin.net/places/armmap)
- [Учет предоставляемых ИТ отделом услуг и сервисов](https://inventory.reviakin.net/services/index?showChildren=1)
  - [Распределение сервисов по ответственным](https://inventory.reviakin.net/services/index-by-users)
  - [Планирование отсутствий сотрудников](https://inventory.reviakin.net/services/index-by-users?disabled_ids%5B1%5D=6&disabled_ids%5B2%5D=9) для оценки деградации поддержки сервисов
- [Учет лицензий](https://inventory.reviakin.net/lic-groups/index)
  - [Учет ключей](https://inventory.reviakin.net/lic-items/view?id=1)
- [Учет сегментов инфраструктуры](https://inventory.reviakin.net/segments/index)
- [Учет сетей](https://inventory.reviakin.net/networks/index), [Vlan](https://inventory.reviakin.net/net-vlans/index), [IP Адресов](https://inventory.reviakin.net/networks/view?id=12)
  - [Учет вводов интернет](https://inventory.reviakin.net/org-inet/index) и [подключений телефонии](https://inventory.reviakin.net/org-phones/index) c [привязкой к договорам](https://inventory.reviakin.net/services/view?id=2)
- [Ведение расписаний](https://inventory.reviakin.net/schedules/view?id=4)
- [Учет временных доступов](https://inventory.reviakin.net/scheduled-access/view?id=6)
  - [В т.ч. сотрудникам внешних организаций](https://inventory.reviakin.net/partners/view?id=2)

## REQUIREMENTS/ТРЕБОВАНИЯ

PHP 8.1
MariaDB 10

## INSTALLATION/УСТАНОВКА

[Установка](https://wiki.reviakin.net/%D0%B8%D0%BD%D0%B2%D0%B5%D0%BD%D1%82%D0%B0%D1%80%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F:%D1%83%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0)

## CONFIGURATION/НАСТРОЙКА

[Настройка](https://wiki.reviakin.net/%D0%B8%D0%BD%D0%B2%D0%B5%D0%BD%D1%82%D0%B0%D1%80%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F:%D0%BD%D0%B0%D1%81%D1%82%D1%80%D0%BE%D0%B9%D0%BA%D0%B0)

## Дополнительная документация

- [Структура проекта](structure.md)
- [Стандарты кодирования](standards.md)
- [Тесты](tests/readme.md)
- [Swagger/OpenAPI](swagger/readme.md)
