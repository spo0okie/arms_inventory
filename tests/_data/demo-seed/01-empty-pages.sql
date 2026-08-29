-- Демо-данные, этап 1: разделы, которые без записей открываются пустыми.
-- См. plans/demo-data.md. Все строки этапа живут в демо-диапазоне id>=9000.

SET NAMES utf8mb4;

DELETE FROM `notifications` WHERE `id`>=9000;
DELETE FROM `integrations_log` WHERE `id`>=9000;
DELETE FROM `default_access_in_services` WHERE `id`>=9000;
DELETE FROM `access_types_hierarchy` WHERE `id`>=9000;
DELETE FROM `admins_in_comps` WHERE `id`>=9000;
DELETE FROM `users_in_svc_infrastructure` WHERE `id`>=9000;
DELETE FROM `users_in_contracts` WHERE `id`>=9000;

-- ---------------------------------------------------------------------------
-- Очередь оповещений (outbox): отправленные, ожидающие, застрявшие
-- ---------------------------------------------------------------------------
INSERT INTO `notifications`
	(`id`,`user_id`,`event_key`,`subject`,`body`,`created_at`,`sent_at`,`attempts`,`last_error`) VALUES
(9001,1,'contracts:7:state_id',
	'Документ «Счет № 213 - Серверные шкафы в Чел и Мск»: Состояние — Оплачено 100%',
	'<p>Документ <a href="/contracts/view?id=7">Счет № 213 - Серверные шкафы в Чел и Мск</a></p><p><strong>Состояние:</strong> Ожидает оплаты &rarr; Оплачено 100%</p>',
	'2026-08-19 07:12:00','2026-08-19 07:15:04',0,NULL),
(9002,9,'contracts:7:state_id',
	'Документ «Счет № 213 - Серверные шкафы в Чел и Мск»: Состояние — Оплачено 100%',
	'<p>Документ <a href="/contracts/view?id=7">Счет № 213 - Серверные шкафы в Чел и Мск</a></p><p><strong>Состояние:</strong> Ожидает оплаты &rarr; Оплачено 100%</p>',
	'2026-08-19 07:12:00','2026-08-19 07:15:05',0,NULL),
(9003,6,'contracts:3:state_id',
	'Документ «Счет №456 Серверное оборудование в МСК»: Состояние — Оплачено 100%',
	'<p>Документ <a href="/contracts/view?id=3">Счет №456 Серверное оборудование в МСК</a></p><p><strong>Состояние:</strong> Согласовано &rarr; Оплачено 100%</p>',
	'2026-08-24 11:40:00',NULL,0,NULL),
(9004,1,'watch:contracts_paywait:4',
	'Документ «Счет№ 709 Оборудование в офисы» ждёт оплаты больше 30 дней',
	'<p>Документ <a href="/contracts/view?id=4">Счет№ 709 Оборудование в офисы</a> в состоянии «Ожидает оплаты» с 21.12.2021.</p>',
	'2026-08-26 05:00:00',NULL,3,'SMTP: Connection could not be established with host localhost: stream_socket_client(): connection refused'),
(9005,10,'watch:contracts_paywait:4',
	'Документ «Счет№ 709 Оборудование в офисы» ждёт оплаты больше 30 дней',
	'<p>Документ <a href="/contracts/view?id=4">Счет№ 709 Оборудование в офисы</a> в состоянии «Ожидает оплаты» с 21.12.2021.</p>',
	'2026-08-26 05:00:00','2026-08-26 05:00:12',1,NULL),
(9006,5,'contracts:7:total',
	'Документ «Счет № 213 - Серверные шкафы в Чел и Мск»: Сумма — 39 611,00 руб.',
	'<p>Документ <a href="/contracts/view?id=7">Счет № 213 - Серверные шкафы в Чел и Мск</a></p><p><strong>Сумма:</strong> <i>(пусто)</i> &rarr; 39 611,00 руб.</p>',
	'2026-07-30 09:21:00','2026-07-30 09:25:00',0,NULL);

-- ---------------------------------------------------------------------------
-- Журнал интеграций: успех, отказ, вложенный вызов (AD -> SMS), зависшая запись
-- ---------------------------------------------------------------------------
INSERT INTO `integrations_log`
	(`id`,`created_at`,`users_id`,`provider`,`action`,`class`,`object_id`,`parent_id`,`ext_login`,`params`,`result`,`message`) VALUES
(9001,'2026-08-18 06:31:12',1,'ad','reset-password','app\\models\\Users',6,NULL,'TABURETKA\\admin',
	'{"length":12,"mustChange":true}','ok','Пароль учётной записи DaniilZimin изменён, SMS доставлено'),
