-- Демо-данные, этап 12: транзитные межсервисные связи (см. tests/_data/readme.md,
-- docs/dev/access-chains.md, §4).
--
-- Связь «сервис А ходит в сервис В через посредника Б» = две записи доступа
-- (А→Б, Б→В) и указатель «следующий хоп» между ними (aces_next_aces):
--   Инвентаризация  —HTTPS TCP 44344→ Контроль доступа в интернет (прокси) —HTTPS TCP 443→ Кластер 1С
--   Сайт taburetka  —HTTPS TCP 44345→ Контроль доступа в интернет (прокси) —HTTPS TCP 443→ Кластер 1С
-- Два входящих хопа сходятся в один исходящий: у конечного сервиса видно, кто
-- приходит через прокси, у прокси — какие соединения транзитные.
--
-- Диапазон id 9200+: ниже — этапы 07 (доступы) и 11 (пробросы).

SET NAMES utf8mb4;

DELETE FROM `aces_next_aces` WHERE `id`>=9200;
DELETE FROM `access_in_aces` WHERE `id`>=9200;
DELETE FROM `services_in_aces` WHERE `id`>=9200;
DELETE FROM `aces` WHERE `id`>=9200;
DELETE FROM `acls` WHERE `id`>=9200;

INSERT INTO `acls` (`id`,`schedules_id`,`services_id`,`ips_id`,`comps_id`,`techs_id`,`networks_id`,
	`comment`,`notepad`,`links`,`updated_at`,`updated_by`) VALUES
(9200,NULL,19,NULL,NULL,NULL,NULL,NULL,'Прокси контролирует на сетевом уровне, кто куда ходит',NULL,'2026-09-16 09:00:00','admin'),
(9201,NULL,18,NULL,NULL,NULL,NULL,NULL,'Доступ к веб-сервисам 1С только через прокси',NULL,'2026-09-16 09:00:00','admin');

INSERT INTO `aces` (`id`,`acls_id`,`ips`,`comment`,`notepad`,`name`,`updated_at`,`updated_by`) VALUES
(9200,9200,'','','','Забирает кадровые данные из 1С (через прокси)','2026-09-16 09:05:00','admin'),
(9201,9200,'','','','Публикует прайс из 1С (через прокси)','2026-09-16 09:06:00','admin'),
(9202,9201,'','','','Проброс запросов к веб-сервисам 1С','2026-09-16 09:10:00','admin');

INSERT INTO `services_in_aces` (`id`,`aces_id`,`services_id`) VALUES
(9200,9200,24),
(9201,9201,22),
(9202,9202,19);

INSERT INTO `access_in_aces` (`id`,`access_types_id`,`aces_id`,`ip_params`) VALUES
(9200,9001,9200,'TCP 44344'),
(9201,9001,9201,'TCP 44345'),
(9202,9001,9202,'TCP 443');

INSERT INTO `aces_next_aces` (`id`,`aces_id`,`next_aces_id`) VALUES
(9200,9200,9202),
(9201,9201,9202);
