-- Демо-данные, этап 8: лицензии, документы, обслуживание (plans/demo-data.md).
--
-- Лицензии в демо висели только на оборудовании: «лицензия на ОС» и «лицензия
-- на сотрудника» показать было нечем, срок действия не заполнялся ни у одной
-- позиции. У документов пустовали срок, заявка на платёж, план поставки и
-- правопреемство. Требования и работы по обслуживанию были связаны только с
-- ОС - ни сервисов, ни оборудования.
--
-- Даты у lic_groups/lic_types задаются явно: там ON UPDATE CURRENT_TIMESTAMP.

SET NAMES utf8mb4;

DELETE FROM `lic_items_in_comps` WHERE `id`>=9000;
DELETE FROM `lic_items_in_users` WHERE `id`>=9000;
DELETE FROM `lic_keys_in_comps` WHERE `id`>=9000;
DELETE FROM `lic_keys_in_users` WHERE `id`>=9000;
DELETE FROM `lic_groups_in_users` WHERE `id`>=9000;
DELETE FROM `lic_items` WHERE `id`>=9000;
DELETE FROM `lic_groups` WHERE `id`>=9000;
DELETE FROM `contracts` WHERE `id`>=9000;
DELETE FROM `maintenance_jobs_in_services` WHERE `id`>=9000;
DELETE FROM `maintenance_jobs_in_techs` WHERE `id`>=9000;
DELETE FROM `maintenance_reqs_in_comps` WHERE `id`>=9000;
DELETE FROM `maintenance_reqs_in_services` WHERE `id`>=9000;
DELETE FROM `maintenance_reqs_in_techs` WHERE `id`>=9000;

-- ---------------------------------------------------------------------------
-- Срочные лицензии: действующая подписка и просроченная позиция
-- ---------------------------------------------------------------------------
INSERT INTO `lic_groups` (`id`,`lic_types_id`,`descr`,`comment`,`services_id`,`updated_at`,`updated_by`,
	`created_at`,`created_by`) VALUES
(9001,10,'Zabbix Support Silver','Годовая подписка на поддержку системы мониторинга',20,
	'2026-01-11 10:00:00','admin','2026-01-11 10:00:00','admin');

INSERT INTO `lic_items` (`id`,`lic_group_id`,`descr`,`count`,`comment`,`active_from`,`active_to`,
	`created_at`,`services_id`,`updated_at`,`updated_by`) VALUES
(9001,9001,'Подписка Zabbix Support на 2026 год',1,'Продлевается в декабре',
	'2026-01-01','2026-12-31','2026-01-11 10:05:00',20,'2026-01-11 10:05:00','admin'),
(9002,25,'1С:Предприятие 8.3, клиентские лицензии (годовые)',10,'Срок истёк, продление в работе',
	'2025-07-01','2026-06-30','2025-06-24 09:00:00',18,'2026-07-17 09:40:00','VeniaminLevchenko');

-- у существующей позиции КриптоПро тоже есть срок
UPDATE `lic_items` SET `active_from`='2026-03-01', `active_to`='2027-02-28' WHERE `id`=4;

-- ---------------------------------------------------------------------------
-- Лицензии на ОС и на сотрудников (раньше были только на оборудовании)
-- ---------------------------------------------------------------------------
INSERT INTO `lic_items_in_comps` (`id`,`lic_items_id`,`comps_id`,`comment`,`updated_by`,`updated_at`,`created_by`,`created_at`) VALUES
(9001,9002,17,'Сервер приложений 1С',1,'2026-07-17 09:45:00',1,'2026-07-17 09:45:00'),
(9002,9002,19,'Терминальный сервер 1С',1,'2026-07-17 09:45:00',1,'2026-07-17 09:45:00'),
(9003,2,1,'Хост виртуализации',1,'2026-07-17 09:46:00',1,'2026-07-17 09:46:00'),
(9004,2,2,'Хост виртуализации',1,'2026-07-17 09:46:00',1,'2026-07-17 09:46:00'),
(9005,9001,33,'Сервер мониторинга',1,'2026-07-17 09:47:00',1,'2026-07-17 09:47:00');

INSERT INTO `lic_items_in_users` (`id`,`lic_items_id`,`users_id`,`comment`,`updated_by`,`updated_at`,`created_by`,`created_at`) VALUES
(9001,9002,2,'Бухгалтерия',1,'2026-07-17 09:50:00',1,'2026-07-17 09:50:00'),
(9002,9002,4,'Бухгалтерия',1,'2026-07-17 09:50:00',1,'2026-07-17 09:50:00'),
(9003,9002,12,'Бухгалтерия',1,'2026-07-17 09:50:00',1,'2026-07-17 09:50:00'),
(9004,9002,13,'Бухгалтерия',1,'2026-07-17 09:50:00',1,'2026-07-17 09:50:00'),
(9005,4,3,'КриптоПро для обмена с банком',1,'2026-07-17 09:52:00',1,'2026-07-17 09:52:00');

