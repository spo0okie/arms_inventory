-- Демо-данные, этап 6: песочницы и облачные ОС (см. tests/_data/readme.md).
--
-- Песочницы в справочнике были, но ни одна ОС в них не стояла: суффиксы имён,
-- одноимённые ОС в разных окружениях и резолв имени показать было нечем.
-- Отдельно - ОС, которая работает не на нашем оборудовании, а «предоставляется
-- услугой» (comps.platform_id), и параметры ВМ у сервисов.
--
-- Дата у comps задаётся явно: там ON UPDATE CURRENT_TIMESTAMP.

SET NAMES utf8mb4;

DELETE FROM `comps_in_services` WHERE `id`>=9000;
-- 9001 в этих таблицах занят архивными записями этапа 3
DELETE FROM `comps` WHERE `id` BETWEEN 9002 AND 9099;
DELETE FROM `services` WHERE `id` BETWEEN 9002 AND 9099;

-- ---------------------------------------------------------------------------
-- Услуга-платформа: чужие вычислительные мощности
-- ---------------------------------------------------------------------------
INSERT INTO `services` (`id`,`name`,`description`,`links`,`is_end_user`,`notebook`,`responsible_id`,
	`providing_schedule_id`,`support_schedule_id`,`segment_id`,`parent_id`,`archived`,`is_service`,
	`currency_id`,`search_text`,`weight`,`external_links`,`vm_cores`,`vm_ram`,`vm_hdd`,
	`updated_at`,`updated_by`) VALUES
(9002,'Облачная платформа IaaS','Арендованные вычислительные мощности внешнего провайдера','',0,'',1,
	1,2,5,NULL,0,1,1,'',100,'[]',16,64,2000,'2026-08-05 09:30:00','admin');

-- ---------------------------------------------------------------------------
-- ОС в песочницах: имена совпадают с боевыми, различает суффикс окружения
-- ---------------------------------------------------------------------------
INSERT INTO `comps` (`id`,`domain_id`,`name`,`os`,`raw_hw`,`raw_soft`,`raw_version`,`ip`,`mac`,
	`arm_id`,`comment`,`user_id`,`archived`,`external_links`,`sandbox_id`,`platform_id`,
	`updated_at`,`updated_by`) VALUES
(9002,1,'msk-1c-app','10.0.20348 Майкрософт Windows Server 2022 Standard','','','',
	'10.20.75.61','00:15:5d:01:2a:61',2,'Тестовый сервер приложений 1С',NULL,0,'[]',1,NULL,
	'2026-08-05 10:00:00','VeniaminLevchenko'),
(9003,1,'msk-1c-term','10.0.20348 Майкрософт Windows Server 2022 Standard','','','',
	'10.20.75.62','00:15:5d:01:2a:62',2,'Тестовый терминальный сервер 1С',NULL,0,'[]',1,NULL,
	'2026-08-05 10:02:00','VeniaminLevchenko'),
(9004,1,'msk-inventory','Debian GNU/Linux 12 (bookworm)','','','',
	'10.20.75.63','00:15:5d:01:2a:63',1,'Обкатка обновлений ARMS',NULL,0,'[]',2,NULL,
	'2026-08-05 10:05:00','admin'),
-- ОС на чужой площадке: оборудования у нас нет, мощности даёт услуга
(9005,2,'cloud-www','Debian GNU/Linux 12 (bookworm)','','','',
	'92.53.96.14','',NULL,'Фронтенд сайта у провайдера',NULL,0,'[]',NULL,9002,
	'2026-08-05 10:10:00','admin'),
(9006,2,'cloud-mail','Debian GNU/Linux 12 (bookworm)','','','',
	'92.53.96.15','',NULL,'Почтовый релей у провайдера',NULL,0,'[]',NULL,9002,
	'2026-08-05 10:12:00','admin');

-- песочные копии обслуживают те же сервисы, что и боевые
INSERT INTO `comps_in_services` (`id`,`comps_id`,`services_id`) VALUES
(9001,9002,18),
(9002,9003,18),
(9003,9004,24),
(9004,9005,22),
(9005,9006,22);

-- ---------------------------------------------------------------------------
-- Параметры ВМ у сервисов: сколько ресурсов виртуализации под них выделено
-- ---------------------------------------------------------------------------
UPDATE `services` SET `vm_cores`=12, `vm_ram`=48, `vm_hdd`=1200 WHERE `id`=18; -- Кластер 1С
UPDATE `services` SET `vm_cores`=2,  `vm_ram`=4,  `vm_hdd`=80   WHERE `id`=22; -- Сайт taburetka
UPDATE `services` SET `vm_cores`=4,  `vm_ram`=16, `vm_hdd`=8000 WHERE `id`=26; -- Резервное копирование
UPDATE `services` SET `vm_cores`=8,  `vm_ram`=32, `vm_hdd`=600  WHERE `id`=12; -- Домен Taburetka.local
UPDATE `services` SET `vm_cores`=2,  `vm_ram`=8,  `vm_hdd`=200  WHERE `id`=20; -- Мониторинг инфраструктуры
