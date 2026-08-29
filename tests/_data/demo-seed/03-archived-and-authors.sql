-- Демо-данные, этап 3: архивные записи и авторство правок (см. tests/_data/readme.md).
--
-- Тогглер «Показывать архивные» и угловой виджет (архивность + «Изменено»)
-- есть на 13 страницах, но показывать было нечего: `archived` стоял нулём
-- везде, кроме одного состояния оборудования, а `updated_by` был пуст.
--
-- Архивируем не рабочие записи, а специально заведённые «выведенные из
-- эксплуатации» (id>=9000) плюс справочные позиции, за которыми в демо
-- ничего не числится (модели без оборудования, устаревший софт).
--
-- Осторожно с ON UPDATE CURRENT_TIMESTAMP (comps, soft, tech_models,
-- tech_types, techs, lic_groups, lic_types, partners, manufacturers*):
-- дату там задаём явно, иначе каждый прогон сида менял бы её на «сейчас»
-- и давал шумный diff дампа.

SET NAMES utf8mb4;

DELETE FROM `comps` WHERE `id`>=9000;
DELETE FROM `services` WHERE `id`>=9000;
DELETE FROM `networks` WHERE `id`>=9000;
DELETE FROM `segments` WHERE `id`>=9000;
DELETE FROM `sandboxes` WHERE `id`>=9000;
DELETE FROM `tags` WHERE `id`>=9000;
DELETE FROM `maintenance_jobs` WHERE `id`>=9000;
DELETE FROM `maintenance_reqs` WHERE `id`>=9000;

-- ---------------------------------------------------------------------------
-- Выведенное из эксплуатации: видно только с включённым тогглером «Архивные»
-- ---------------------------------------------------------------------------
INSERT INTO `comps` (`id`,`domain_id`,`name`,`os`,`raw_hw`,`raw_soft`,`raw_version`,`ip`,`mac`,
	`arm_id`,`comment`,`user_id`,`archived`,`external_links`,`updated_at`,`updated_by`) VALUES
(9001,1,'msk-fsrv-old','6.3.9600 Майкрософт Windows Server 2012 R2 Standard','','','',
	'10.20.75.9','00:15:5d:01:2a:09',NULL,'Заменён на MSK-FSRV, выключен 2026-06-30',NULL,1,'[]',
	'2026-06-30 14:20:00','VeniaminLevchenko');

INSERT INTO `services` (`id`,`name`,`description`,`links`,`is_end_user`,`notebook`,`responsible_id`,
	`segment_id`,`parent_id`,`archived`,`is_service`,`currency_id`,`search_text`,`weight`,
	`external_links`,`updated_at`,`updated_by`) VALUES
(9001,'Файловый архив (выведен)','Общие папки старого файлового сервера','',1,'',9,
	6,NULL,1,1,1,'',100,'[]','2026-06-30 14:25:00','VeniaminLevchenko');

INSERT INTO `networks` (`id`,`name`,`vlan_id`,`text_addr`,`addr`,`mask`,`comment`,`segments_id`,
	`notepad`,`archived`,`links`,`ranges`,`updated_at`,`updated_by`) VALUES
(9001,'MSK_OLD_LAN',NULL,'10.20.90.0/24',169105920,24,'Сеть старого файлового сервера, выведена',6,
	'',1,'','','2026-06-30 14:30:00','VeniaminLevchenko');

INSERT INTO `segments` (`id`,`name`,`description`,`code`,`history`,`archived`,`links`,
	`updated_at`,`updated_by`) VALUES
(9001,'Гостевой WiFi (закрыт)','Сегмент гостевой сети, закрыт после перехода на ваучеры',
	'segment_guest_wifi_old','',1,'','2026-05-18 09:10:00','DaniilZimin');

INSERT INTO `sandboxes` (`id`,`name`,`suffix`,`network_accessible`,`notepad`,`links`,`archived`,
	`updated_at`,`updated_by`) VALUES
(9001,'Песочница миграции 1С 8.2','1C82_MIGR',0,'Снесена после перевода базы на 8.3','',1,
	'2026-04-02 11:00:00','admin');

INSERT INTO `tags` (`id`,`name`,`slug`,`color`,`description`,`usage_count`,`archived`,
	`created_at`,`updated_at`,`updated_by`) VALUES
(9001,'Устаревшее','ustarevshee','#999999','Помечает выведенное из эксплуатации',0,1,
	'2026-04-02 11:05:00','2026-04-02 11:05:00','admin');

INSERT INTO `maintenance_jobs` (`id`,`name`,`description`,`schedules_id`,`services_id`,`links`,
	`updated_at`,`updated_by`,`archived`,`parent_id`,`external_links`) VALUES
(9001,'Выгрузка архива на ленты LTO','Ежемесячная выгрузка на ленточную библиотеку, отменена после отказа от LTO',
	NULL,NULL,NULL,'2026-05-18 09:20:00','admin',1,NULL,NULL);

INSERT INTO `maintenance_reqs` (`id`,`name`,`description`,`is_backup`,`spread_comps`,`spread_techs`,
	`links`,`updated_at`,`updated_by`,`archived`,`external_links`) VALUES
(9001,'Хранение копий на LTO (выведено)','Требование отменено вместе с ленточной библиотекой',
	1,0,0,NULL,'2026-05-18 09:22:00','admin',1,NULL);

