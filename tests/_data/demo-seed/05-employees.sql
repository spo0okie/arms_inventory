-- Демо-данные, этап 5: сотрудники (см. tests/_data/readme.md).
--
-- Отсутствия всех типов из всех источников, руководители, дерево оргструктуры
-- с двумя корнями, заполненные кадровые поля и журнал изменений сотрудника.
--
-- Журнал (users_history) собирается ПОСЛЕ правки users и последним снимком
-- копирует текущую карточку: запись истории - это состояние ПОСЛЕ изменения,
-- а diff карточки считается против предыдущей записи (HistoryModel).
--
-- Фото сотрудников (scans.users_id) сюда намеренно не входят: файлы сканов
-- лежат в web/scans, который в .gitignore, - запись без файла даст битую
-- картинку. См. tests/_data/readme.md.

SET NAMES utf8mb4;

DELETE FROM `absences` WHERE `id`>=9000;
DELETE FROM `users_history` WHERE `id`>=9000;
DELETE FROM `org_struct` WHERE `id`>=9000;

-- ---------------------------------------------------------------------------
-- Оргструктура: дерево с двумя корнями (дирекция и «висящий» хозотдел)
-- ---------------------------------------------------------------------------
UPDATE `org_struct` SET `parent_id`=NULL, `parent_hr_id`=NULL WHERE `hr_id`='4';   -- Генеральная дирекция - корень
UPDATE `org_struct` SET `parent_id`=4, `parent_hr_id`='4' WHERE `hr_id` IN ('1','2','3');
UPDATE `org_struct` SET `parent_id`=NULL, `parent_hr_id`=NULL WHERE `hr_id`='5';   -- Хозяйственный отдел - второй корень

INSERT INTO `org_struct` (`id`,`hr_id`,`org_id`,`parent_hr_id`,`name`,`parent_id`) VALUES
(9001,'31',1,'3','Группа поддержки пользователей',3),
(9002,'32',1,'3','Группа сопровождения 1С',3),
(9003,'21',1,'2','Корпоративные продажи',2);

-- ---------------------------------------------------------------------------
-- Кадровые поля и руководители
-- ---------------------------------------------------------------------------
-- дирекция
UPDATE `users` SET `manager_id`=NULL, `work_phone`='1100', `employ_date`='12.01.2015',
	`uid`='S-1-5-21-1102', `updated_at`='2026-07-26 10:00:00', `updated_by`='sapsync' WHERE `id`=5;
-- ИТ: Безруков - руководитель, остальные под ним
UPDATE `users` SET `manager_id`=5, `work_phone`='3034', `employ_date`='03.02.2016',
	`uid`='S-1-5-21-1001', `notepad`='Ведёт инвентаризацию и доступы',
	`updated_at`='2026-08-12 08:30:00', `updated_by`='admin' WHERE `id`=1;
UPDATE `users` SET `manager_id`=1, `work_phone`='3041', `employ_date`='17.09.2019',
	`uid`='S-1-5-21-1002', `updated_at`='2026-08-12 08:32:00', `updated_by`='admin' WHERE `id`=9;
UPDATE `users` SET `manager_id`=1, `work_phone`='3044', `employ_date`='04.03.2021',
	`uid`='S-1-5-21-1003', `updated_at`='2026-08-12 08:34:00', `updated_by`='admin' WHERE `id`=10;
UPDATE `users` SET `manager_id`=1, `work_phone`='1136', `employ_date`='11.11.2021',
	`uid`='S-1-5-21-1004', `updated_at`='2026-08-12 08:36:00', `updated_by`='admin' WHERE `id`=6;
-- бухгалтерия: главбух и подчинённые
UPDATE `users` SET `manager_id`=5, `work_phone`='1122', `employ_date`='19.05.2017',
	`updated_at`='2026-07-26 10:05:00', `updated_by`='sapsync' WHERE `id`=4;
