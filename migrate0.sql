SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;

START TRANSACTION;

-- Таблицы в которых есть пользователи:
-- arms
-- login_journal
-- materials
-- techs
-- users
-- users_in_groups

-- Убираем внешние ключи на пользователей. Почему? Ну хз. нигде нету больше. Надо или везде или нигде.
ALTER TABLE `techs` DROP FOREIGN KEY `techs_ibfk_1`;
ALTER TABLE `techs` DROP FOREIGN KEY `techs_ibfk_2`;

-- добавляем пару столбцоы: организация и табельный в ней
ALTER TABLE `users` ADD `employee_id` VARCHAR(16) NOT NULL COMMENT 'Табельный номер' AFTER `id`, ADD `org_id` INT NOT NULL COMMENT 'Организация' AFTER `employee_id`, ADD INDEX (`employee_id`), ADD INDEX (`org_id`);
ALTER TABLE `org_struct` ADD `org_id` INT NOT NULL COMMENT 'Организация' AFTER `id`, DROP PRIMARY KEY , ADD PRIMARY KEY(`id`,`org_id`);

UPDATE org_struct SET org_struct.org_id=1;
-- проставляем табельные номера
UPDATE `users` SET users.`employee_id`=`id`,users.org_id=1;

-- нумеруем ID
UPDATE `users` SET id = (SELECT @a:= @a + 1 FROM (SELECT @a:= 0) as tbl);

-- подменяем ID в таблице ARMS
UPDATE `arms` SET user_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`arms`.`user_id` limit 1);
UPDATE `arms` SET it_staff_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`arms`.`it_staff_id` limit 1);
UPDATE `arms` SET head_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`arms`.`head_id` limit 1);
UPDATE `arms` SET responsible_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`arms`.`responsible_id` limit 1);
-- журнал входов
UPDATE `login_journal` SET users_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`login_journal`.`users_id` limit 1);
-- группы пользователей
UPDATE `users_in_groups` SET users_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`users_in_groups`.`users_id` limit 1);
-- материалы
UPDATE `materials` SET it_staff_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`materials`.`it_staff_id` limit 1);
-- подменяем ID в таблице TECHS
UPDATE `techs` SET user_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`techs`.`user_id` limit 1);
UPDATE `techs` SET it_staff_id=(SELECT id FROM `users` WHERE `users`.`employee_id`=`techs`.`it_staff_id` limit 1);

-- SET FOREIGN_KEY_CHECKS=0;

-- arms
-- login_journal
-- materials
-- techs
-- users
-- users_in_groups

ALTER TABLE `users`   CHANGE `id` `id` INT(16) NOT NULL;
ALTER TABLE `arms`    CHANGE `user_id` `user_id` INT(16) NOT NULL;
ALTER TABLE `arms`    CHANGE `it_staff_id` `it_staff_id` INT(16) NOT NULL;
ALTER TABLE `arms`    CHANGE `head_id` `head_id` INT(16) NOT NULL;
ALTER TABLE `arms`    CHANGE `responsible_id` `responsible_id` INT(16) NOT NULL;
ALTER TABLE `login_journal` CHANGE `users_id` `users_id` INT(16) NOT NULL;
ALTER TABLE `users_in_groups` CHANGE `users_id` `users_id` INT(16) NOT NULL;
ALTER TABLE `materials` CHANGE `it_staff_id` `it_staff_id` INT(16) NOT NULL;
ALTER TABLE `techs`   CHANGE `it_staff_id` `it_staff_id` INT(16) NOT NULL;
ALTER TABLE `techs`   CHANGE `user_id` `user_id` INT(16) NOT NULL;

CREATE TABLE `arms`.`orgs` ( `id` INT NOT NULL AUTO_INCREMENT COMMENT 'id' , `name` VARCHAR(128) NOT NULL COMMENT 'Наименование' , `short` VARCHAR(16) NOT NULL COMMENT 'Короткое имя' , `comment` TEXT COMMENT 'Комментарии' , PRIMARY KEY (`id`)) ENGINE = InnoDB;

COMMIT;
SET FOREIGN_KEY_CHECKS=1;