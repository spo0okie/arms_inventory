-- Демо-данные, этап 9: хвосты (plans/demo-data.md).
--
-- Мелкие поля и связи, каждое из которых по отдельности не тянет на этап, но
-- вместе они и составляют «пустоту» карточек: сетевые имена и ответственные у
-- оборудования, задняя сторона стойки, теги не только на сервисах, параметры
-- ввода интернета, комментарии к помещениям и адресам, префиксы контрагентов,
-- журнал входов с типами.
--
-- Даты у techs/tech_models/partners задаются явно: ON UPDATE CURRENT_TIMESTAMP.

SET NAMES utf8mb4;

DELETE FROM `tags_links` WHERE `id`>=9000;
DELETE FROM `login_journal` WHERE `id`>=9000;

-- ---------------------------------------------------------------------------
-- Сетевые имена, веб-интерфейсы, ответственные и сервис управления
-- ---------------------------------------------------------------------------
UPDATE `techs` SET `hostname`='msk-sw-core', `domain_id`=1, `url`='https://10.20.1.1/',
	`uid`='c3560x-msk-0001', `responsible_id`=1, `management_service_id`=3,
	`updated_at`='2026-08-23 12:10:00', `updated_by`='admin' WHERE `id`=12;
UPDATE `techs` SET `hostname`='msk-sw-access', `domain_id`=1, `url`='https://10.20.1.2/',
	`uid`='c2960x-msk-0003', `responsible_id`=1, `management_service_id`=3,
	`updated_at`='2026-08-23 12:10:00', `updated_by`='admin' WHERE `id`=22;
UPDATE `techs` SET `hostname`='chl-sw-core', `domain_id`=1, `url`='https://10.50.1.1/',
	`uid`='c3560x-chl-0001', `responsible_id`=9, `management_service_id`=3,
	`updated_at`='2026-08-14 07:30:00', `updated_by`='VeniaminLevchenko' WHERE `id`=14;
UPDATE `techs` SET `hostname`='chl-sw-access', `domain_id`=1, `url`='https://10.50.1.2/',
	`uid`='c2960x-chl-0002', `responsible_id`=9, `management_service_id`=3,
	`updated_at`='2026-08-14 07:30:00', `updated_by`='VeniaminLevchenko' WHERE `id`=23;
UPDATE `techs` SET `hostname`='chl-sw-access-2', `domain_id`=1,
	`uid`='c2960x-chl-0003', `responsible_id`=9, `management_service_id`=3,
	`updated_at`='2026-08-23 12:00:00', `updated_by`='VeniaminLevchenko' WHERE `id`=9001;
UPDATE `techs` SET `hostname`='msk-san', `domain_id`=1, `url`='https://10.20.1.100/',
	`responsible_id`=9, `management_service_id`=5,
	`updated_at`='2026-08-23 12:10:00', `updated_by`='admin' WHERE `id`=45;
UPDATE `techs` SET `responsible_id`=9, `management_service_id`=5,
	`updated_at`='2026-08-14 07:30:00', `updated_by`='VeniaminLevchenko' WHERE `id` IN (1,2,33,34);
-- рабочие места закреплены за подразделениями
UPDATE `techs` SET `departments_id`=1, `updated_at`='2026-06-19 10:05:00', `updated_by`='BorisBarinov'
	WHERE `id` IN (5,6,7,8,9,10,11,17);

-- ---------------------------------------------------------------------------
-- Задняя сторона шкафа: у ND-05C есть задняя рама со своей раскладкой
-- ---------------------------------------------------------------------------
UPDATE `tech_models` SET `contain_back_rack`=1, `back_rack_two_sided`=0,
	`back_rack_layout`=`front_rack_layout`,
	`updated_at`='2026-08-10 09:00:00', `updated_by`='admin' WHERE `id`=309;

-- ---------------------------------------------------------------------------
-- Теги: описание, счётчик и метки не только на сервисах
-- ---------------------------------------------------------------------------
UPDATE `tags` SET `description`='Всё, что относится к телефонии и каналам связи', `usage_count`=6,
	`updated_at`='2026-07-09 14:10:00', `updated_by`='admin' WHERE `id`=1;
UPDATE `tags` SET `description`='Ресурсы, вынесенные к внешнему провайдеру', `usage_count`=4,
	`updated_at`='2026-07-09 14:10:00', `updated_by`='admin' WHERE `id`=2;

INSERT INTO `tags_links` (`id`,`tag_id`,`model_class`,`model_id`,`created_at`) VALUES
(9001,1,'app\\models\\Techs',46,'2026-07-09 14:20:00'),
(9002,1,'app\\models\\Techs',50,'2026-07-09 14:20:00'),
(9003,1,'app\\models\\Comps',15,'2026-07-09 14:21:00'),
(9004,2,'app\\models\\Comps',9005,'2026-08-05 10:20:00'),
(9005,2,'app\\models\\Comps',9006,'2026-08-05 10:20:00'),
(9006,2,'app\\models\\Services',9002,'2026-08-05 10:21:00');