UPDATE `users` SET `manager_id`=4, `work_phone`='1123', `employ_date`='08.08.2020',
	`updated_at`='2026-07-26 10:06:00', `updated_by`='sapsync' WHERE `id`=2;
UPDATE `users` SET `manager_id`=4, `work_phone`='3021', `employ_date`='02.09.2021',
	`updated_at`='2026-07-26 10:07:00', `updated_by`='sapsync' WHERE `id`=12;
UPDATE `users` SET `manager_id`=4, `work_phone`='3024', `employ_date`='15.02.2022',
	`updated_at`='2026-07-26 10:08:00', `updated_by`='sapsync' WHERE `id`=13;
-- продажи: Питерский ведёт корпоративные продажи
UPDATE `users` SET `manager_id`=5, `work_phone`='3011', `employ_date`='01.06.2018',
	`updated_at`='2026-07-26 10:10:00', `updated_by`='sapsync' WHERE `id`=14;
UPDATE `users` SET `manager_id`=14, `work_phone`='1201-3', `employ_date`='14.03.2020',
	`updated_at`='2026-08-03 07:40:00', `updated_by`='sapsync' WHERE `id`=3;
UPDATE `users` SET `manager_id`=14, `work_phone`='1202', `employ_date`='21.10.2021',
	`updated_at`='2026-07-26 10:12:00', `updated_by`='sapsync' WHERE `id`=7;
UPDATE `users` SET `manager_id`=14, `work_phone`='3014', `employ_date`='19.08.2026',
	`updated_at`='2026-08-19 08:05:00', `updated_by`='sapsync' WHERE `id`=15;
-- уволенная: дата увольнения приехала из SAP
UPDATE `users` SET `manager_id`=14, `resign_date`='30.04.2026',
	`updated_at`='2026-04-30 06:00:00', `updated_by`='sapsync' WHERE `id`=11;
-- офис-менеджер
UPDATE `users` SET `manager_id`=5, `work_phone`='1000', `employ_date`='05.04.2019',
	`updated_at`='2026-07-26 10:14:00', `updated_by`='sapsync' WHERE `id`=8;
-- служебная учётка синхронизацией не трогается
UPDATE `users` SET `nosync`=1, `notepad`='Учётка для демонстрационного входа, синхронизацией не трогается',
	`updated_at`='2026-07-26 10:20:00', `updated_by`='admin' WHERE `id`=17;

-- ---------------------------------------------------------------------------
-- Отсутствия: все девять типов, три источника, прошлые/текущие/будущие
-- ---------------------------------------------------------------------------
INSERT INTO `absences` (`id`,`user_id`,`type`,`date_from`,`date_to`,`comment`,`source`,`external_id`,
	`updated_at`,`updated_by`) VALUES
(9001,2,'VACATION','2026-08-24','2026-09-07','Основной отпуск','sap','SAP-2026-0231','2026-08-10 05:00:00','sapsync'),
(9002,3,'ASSIGNMENT','2026-08-27','2026-08-29','Челябинск, приёмка офиса','manual',NULL,'2026-08-25 12:30:00','admin'),
(9003,4,'LEAVESICK','2026-08-18','2026-08-22',NULL,'c1','1C-BL-4412','2026-08-24 04:15:00','c1sync'),
(9004,6,'VACATION_PLAN','2026-10-05','2026-10-18','График отпусков на IV квартал','sap','SAP-2026-0410','2026-08-01 05:00:00','sapsync'),
(9005,7,'LEAVEMATERNITY','2026-03-02','2027-07-20',NULL,'sap','SAP-2026-0088','2026-03-02 05:00:00','sapsync'),
(9006,9,'LEAVEUNPAYED','2026-09-01','2026-09-01','Отгул за работу в выходной','manual',NULL,'2026-08-26 09:10:00','admin'),
(9007,10,'UNKNOWN','2026-07-13','2026-07-13','Отсутствовал без объяснения, разобрано','c1','1C-PR-0771','2026-07-14 04:20:00','c1sync'),
(9008,12,'OTHER','2026-09-14','2026-09-18','Обучение 1С:Бухгалтерия','manual',NULL,'2026-08-20 11:00:00','admin'),
(9009,13,'PERSONAL','2026-08-31','2026-08-31','Удалённый день','manual',NULL,'2026-08-27 15:40:00','admin'),
(9010,14,'ASSIGNMENT','2026-09-08','2026-09-12','Выставка, Екатеринбург','sap','SAP-2026-0255','2026-08-22 05:00:00','sapsync'),
(9011,15,'VACATION','2026-06-01','2026-06-14',NULL,'sap','SAP-2026-0177','2026-05-20 05:00:00','sapsync'),
(9012,5,'ASSIGNMENT','2026-08-28','2026-08-28','Переговоры у контрагента','manual',NULL,'2026-08-27 16:00:00','admin');

