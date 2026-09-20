-- Демо-данные, этап 11: пробросы (NAT) через записи доступа (см. tests/_data/readme.md,
-- docs/dev/access-chains.md, §3).
--
-- Проброс документируется обычным ACE с признаком aces.is_forward: субъект — адрес
-- входа (белый IP), ресурс ACL — узел назначения или его адрес, параметры — «порт
-- входа -> порт назначения». Типы доступа — обычные (HTTPS, Ovpn): признак проброса
-- живёт на записи (хопе), а не на типе, поэтому копии типов («HTTPS forward») не нужны.
-- Вместе с DNS-именами этапа 10 даёт цепочку «имя → белый адрес → проброс → узел»:
--   mail.taburetka.ru / vpn.taburetka.ru → 55.66.77.81 (msk-gw) → MSK-PROXY :8443
--   vpn.taburetka.ru → 55.66.77.81 → MSK-OVPN, 66.77.88.98 (chl-gw) → chl-ovpn
--
-- Диапазон id 9100+: ниже — записи этапа 07 (доступы).

SET NAMES utf8mb4;

DELETE FROM `access_in_aces` WHERE `id`>=9100 AND `id`<9200;
DELETE FROM `ips_in_aces` WHERE `id`>=9100 AND `id`<9200;
DELETE FROM `aces` WHERE `id`>=9100 AND `id`<9200;
DELETE FROM `acls` WHERE `id`>=9100 AND `id`<9200;
-- тип-копия «Проброс порта» из первой версии этапа больше не нужен
DELETE FROM `access_types` WHERE `id`=9100;

-- ---------------------------------------------------------------------------
-- Пробросы: ресурс — узел (ОС) либо его адрес
-- ---------------------------------------------------------------------------
INSERT INTO `acls` (`id`,`schedules_id`,`services_id`,`ips_id`,`comps_id`,`techs_id`,`networks_id`,
	`comment`,`notepad`,`links`,`updated_at`,`updated_by`) VALUES
(9100,NULL,NULL,NULL,34,NULL,NULL,NULL,'Публикация наружу через шлюз msk-gw',NULL,'2026-09-15 10:00:00','admin'),
(9101,NULL,NULL,30,NULL,NULL,NULL,NULL,'Клиентский VPN, площадка Москва',NULL,'2026-09-15 10:05:00','admin'),
(9102,NULL,NULL,31,NULL,NULL,NULL,NULL,'Клиентский VPN, площадка Челябинск',NULL,'2026-09-15 10:05:00','admin');

INSERT INTO `aces` (`id`,`acls_id`,`ips`,`comment`,`notepad`,`name`,`is_forward`,`updated_at`,`updated_by`) VALUES
(9100,9100,'55.66.77.81','HTTPS снаружи на реверс-прокси','','Проброс 443 на MSK-PROXY',1,'2026-09-15 10:01:00','admin'),
(9101,9101,'55.66.77.81','OpenVPN снаружи','','Проброс VPN на MSK-OVPN',1,'2026-09-15 10:06:00','admin'),
(9102,9102,'66.77.88.98','OpenVPN снаружи','','Проброс VPN на chl-ovpn',1,'2026-09-15 10:06:00','VeniaminLevchenko');

INSERT INTO `ips_in_aces` (`id`,`ips_id`,`aces_id`) VALUES
(9100,26,9100),
(9101,26,9101),
(9102,24,9102);

-- обычные типы доступа: HTTPS (9001) и Ovpn (4)
INSERT INTO `access_in_aces` (`id`,`access_types_id`,`aces_id`,`ip_params`) VALUES
(9100,9001,9100,'TCP 443->8443'),
(9101,4,   9101,'UDP 1194'),
(9102,4,   9102,'UDP 1194');
