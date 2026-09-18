-- Демо-данные, этап 13: сегменты в списках доступа и матрица межсегментного доступа
-- (см. tests/_data/readme.md, plans/access-chains.md итерация 4, issue #220).
--
-- Сегмент как ресурс ACL (acls.segments_id) — доступ ко всем сетям и сервисам сегмента;
-- сегмент как субъект ACE (segments_in_aces) — доступ из всех сетей/сервисов сегмента.
-- Матрица /segments/matrix показывает только пары «сегмент → сегмент»:
--   Открытый   → Сеть серверов (HTTPS 443), Внешний (HTTPS 80,443)
--   IT сегмент → Сеть серверов (RDP, HTTPS), Сегмент управления (HTTPS)
-- Запись 9302 (сервис «Мониторинг» → сегмент «Сеть серверов») матрицей игнорируется:
-- субъект не сегмент; она видна на странице сегмента во входящих доступах.
--
-- Диапазон id 9300+: ниже — этапы 07, 11, 12.

SET NAMES utf8mb4;

DELETE FROM `access_in_aces` WHERE `id`>=9300;
DELETE FROM `services_in_aces` WHERE `id`>=9300;
DELETE FROM `segments_in_aces` WHERE `id`>=9300;
DELETE FROM `aces` WHERE `id`>=9300;
DELETE FROM `acls` WHERE `id`>=9300;

INSERT INTO `acls` (`id`,`schedules_id`,`services_id`,`ips_id`,`comps_id`,`techs_id`,`networks_id`,`segments_id`,
	`comment`,`notepad`,`links`,`updated_at`,`updated_by`) VALUES
(9300,NULL,NULL,NULL,NULL,NULL,NULL,6, NULL,'Базовая политика: кто ходит в серверный сегмент',NULL,'2026-09-17 09:00:00','admin'),
(9301,NULL,NULL,NULL,NULL,NULL,NULL,9, NULL,'Сегмент управления доступен только ИТ',NULL,'2026-09-17 09:05:00','admin'),
(9302,NULL,NULL,NULL,NULL,NULL,NULL,10,NULL,'Выход в интернет',NULL,'2026-09-17 09:10:00','admin');

INSERT INTO `aces` (`id`,`acls_id`,`ips`,`comment`,`notepad`,`name`,`updated_at`,`updated_by`) VALUES
(9300,9300,'','','','Пользователи к серверным веб-сервисам','2026-09-17 09:01:00','admin'),
(9301,9300,'','','','Администраторы к серверам','2026-09-17 09:02:00','admin'),
(9302,9300,'','','','Мониторинг опрашивает серверный сегмент','2026-09-17 09:03:00','DaniilZimin'),
(9303,9301,'','','','Администраторы к интерфейсам управления','2026-09-17 09:06:00','admin'),
(9304,9302,'','','','Пользователи в интернет (веб)','2026-09-17 09:11:00','admin');

INSERT INTO `segments_in_aces` (`id`,`aces_id`,`segments_id`) VALUES
(9300,9300,1),
(9301,9301,8),
(9303,9303,8),
(9304,9304,1);

INSERT INTO `services_in_aces` (`id`,`aces_id`,`services_id`) VALUES
(9302,9302,20);

INSERT INTO `access_in_aces` (`id`,`access_types_id`,`aces_id`,`ip_params`) VALUES
(9300,9001,9300,'TCP 443'),
(9301,7,   9301,'TCP 3389'),
(9302,9001,9301,'TCP 443'),
(9303,9001,9302,'TCP 443'),
(9304,9001,9303,'TCP 443'),
(9305,9001,9304,'TCP 80,443');

-- ---------------------------------------------------------------------------
-- Сети разложены по сегментам: без этого сегмент-ресурс не во что разворачивать
-- (узлы доступа сегмента — его подсети и узлы его сервисов), а вкладка сети
-- «Вх. соединения» не покажет доступы к её сегменту
-- ---------------------------------------------------------------------------
UPDATE `networks` SET `segments_id`=6  WHERE `id` IN (1,2);    -- серверные сети
UPDATE `networks` SET `segments_id`=8  WHERE `id`=3;           -- ИТ
UPDATE `networks` SET `segments_id`=9  WHERE `id` IN (4,12);   -- управление
UPDATE `networks` SET `segments_id`=1  WHERE `id` IN (5,13);   -- открытые
UPDATE `networks` SET `segments_id`=3  WHERE `id` IN (6,14);   -- клиентский VPN
UPDATE `networks` SET `segments_id`=2  WHERE `id` IN (7,15);   -- принтеры
UPDATE `networks` SET `segments_id`=7  WHERE `id` IN (8,16);   -- VoIP
UPDATE `networks` SET `segments_id`=4  WHERE `id`=9;           -- закрытый
UPDATE `networks` SET `segments_id`=5  WHERE `id`=10;          -- внешний DMZ
UPDATE `networks` SET `segments_id`=10 WHERE `id` IN (11,17);  -- внешние (провайдеры)