-- ---------------------------------------------------------------------------
-- Вводы интернета: то, что выдал провайдер
-- ---------------------------------------------------------------------------
UPDATE `org_inet` SET `ip_addr`='81.200.15.42', `ip_mask`='255.255.255.248', `ip_gw`='81.200.15.41',
	`ip_dns1`='81.200.14.1', `ip_dns2`='81.200.14.2', `type`='Оптика (Ethernet), 100 Мбит/с',
	`static`=1 WHERE `id`=1;
UPDATE `org_inet` SET `ip_addr`='92.53.104.18', `ip_mask`='255.255.255.252', `ip_gw`='92.53.104.17',
	`ip_dns1`='92.53.96.1', `ip_dns2`='8.8.8.8', `type`='Оптика (Ethernet), 50 Мбит/с',
	`static`=1 WHERE `id`=2;

-- ---------------------------------------------------------------------------
-- Комментарии к адресам и помещениям
-- ---------------------------------------------------------------------------
UPDATE `net_ips` SET `mask`=24, `comment`='Интерфейс управления коммутатора' WHERE `text_addr` IN ('10.20.1.1','10.20.1.2','10.50.1.1','10.50.1.2');
UPDATE `net_ips` SET `mask`=24, `comment`='Хост виртуализации' WHERE `text_addr` IN ('10.20.1.10','10.20.1.11','10.50.1.10','10.50.1.11');

UPDATE `places` SET `comment`='Стойка ND-05C, кондиционер, СКУД на двери' WHERE `id`=6;
UPDATE `places` SET `comment`='Стойка ND-05C, вход по ключу у дежурного' WHERE `id`=8;
UPDATE `places` SET `comment`='Открытое пространство, 4 рабочих места' WHERE `id`=3;
UPDATE `places` SET `comment`='Открытое пространство, 6 рабочих мест' WHERE `id`=4;
UPDATE `places` SET `comment`='Рабочие места ИТ, тестовый стенд' WHERE `id`=11;

-- ---------------------------------------------------------------------------
-- Контрагенты: короткие имена и префиксы для автоподстановки
-- ---------------------------------------------------------------------------
UPDATE `partners` SET `prefix`='TAB',   `alias`='Табуретка',    `updated_at`='2026-05-30 10:15:00', `updated_by`='admin' WHERE `id`=1;
UPDATE `partners` SET `prefix`='X3',    `alias`='X3 Team',      `updated_at`='2026-05-30 10:15:00', `updated_by`='admin' WHERE `id`=2;
UPDATE `partners` SET `prefix`='DOMRU', `alias`='ДомРУ',        `updated_at`='2026-05-30 10:15:00', `updated_by`='admin' WHERE `id`=7;
UPDATE `partners` SET `prefix`='RT',    `alias`='Ростелеком',   `updated_at`='2026-05-30 10:15:00', `updated_by`='admin' WHERE `id`=57;

-- ---------------------------------------------------------------------------
-- Журнал входов: локальные и удалённые сеансы с отметками времени клиента
-- ---------------------------------------------------------------------------
UPDATE `login_journal` SET `type`=0, `created_at`='2025-05-09 14:30:30', `local_time`=1746801027 WHERE `id`=1;
UPDATE `login_journal` SET `type`=0, `created_at`='2025-06-05 14:09:45', `local_time`=1749132581 WHERE `id`=2;

INSERT INTO `login_journal` (`id`,`time`,`comp_name`,`comps_id`,`user_login`,`users_id`,`type`,
	`local_time`,`created_at`,`calc_time`) VALUES
(9001,'2026-08-26 06:12:04','wks-05',7,'KimSadovskiy',3,0,1787292724,'2026-08-26 06:12:10','2026-08-26 06:12:04'),
(9002,'2026-08-26 07:40:11','msk-1c-term',19,'KimSadovskiy',3,1,1787298011,'2026-08-26 07:40:15','2026-08-26 07:40:11'),
(9003,'2026-08-27 05:55:39','wks-03',5,'NinaBelozerova',2,0,1787378139,'2026-08-27 05:55:44','2026-08-27 05:55:39'),
(9004,'2026-08-27 09:14:02','msk-1c-term',19,'NinaBelozerova',2,1,1787390042,'2026-08-27 09:14:08','2026-08-27 09:14:02'),
(9005,'2026-08-27 12:31:57','admin-lab7',39,'DaniilZimin',6,1,1787402117,'2026-08-27 12:32:01','2026-08-27 12:31:57'),
(9006,'2026-08-28 05:48:20','chel-pc-01',9,'SilantiyGorodnov',12,0,1787464100,'2026-08-28 05:48:26','2026-08-28 05:48:20');
