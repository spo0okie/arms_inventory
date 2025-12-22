# Методы API за рамками базового CRUD + search + filter

Эти методы требуют отдельной реализации тестов в следующих этапах разработки test-suite.

## CompsController
- actionUpload - загрузка сканов для компьютера

## LoginJournalController
- actionPush - отправка записи в журнал входов

## NetIpsController
- actionFirstUnused - получение первого неиспользуемого IP адреса

## ScansController
- actionUpload - загрузка файла скана
- actionDownload - скачивание файла скана

## SchedulesController
- actionStatus - получение статуса расписания
- actionMetaStatus - получение мета-статуса расписания
- actionNextMeta - получение следующего мета-расписания
- actionDaysSchedules - получение расписаний на дни

## TechsController
- actionSearchByMac - поиск оборудования по MAC адресу
- actionSearchByUser - поиск оборудования по пользователю

## UsersController
- actionWhoami - получение информации о текущем пользователе

## PhonesController
- actionSearchByNum - поиск имени пользователя по номеру телефона
- actionSearchByUser - поиск номера телефона по ID или логину пользователя

## LicLinksController
нестандартный для него отдельный тест надо писать