(9002,'2026-08-18 06:31:14',1,'sms','send','app\\models\\Users',6,9001,NULL,
	'{"phone":"+7 (952) 073-14-59"}','ok','Отправлено'),
(9003,'2026-08-19 08:04:50',1,'ad','create-account','app\\models\\Users',15,NULL,'TABURETKA\\admin',
	'{"ou":"OU=Users,OU=Taburetka,DC=taburetka,DC=local","groups":["sales","vpn-users"]}','ok',
	'Учётная запись ApollonKirillov создана'),
(9004,'2026-08-19 08:04:53',1,'sms','send','app\\models\\Users',15,9003,NULL,
	'{"phone":"+7 (961) 020-15-86"}','ok','Отправлено'),
(9005,'2026-08-19 12:10:31',9,'ad','restore-account','app\\models\\Users',11,NULL,'TABURETKA\\VeniaminLevchenko',
	'{"login":"ElinaTarskaya"}','error','Учётная запись не найдена в OU уволенных'),
(9006,'2026-08-20 10:02:17',10,'ad','reset-password','app\\models\\Users',13,NULL,'TABURETKA\\BorisBarinov',
	'{"length":10,"mustChange":true}','error','Отказ AD: недостаточно прав на сброс пароля (Reset Password)'),
(9007,'2026-08-21 14:45:03',1,'sms','send',NULL,NULL,NULL,NULL,
	'{"phone":"+7 (912) 137-28-86"}','ok','Отправлено'),
(9008,'2026-08-25 09:15:44',6,'sms','send',NULL,NULL,NULL,NULL,
	'{"phone":"+7 (998) 558-41-00"}','error','Таймаут запроса к шлюзу (15 с)'),
(9009,'2026-08-26 07:58:20',1,'ad','create-account','app\\models\\Users',14,NULL,'TABURETKA\\admin',
	'{"ou":"OU=Users,OU=Taburetka,DC=taburetka,DC=local"}','error',
	'Учётная запись с таким sAMAccountName уже существует'),
(9010,'2026-08-27 16:20:00',1,'ad','reset-password','app\\models\\Users',12,NULL,'TABURETKA\\admin',
	NULL,'run',NULL);

-- ---------------------------------------------------------------------------
-- Дефолтные типы доступа у сервиса (issue #204)
-- ---------------------------------------------------------------------------
INSERT INTO `default_access_in_services` (`id`,`services_id`,`access_types_id`,`ip_params`) VALUES
(9001,17,4,'UDP 1194'),
(9002,18,6,'TCP 3389'),
(9003,18,4,'UDP 1194'),
(9004,22,2,'TCP 80,443'),
(9005,11,6,'UDP 5060,20000-20100'),
(9006,19,2,'TCP 3128');

-- ---------------------------------------------------------------------------
-- Иерархия типов доступа: Полный включает Запись и Ovpn, Запись включает Чтение
-- ---------------------------------------------------------------------------
INSERT INTO `access_types_hierarchy` (`id`,`child_id`,`parent_id`) VALUES
(9001,3,6),
(9002,4,6),
(9003,2,3);

-- ---------------------------------------------------------------------------
-- Администраторы ПК
-- ---------------------------------------------------------------------------
INSERT INTO `admins_in_comps` (`id`,`comps_id`,`users_id`) VALUES
(9001,39,6),
(9002,40,1),
(9003,17,9),
(9004,18,9),
(9005,19,9),
(9006,5,10),
(9007,6,10),
(9008,41,1);

-- ---------------------------------------------------------------------------
-- Обслуживающий персонал сервисов
-- ---------------------------------------------------------------------------
INSERT INTO `users_in_svc_infrastructure` (`id`,`services_id`,`users_id`) VALUES
(9001,8,9),
(9002,9,9),
(9003,10,9),
(9004,15,6),
(9005,16,6),
(9006,17,6),
(9007,26,1),
(9008,20,10);

UPDATE `services` SET `infrastructure_user_id`=9  WHERE `id` IN (8,9,10);
UPDATE `services` SET `infrastructure_user_id`=6  WHERE `id` IN (15,16,17);
UPDATE `services` SET `infrastructure_user_id`=1  WHERE `id`=26;
UPDATE `services` SET `infrastructure_user_id`=10 WHERE `id`=20;

-- ---------------------------------------------------------------------------
-- Сотрудники в договорах (кто фигурирует в документе)
-- ---------------------------------------------------------------------------
INSERT INTO `users_in_contracts` (`id`,`users_id`,`contracts_id`) VALUES
(9001,1,3),
(9002,9,3),
(9003,6,4),
(9004,1,7),
(9005,14,1),
(9006,14,2);