INSERT INTO `lic_keys_in_comps` (`id`,`lic_keys_id`,`comps_id`,`comment`,`updated_by`,`updated_at`,`created_by`,`created_at`) VALUES
(9001,1,3,'Активирован при установке',1,'2026-07-17 09:55:00',1,'2026-07-17 09:55:00'),
(9002,2,4,'Активирован при установке',1,'2026-07-17 09:55:00',1,'2026-07-17 09:55:00');

INSERT INTO `lic_keys_in_users` (`id`,`lic_keys_id`,`users_id`,`comment`,`updated_by`,`updated_at`,`created_by`,`created_at`) VALUES
(9001,4,9,'Выдан под личный ноутбук',1,'2026-07-17 09:58:00',1,'2026-07-17 09:58:00'),
(9002,5,10,'Выдан под личный ноутбук',1,'2026-07-17 09:58:00',1,'2026-07-17 09:58:00');

INSERT INTO `lic_groups_in_users` (`id`,`lic_groups_id`,`users_id`,`comment`,`updated_by`,`updated_at`,`created_by`,`created_at`) VALUES
(9001,25,2,'Работает в 1С',1,'2026-07-17 10:00:00',1,'2026-07-17 10:00:00'),
(9002,25,4,'Работает в 1С',1,'2026-07-17 10:00:00',1,'2026-07-17 10:00:00'),
(9003,25,12,'Работает в 1С',1,'2026-07-17 10:00:00',1,'2026-07-17 10:00:00'),
(9004,25,13,'Работает в 1С',1,'2026-07-17 10:00:00',1,'2026-07-17 10:00:00');

-- ---------------------------------------------------------------------------
-- Документы: срок, заявка на платёж, план поставки, правопреемник
-- ---------------------------------------------------------------------------
UPDATE `contracts` SET `end_date`='2027-01-08', `pay_id`='ЗНП-2023-0444' WHERE `id`=1;
UPDATE `contracts` SET `end_date`='2026-10-10', `pay_id`='ЗНП-2020-1010' WHERE `id`=2;
UPDATE `contracts` SET `pay_id`='ЗНП-2020-0456', `techs_delivery`=6, `lics_delivery`=3 WHERE `id`=3;
UPDATE `contracts` SET `pay_id`='ЗНП-2021-0709', `techs_delivery`=20, `materials_delivery`=10 WHERE `id`=4;
UPDATE `contracts` SET `pay_id`='ЗНП-2019-0213' WHERE `id`=7;

-- дополнительное соглашение, которое заменяет собой предыдущее
INSERT INTO `contracts` (`id`,`parent_id`,`is_successor`,`name`,`date`,`end_date`,`comment`,`state_id`,
	`total`,`charge`,`currency_id`,`pay_id`,`techs_delivery`,`updated_at`,`updated_by`) VALUES
(9001,5,1,'ДС №2 - Серверные шкафы (замена ДС №1)','2019-04-12',NULL,
	'Заменяет ДС №1: сменилась модель шкафа',6,41250.00,6875.00,1,'ЗНП-2019-0288',2,
	'2026-07-30 09:30:00','admin');

-- стоимость и наценка у услуг (в демо не было заполнено ни у одной)
UPDATE `services` SET `cost`=8500,  `charge`=1416.67 WHERE `id`=1;    -- связь МСК
UPDATE `services` SET `cost`=6200,  `charge`=1033.33 WHERE `id`=2;    -- связь ЧЕЛ
UPDATE `services` SET `cost`=3500,  `charge`=583.33  WHERE `id`=19;   -- контроль доступа в интернет
UPDATE `services` SET `cost`=42000, `charge`=9240.00 WHERE `id`=9002; -- облачная платформа

-- ---------------------------------------------------------------------------
-- Обслуживание: работы и требования не только у ОС
-- ---------------------------------------------------------------------------
INSERT INTO `maintenance_jobs_in_services` (`id`,`services_id`,`jobs_id`) VALUES
(9001,26,1),
(9002,26,2),
(9003,18,2);

INSERT INTO `maintenance_jobs_in_techs` (`id`,`techs_id`,`jobs_id`) VALUES
(9001,45,1),
(9002,1,1),
(9003,2,2);

INSERT INTO `maintenance_reqs_in_services` (`id`,`reqs_id`,`services_id`) VALUES
(9001,1,18),
(9002,2,22),
(9003,2,24);

INSERT INTO `maintenance_reqs_in_comps` (`id`,`reqs_id`,`comps_id`) VALUES
(9001,1,17),
(9002,1,18),
(9003,1,19),
(9004,2,36),
(9005,2,41);

INSERT INTO `maintenance_reqs_in_techs` (`id`,`reqs_id`,`techs_id`) VALUES
(9001,1,1),
(9002,1,2),
(9003,2,45);

-- требование распространяется на оборудование сервиса, а не только на ОС
UPDATE `maintenance_reqs` SET `spread_techs`=1 WHERE `id`=1;
