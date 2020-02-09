<?php

use yii\db\Migration;

/**
 * Class m191124_100710_update0
 */
class m190101_100000_update0 extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	    $sql=<<<SQL
set names utf8mb4;

ALTER TABLE `techs` DROP FOREIGN KEY `techs_ibfk_1`;
ALTER TABLE `techs` DROP FOREIGN KEY `techs_ibfk_2`;

-- changed table `domains`
ALTER TABLE `domains`
  CHANGE COLUMN `name` `name` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `id`,
  CHANGE COLUMN `fqdn` `fqdn` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `name`,
  CHANGE COLUMN `comment` `comment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `fqdn`,
  DEFAULT CHARSET=utf8mb4;

-- changed table `lic_types`
ALTER TABLE `lic_types`
  CHANGE COLUMN `name` `name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `id`,
  CHANGE COLUMN `descr` `descr` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `name`,
  CHANGE COLUMN `comment` `comment` mediumtext COLLATE utf8mb4_unicode_ci AFTER `descr`,
  DEFAULT CHARSET=utf8mb4;

-- changed table `org_struct`
ALTER TABLE `org_struct`
  CHANGE COLUMN `id` `id` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' FIRST,
  CHANGE COLUMN `pup` `pup` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `id`,
  CHANGE COLUMN `name` `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `pup`,
  DEFAULT CHARSET=utf8mb4;

-- changed table `partners`
ALTER TABLE `partners`
  CHANGE COLUMN `inn` `inn` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `id`,
  CHANGE COLUMN `kpp` `kpp` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `inn`,
  CHANGE COLUMN `uname` `uname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `kpp`,
  CHANGE COLUMN `bname` `bname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `uname`,
  CHANGE COLUMN `coment` `coment` text COLLATE utf8mb4_unicode_ci AFTER `bname`,
  DEFAULT CHARSET=utf8mb4;

-- changed table `scans`
ALTER TABLE `scans`
  CHANGE COLUMN `format` `format` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `contracts_id`,
  CHANGE COLUMN `file` `file` text COLLATE utf8mb4_unicode_ci AFTER `format`,
  DEFAULT CHARSET=utf8mb4;

-- changed table `soft_hits`
ALTER TABLE `soft_hits`
  CHANGE COLUMN `hits` `hits` mediumtext COLLATE utf8mb4_unicode_ci AFTER `comp_id`,
  DEFAULT CHARSET=utf8mb4;

-- changed table `soft_in_lists`
ALTER TABLE `soft_in_lists`
  DEFAULT CHARSET=utf8mb4;

-- changed table `soft_lists`
ALTER TABLE `soft_lists`
  CHANGE COLUMN `name` `name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `id`,
  CHANGE COLUMN `descr` `descr` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `name`,
  CHANGE COLUMN `comment` `comment` mediumtext COLLATE utf8mb4_unicode_ci AFTER `descr`,
  DEFAULT CHARSET=utf8mb4;

-- changed table `tech_states`
ALTER TABLE `tech_states`
  CHANGE COLUMN `code` `code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `id`,
  CHANGE COLUMN `name` `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `code`,
  CHANGE COLUMN `descr` `descr` text COLLATE utf8mb4_unicode_ci AFTER `name`,
  DEFAULT CHARSET=utf8mb4;

-- new table `hw_ignore`
ALTER TABLE `hw_ignore`
  CHANGE COLUMN `fingerprint` `fingerprint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  CHANGE COLUMN `comment` `comment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  DEFAULT CHARSET=utf8mb4;

-- new table `manufacturers`
ALTER TABLE `manufacturers`
  CHANGE COLUMN `name`  `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  CHANGE COLUMN `full_name` `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  CHANGE COLUMN `comment` `comment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  DEFAULT CHARSET=utf8mb4 COMMENT='Производители ПО и железа';

-- new table `soft`
ALTER TABLE `soft`
  CHANGE COLUMN `manufacturers_id` `manufacturers_id` int(11) NOT NULL,
  CHANGE COLUMN `descr` `descr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  CHANGE COLUMN `comment` `comment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  CHANGE COLUMN `items` `items` mediumtext COLLATE utf8mb4_unicode_ci,
  CHANGE COLUMN `additional` `additional` mediumtext COLLATE utf8mb4_unicode_ci,
  CHANGE COLUMN `created_at` `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  DEFAULT CHARSET=utf8mb4;

-- new table `tech_types`
ALTER TABLE `tech_types`
  ADD COLUMN `comment_name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `comment`,
  ADD COLUMN `comment_hint` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `comment_name`,
  DEFAULT CHARSET=utf8mb4;

-- new table `contracts_in_materials`
CREATE TABLE `contracts_in_materials` (
  `id` int(11) NOT NULL,
  `contracts_id` int(11) NOT NULL,
  `materials_id` int(11) NOT NULL
) ENGINE=InnoDB ;

# Disable Foreign Keys Check
SET FOREIGN_KEY_CHECKS = 1;

SQL;
	    $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190101_100000_update0 cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191124_100710_update0 cannot be reverted.\n";

        return false;
    }
    */
}
