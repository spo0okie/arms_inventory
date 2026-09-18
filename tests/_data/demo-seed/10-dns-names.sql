-- Демо-данные, этап 10: DNS-имена и карта зоны (см. tests/_data/readme.md,
-- plans/access-chains.md, итерация 1).
--
-- Внешняя зона taburetka.ru: имена на белых адресах DMZ (MSK-WWW, MSK-NS) и на
-- шлюзах (msk-gw, chl-gw) — то, чего в инвентаризации по hostname узлов не видно.
-- Внутренняя зона taburetka.local: алиасы к существующим узлам (inventory →
-- msk-inventory, zabbix → CHL-ZABBIX) и имя на два адреса (proxy → оба прокси).
-- Одно имя без адресов (old-portal) — парковка.
--
-- Текстовое поле ip дублирует junction dns_names_in_ips (как techs.ip + ips_in_techs).

SET NAMES utf8mb4;

DELETE FROM `dns_names_in_ips` WHERE `dns_names_id`>=9000;
DELETE FROM `dns_names_history` WHERE `master_id`>=9000;
DELETE FROM `dns_names` WHERE `id`>=9000;
DELETE FROM `domains` WHERE `id`>=9000;

-- ---------------------------------------------------------------------------
-- Внешняя зона
-- ---------------------------------------------------------------------------
INSERT INTO `domains` (`id`,`name`,`fqdn`,`comment`) VALUES
	(9000,'TABURETKA-RU','taburetka.ru','Внешняя (публичная) DNS-зона: имена сайта, почты и VPN на белых адресах');

INSERT INTO `dns_names` (`id`,`domain_id`,`host`,`ip`,`comment`,`updated_at`,`updated_by`) VALUES
	(9000,9000,'',        '55.66.77.90',              'Apex зоны: сайт на MSK-WWW',                       '2026-09-10 09:00:00','admin'),
	(9001,9000,'www',     '55.66.77.90',              'Сайт (тот же адрес, что apex)',                    '2026-09-10 09:00:00','admin'),
	(9002,9000,'ns1',     '55.66.77.82',              'Внешний DNS на MSK-NS',                            '2026-09-10 09:00:00','admin'),
	(9003,9000,'mail',    '55.66.77.81',              'Почта: белый адрес шлюза msk-gw, NAT на внутренний почтовик','2026-09-10 09:05:00','admin'),
	(9004,9000,'vpn',     '55.66.77.81\n66.77.88.98', 'Клиентский VPN: оба шлюза (Москва и Челябинск)',    '2026-09-10 09:05:00','admin'),
	(9005,9000,'old-portal',NULL,                     'Старый портал выведен из эксплуатации, имя зарезервировано до конца года','2026-09-12 15:30:00','VeniaminLevchenko'),
	(9010,1,   'inventory','10.20.75.20',             'Алиас инвентаризации (hostname узла — msk-inventory)','2026-09-11 11:00:00','admin'),
	(9011,1,   'zabbix',  '10.50.75.4',               'Алиас мониторинга (hostname узла — CHL-ZABBIX)',    '2026-09-11 11:00:00','admin'),
	(9012,1,   'proxy',   '10.20.100.10\n10.50.100.10','Одно имя на два прокси (по площадкам)',            '2026-09-11 11:10:00','BorisBarinov');

INSERT INTO `dns_names_in_ips` (`id`,`dns_names_id`,`ips_id`) VALUES
	(9000,9000,50),
	(9001,9001,50),
	(9002,9002,51),
	(9003,9003,26),
	(9004,9004,26),(9005,9004,24),
	(9010,9010,18),
	(9011,9011,38),
	(9012,9012,39),(9013,9012,40);