-- ---------------------------------------------------------------------------
-- Архивные позиции справочников: за ними в демо ничего не числится
-- ---------------------------------------------------------------------------
-- модели, которых нет ни у одной единицы оборудования (сняты с производства)
UPDATE `tech_models` SET `archived`=1, `updated_at`='2026-03-11 08:00:00', `updated_by`='admin'
	WHERE `id` IN (2,3,5);
-- типы без оборудования уходят из меню совсем, редкие - только из меню
UPDATE `tech_types` SET `archived`=1, `updated_at`='2026-03-11 08:05:00', `updated_by`='admin'
	WHERE `code` IN ('videoconf','tsd');
UPDATE `tech_types` SET `hide_menu`=1, `updated_at`='2026-03-11 08:05:00', `updated_by`='admin'
	WHERE `code` IN ('usb_k','web_camera');
-- софт, который больше не устанавливается
UPDATE `soft` SET `archived`=1, `updated_at`='2026-03-11 08:10:00', `updated_by`='admin'
	WHERE `id` IN (43,79);
-- цвета, которыми ничего не покрашено
UPDATE `markers` SET `archived`=1, `updated_at`='2026-03-11 08:15:00', `updated_by`='admin'
	WHERE `id` IN (81,82);

-- ---------------------------------------------------------------------------
-- «Изменено»: кто и когда правил запись (вторая половина углового виджета)
-- ---------------------------------------------------------------------------
UPDATE `techs` SET `updated_at`='2026-08-23 12:10:00', `updated_by`='admin'             WHERE `id` IN (12,22,45);
UPDATE `techs` SET `updated_at`='2026-08-14 07:30:00', `updated_by`='VeniaminLevchenko' WHERE `id` IN (14,23,33,34);
UPDATE `techs` SET `updated_at`='2026-07-02 15:45:00', `updated_by`='DaniilZimin'       WHERE `id` IN (46,47,48,49);
UPDATE `techs` SET `updated_at`='2026-06-19 10:05:00', `updated_by`='BorisBarinov'      WHERE `id` IN (5,6,7,8,9);

UPDATE `comps` SET `updated_at`='2026-08-20 06:40:00', `updated_by`='admin'             WHERE `id` IN (3,4,32,41);
UPDATE `comps` SET `updated_at`='2026-07-28 13:15:00', `updated_by`='VeniaminLevchenko' WHERE `id` IN (17,18,19);
UPDATE `comps` SET `updated_at`='2026-06-11 09:25:00', `updated_by`='BorisBarinov'      WHERE `id` IN (5,6,7,8);

UPDATE `services` SET `updated_at`='2026-08-17 11:00:00', `updated_by`='admin'             WHERE `id` IN (3,19,20,24);
UPDATE `services` SET `updated_at`='2026-07-21 08:40:00', `updated_by`='VeniaminLevchenko' WHERE `id` IN (8,9,10,18);
UPDATE `services` SET `updated_at`='2026-06-05 16:20:00', `updated_by`='DaniilZimin'       WHERE `id` IN (12,13,14,15);

UPDATE `networks` SET `updated_at`='2026-07-15 10:30:00', `updated_by`='DaniilZimin' WHERE `id` IN (1,2,3,4);
UPDATE `networks` SET `updated_at`='2026-08-01 12:00:00', `updated_by`='admin'       WHERE `id` IN (5,6,7);

UPDATE `segments` SET `updated_at`='2026-07-15 10:35:00', `updated_by`='DaniilZimin' WHERE `id` IN (1,4,6);
UPDATE `segments` SET `updated_at`='2026-08-01 12:05:00', `updated_by`='admin'       WHERE `id` IN (7,8,9);

UPDATE `sandboxes` SET `updated_at`='2026-07-09 14:05:00', `updated_by`='VeniaminLevchenko' WHERE `id` IN (1,2);
UPDATE `tags`      SET `updated_at`='2026-07-09 14:10:00', `updated_by`='admin'             WHERE `id` IN (1,2);

UPDATE `contracts` SET `updated_at`='2026-07-30 09:20:00', `updated_by`='admin'       WHERE `id` IN (3,4,7);
UPDATE `contracts` SET `updated_at`='2026-06-24 11:40:00', `updated_by`='DaniilZimin' WHERE `id` IN (1,2,5);

UPDATE `maintenance_jobs` SET `updated_at`='2026-07-06 07:50:00', `updated_by`='admin' WHERE `id` IN (1,2,3);
UPDATE `maintenance_reqs` SET `updated_at`='2026-07-06 07:55:00', `updated_by`='admin' WHERE `id` IN (1,2);

UPDATE `lic_items` SET `updated_at`='2026-07-17 09:30:00', `updated_by`='admin'             WHERE `id` IN (1,2,3);
UPDATE `lic_keys`  SET `updated_at`='2026-07-17 09:35:00', `updated_by`='VeniaminLevchenko' WHERE `id` IN (1,2,3,4);
UPDATE `materials` SET `updated_at`='2026-06-27 13:20:00', `updated_by`='BorisBarinov'      WHERE `id` IN (1,2,3);

UPDATE `soft` SET `updated_at`='2026-05-30 10:10:00', `updated_by`='BorisBarinov' WHERE `id` IN (76,15);
UPDATE `partners` SET `updated_at`='2026-05-30 10:15:00', `updated_by`='admin' WHERE `id` IN (1,2,3);