-- ---------------------------------------------------------------------------
-- Журнал изменений сотрудника: цепочка снимков, последний = текущая карточка
--
-- Снимки собираются из самой карточки (INSERT ... SELECT) с подменой полей,
-- которые тогда были другими: так журнал не расходится с текущими данными.
-- ---------------------------------------------------------------------------

-- Садовский (3): переведён в менеджеры, потом получил руководителя и новый номер
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9001,`id`,`employee_id`,`org_id`,`Orgeh`,'Специалист отдела продаж',`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,NULL,`Bday`,NULL,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,'2026-02-10 05:00:00','sapsync',
	NULL,'Doljnost' FROM `users` WHERE `id`=3;
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9002,`id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,NULL,`Bday`,NULL,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,'2026-05-14 05:00:00','sapsync',
	'Перевод на позицию менеджера','Doljnost' FROM `users` WHERE `id`=3;
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9003,`id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	NULL,'work_phone,manager_id' FROM `users` WHERE `id`=3;

-- Зимин (6): вырос из техподдержки в системные администраторы, получил учётку AD
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9004,`id`,`employee_id`,`org_id`,`Orgeh`,'Специалист техподдержки',`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,NULL,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,NULL,`ips`,'2026-03-01 05:00:00','sapsync',
	NULL,'Doljnost' FROM `users` WHERE `id`=6;
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9005,`id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,NULL,`ips`,'2026-06-20 05:00:00','sapsync',
	'Повышение по итогам аттестации','Doljnost,manager_id' FROM `users` WHERE `id`=6;
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9006,`id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	NULL,'uid' FROM `users` WHERE `id`=6;

-- Тарская (11): увольнение приехало из SAP
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9007,`id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	0,`Login`,`Email`,'1204',`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,NULL,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,'2026-01-15 05:00:00','sapsync',
	NULL,'Phone' FROM `users` WHERE `id`=11;
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9008,`id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	'Увольнение по собственному желанию','Uvolen,Phone,resign_date' FROM `users` WHERE `id`=11;

-- Кириллов (15): заведён в ARMS, потом создана учётная запись AD
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9009,`id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,NULL,NULL,`Phone`,`Mobile`,NULL,`Bday`,NULL,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,'2026-08-18 05:00:00','sapsync',
	'Приём на работу','Ename,Doljnost,employ_date' FROM `users` WHERE `id`=15;
INSERT INTO `users_history` (`id`,`master_id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	`updated_comment`,`changed_attributes`)
SELECT 9010,`id`,`employee_id`,`org_id`,`Orgeh`,`Doljnost`,`Ename`,`Persg`,
	`Uvolen`,`Login`,`Email`,`Phone`,`Mobile`,`work_phone`,`Bday`,`manager_id`,`employ_date`,`resign_date`,
	`nosync`,`notepad`,`private_phone`,`external_links`,`uid`,`ips`,`updated_at`,`updated_by`,
	'Заведена учётная запись в AD','Login,Email,work_phone,manager_id' FROM `users` WHERE `id`=15;
