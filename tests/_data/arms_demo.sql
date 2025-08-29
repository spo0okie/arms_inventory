-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: localhost    Database: arms_test_crud
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `arms_test_crud`
--

/*!40000 DROP DATABASE IF EXISTS `arms_test_crud`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `arms_test_crud` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `arms_test_crud`;

--
-- Table structure for table `access_in_aces`
--

DROP TABLE IF EXISTS `access_in_aces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_in_aces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `access_types_id` int NOT NULL,
  `aces_id` int NOT NULL,
  `ip_params` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-access_in_aces_ace_id` (`aces_id`),
  KEY `idx-access_in_aces_access_id` (`access_types_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_in_aces`
--

LOCK TABLES `access_in_aces` WRITE;
/*!40000 ALTER TABLE `access_in_aces` DISABLE KEYS */;
INSERT INTO `access_in_aces` VALUES (1,4,1,NULL),(4,4,3,NULL),(10,4,4,NULL);
/*!40000 ALTER TABLE `access_in_aces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `access_types`
--

DROP TABLE IF EXISTS `access_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_ip` tinyint(1) DEFAULT '0',
  `is_phone` tinyint(1) DEFAULT '0',
  `is_vpn` tinyint(1) DEFAULT '0',
  `is_app` tinyint(1) DEFAULT '0',
  `ip_params_def` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_types`
--

LOCK TABLES `access_types` WRITE;
/*!40000 ALTER TABLE `access_types` DISABLE KEYS */;
INSERT INTO `access_types` VALUES (2,'read','Чтение',NULL,NULL,0,0,0,0,NULL),(3,'write','Запись',NULL,NULL,0,0,0,0,NULL),(4,'vpn','Ovpn','OpenVPN','',1,0,1,0,NULL),(6,'full','Полный','','',0,0,0,0,''),(7,'full','Полный','','',0,0,0,0,'');
/*!40000 ALTER TABLE `access_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `access_types_hierarchy`
--

DROP TABLE IF EXISTS `access_types_hierarchy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_types_hierarchy` (
  `id` int NOT NULL AUTO_INCREMENT,
  `child_id` int NOT NULL,
  `parent_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `access_types_hiera_parents2children` (`parent_id`,`child_id`),
  KEY `access_types_hiera_children2parents` (`child_id`,`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_types_hierarchy`
--

LOCK TABLES `access_types_hierarchy` WRITE;
/*!40000 ALTER TABLE `access_types_hierarchy` DISABLE KEYS */;
/*!40000 ALTER TABLE `access_types_hierarchy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aces`
--

DROP TABLE IF EXISTS `aces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `acls_id` int DEFAULT NULL,
  `ips` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-aces_acl_id` (`acls_id`),
  KEY `idx-aces-updated_at` (`updated_at`),
  KEY `idx-aces-updated_by` (`updated_by`),
  KEY `idx-aces-name` (`name`),
  CONSTRAINT `fk-aces-acl` FOREIGN KEY (`acls_id`) REFERENCES `acls` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aces`
--

LOCK TABLES `aces` WRITE;
/*!40000 ALTER TABLE `aces` DISABLE KEYS */;
INSERT INTO `aces` VALUES (1,1,'','','',NULL,NULL,NULL),(3,3,'','','','2024-03-18 15:39:06','admin',NULL),(4,4,'','','','2024-03-18 15:51:21','admin',NULL);
/*!40000 ALTER TABLE `aces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aces_history`
--

DROP TABLE IF EXISTS `aces_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aces_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `acls_id` int DEFAULT NULL,
  `users_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comps_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `access_types_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ips` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `services_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `networks_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aces_history-master_id` (`master_id`),
  KEY `aces_history-updated_at` (`updated_at`),
  KEY `aces_history-updated_by` (`updated_by`),
  KEY `idx-aces_history-name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aces_history`
--

LOCK TABLES `aces_history` WRITE;
/*!40000 ALTER TABLE `aces_history` DISABLE KEYS */;
INSERT INTO `aces_history` VALUES (1,3,'2024-03-18 15:39:06','admin',NULL,'acls_id,users_ids,access_types_ids',NULL,NULL,3,'1',NULL,'1,4',NULL,NULL,NULL,NULL),(2,4,'2024-03-18 15:50:21','admin',NULL,'acls_id,users_ids,access_types_ids',NULL,NULL,4,'6',NULL,'1,4',NULL,NULL,NULL,NULL),(3,4,'2024-03-18 15:50:52','admin',NULL,'notepad',NULL,'Для выполнения должностных обязанностей удаленно',4,'6',NULL,'1,4',NULL,NULL,NULL,NULL),(4,4,'2024-03-18 15:51:21','admin',NULL,'notepad',NULL,NULL,4,'6',NULL,'1,4',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `aces_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acls`
--

DROP TABLE IF EXISTS `acls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `schedules_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `ips_id` int DEFAULT NULL,
  `comps_id` int DEFAULT NULL,
  `techs_id` int DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `networks_id` int DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx-acls_schedule_id` (`schedules_id`),
  KEY `idx-acls_service_id` (`services_id`),
  KEY `idx-acls_ip_id` (`ips_id`),
  KEY `idx-acls_comp_id` (`comps_id`),
  KEY `idx-acls_tech_id` (`techs_id`),
  KEY `idx-acls-updated_at` (`updated_at`),
  KEY `idx-acls-updated_by` (`updated_by`),
  KEY `idx-acls-networks_id` (`networks_id`),
  CONSTRAINT `fk-acls-comp` FOREIGN KEY (`comps_id`) REFERENCES `comps` (`id`),
  CONSTRAINT `fk-acls-ip` FOREIGN KEY (`ips_id`) REFERENCES `net_ips` (`id`),
  CONSTRAINT `fk-acls-schedule` FOREIGN KEY (`schedules_id`) REFERENCES `schedules` (`id`),
  CONSTRAINT `fk-acls-service` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`),
  CONSTRAINT `fk-acls-tech` FOREIGN KEY (`techs_id`) REFERENCES `techs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acls`
--

LOCK TABLES `acls` WRITE;
/*!40000 ALTER TABLE `acls` DISABLE KEYS */;
INSERT INTO `acls` VALUES (1,6,NULL,NULL,19,NULL,'','Просили вообще ко всему кластеру, но доступ открыли к терминалу, оттуда на остальные узлы через RDP',NULL,NULL,NULL,NULL),(3,8,NULL,NULL,40,NULL,'','Для выполнения должностных обязанностей удаленно','2024-03-18 15:51:38','admin',NULL,NULL),(4,8,NULL,NULL,39,NULL,'','Для выполнения должностных обязанностей удаленно','2024-03-18 15:51:26','admin',NULL,NULL);
/*!40000 ALTER TABLE `acls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acls_history`
--

DROP TABLE IF EXISTS `acls_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acls_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `schedules_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `ips_id` int DEFAULT NULL,
  `comps_id` int DEFAULT NULL,
  `techs_id` int DEFAULT NULL,
  `aces_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `networks_id` int DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `acls_history-master_id` (`master_id`),
  KEY `acls_history-updated_at` (`updated_at`),
  KEY `acls_history-updated_by` (`updated_by`),
  KEY `idx-acls_history-networks_id` (`networks_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acls_history`
--

LOCK TABLES `acls_history` WRITE;
/*!40000 ALTER TABLE `acls_history` DISABLE KEYS */;
INSERT INTO `acls_history` VALUES (1,3,'2024-03-18 15:22:27','admin',NULL,'schedules_id',NULL,NULL,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,3,'2024-03-18 15:39:06','admin',NULL,'aces_ids',NULL,NULL,8,NULL,NULL,NULL,NULL,'3',NULL,NULL),(3,3,'2024-03-18 15:49:36','admin',NULL,'comps_id',NULL,NULL,8,NULL,NULL,40,NULL,'3',NULL,NULL),(4,4,'2024-03-18 15:50:12','admin',NULL,'schedules_id,comps_id',NULL,NULL,8,NULL,NULL,39,NULL,NULL,NULL,NULL),(5,4,'2024-03-18 15:50:21','admin',NULL,'aces_ids',NULL,NULL,8,NULL,NULL,39,NULL,'4',NULL,NULL),(6,4,'2024-03-18 15:51:26','admin',NULL,'notepad',NULL,'Для выполнения должностных обязанностей удаленно',8,NULL,NULL,39,NULL,'4',NULL,NULL),(7,3,'2024-03-18 15:51:38','admin',NULL,'notepad',NULL,'Для выполнения должностных обязанностей удаленно',8,NULL,NULL,40,NULL,'3',NULL,NULL),(8,5,'2024-03-18 17:06:55','admin',NULL,'schedules_id,services_id',NULL,NULL,8,20,NULL,NULL,NULL,NULL,NULL,NULL),(9,5,'2024-03-19 03:42:25','admin',NULL,'object_deleted',NULL,NULL,8,20,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `acls_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins_in_comps`
--

DROP TABLE IF EXISTS `admins_in_comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins_in_comps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comps_id` int DEFAULT NULL,
  `users_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_in_comps-m2m` (`comps_id`,`users_id`),
  KEY `admins_in_comps-comps_id` (`comps_id`),
  KEY `admins_in_comps-users_id` (`users_id`),
  CONSTRAINT `fk-admins_in_comps-comps_id` FOREIGN KEY (`comps_id`) REFERENCES `comps` (`id`),
  CONSTRAINT `fk-admins_in_comps-users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins_in_comps`
--

LOCK TABLES `admins_in_comps` WRITE;
/*!40000 ALTER TABLE `admins_in_comps` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins_in_comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attaches`
--

DROP TABLE IF EXISTS `attaches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attaches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `techs_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `lic_types_id` int DEFAULT NULL,
  `lic_groups_id` int DEFAULT NULL,
  `lic_items_id` int DEFAULT NULL,
  `lic_keys_id` int DEFAULT NULL,
  `contracts_id` int DEFAULT NULL,
  `places_id` int DEFAULT NULL,
  `schedules_id` int DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `users_id` int DEFAULT NULL,
  `tech_models_id` int DEFAULT NULL,
  `maintenance_reqs_id` int DEFAULT NULL,
  `maintenance_jobs_id` int DEFAULT NULL,
  `partners_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-attaches-techs` (`techs_id`),
  KEY `idx-attaches-services` (`services_id`),
  KEY `idx-attaches-lic_types` (`lic_types_id`),
  KEY `idx-attaches-lic_groups` (`lic_groups_id`),
  KEY `idx-attaches-lic_items` (`lic_items_id`),
  KEY `idx-attaches-lic_keys` (`lic_keys_id`),
  KEY `idx-attaches-contracts` (`contracts_id`),
  KEY `idx-attaches-places` (`places_id`),
  KEY `idx-attaches-schedules` (`schedules_id`),
  KEY `idx-attaches-maintenance_reqs_id` (`maintenance_reqs_id`),
  KEY `idx-attaches-maintenance_jobs_id` (`maintenance_jobs_id`),
  KEY `idx-attaches-partners_id` (`partners_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attaches`
--

LOCK TABLES `attaches` WRITE;
/*!40000 ALTER TABLE `attaches` DISABLE KEYS */;
INSERT INTO `attaches` VALUES (7,NULL,23,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'7-gay-running.gif',NULL,NULL,NULL,NULL,NULL),(8,NULL,21,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'8-159421b32a1f93c37dfb3d2b48ad9675.jpg',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `attaches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_assignment`
--

DROP TABLE IF EXISTS `auth_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_assignment` (
  `item_name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` int DEFAULT NULL,
  PRIMARY KEY (`item_name`,`user_id`),
  KEY `idx-auth_assignment-user_id` (`user_id`),
  CONSTRAINT `auth_assignment_ibfk_1` FOREIGN KEY (`item_name`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_assignment`
--

LOCK TABLES `auth_assignment` WRITE;
/*!40000 ALTER TABLE `auth_assignment` DISABLE KEYS */;
INSERT INTO `auth_assignment` VALUES ('admin','1',1693854620),('editor','1',1693854620),('viewer','1',1693854620),('viewer','17',1701231209);
/*!40000 ALTER TABLE `auth_assignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_item`
--

DROP TABLE IF EXISTS `auth_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_item` (
  `name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` smallint NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `rule_name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `data` blob,
  `created_at` int DEFAULT NULL,
  `updated_at` int DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `rule_name` (`rule_name`),
  KEY `idx-auth_item-type` (`type`),
  CONSTRAINT `auth_item_ibfk_1` FOREIGN KEY (`rule_name`) REFERENCES `auth_rule` (`name`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_item`
--

LOCK TABLES `auth_item` WRITE;
/*!40000 ALTER TABLE `auth_item` DISABLE KEYS */;
INSERT INTO `auth_item` VALUES ('acl',2,'Управление правами доступа',NULL,NULL,1693801011,1693801011),('admin',1,'Управление данными и правами доступа к ним',NULL,NULL,1693801011,1693801011),('edit',2,'Редактирование всех объектов',NULL,NULL,1693801011,1693801011),('editor',1,'Может просматривать и редактировать данные',NULL,NULL,1693801011,1693801011),('view',2,'Просмотр всех объектов',NULL,NULL,1693801011,1693801011),('viewer',1,'Может только просматривать данные',NULL,NULL,1693801011,1693801011);
/*!40000 ALTER TABLE `auth_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_item_child`
--

DROP TABLE IF EXISTS `auth_item_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_item_child` (
  `parent` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `child` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`parent`,`child`),
  KEY `child` (`child`),
  CONSTRAINT `auth_item_child_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `auth_item_child_ibfk_2` FOREIGN KEY (`child`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_item_child`
--

LOCK TABLES `auth_item_child` WRITE;
/*!40000 ALTER TABLE `auth_item_child` DISABLE KEYS */;
INSERT INTO `auth_item_child` VALUES ('admin','acl'),('admin','edit'),('editor','edit'),('admin','view'),('editor','view'),('viewer','view');
/*!40000 ALTER TABLE `auth_item_child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_rule`
--

DROP TABLE IF EXISTS `auth_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_rule` (
  `name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `data` blob,
  `created_at` int DEFAULT NULL,
  `updated_at` int DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_rule`
--

LOCK TABLES `auth_rule` WRITE;
/*!40000 ALTER TABLE `auth_rule` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comps`
--

DROP TABLE IF EXISTS `comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comps` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `domain_id` int DEFAULT NULL COMMENT 'Домен',
  `name` varchar(128) DEFAULT NULL,
  `os` varchar(128) NOT NULL COMMENT 'ОС',
  `raw_hw` text COMMENT 'Отпечаток железа',
  `raw_soft` mediumtext,
  `raw_version` varchar(32) DEFAULT NULL COMMENT 'Версия скрипта отправившего данные',
  `exclude_hw` text COMMENT 'Оборудование для исключения из паспорта',
  `ignore_hw` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Игнорировать аппаратное обеспечение',
  `ip` varchar(768) DEFAULT NULL,
  `ip_ignore` varchar(512) DEFAULT NULL,
  `arm_id` int DEFAULT NULL COMMENT 'Рабочее место',
  `comment` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL,
  `mac` varchar(768) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT '0',
  `external_links` text,
  `updated_by` varchar(32) DEFAULT NULL,
  `sandbox_id` int DEFAULT NULL,
  `platform_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `arm_id` (`arm_id`),
  KEY `idx-comps_user` (`user_id`),
  KEY `comps_archived_index` (`archived`),
  KEY `idx-comps-updated_by` (`updated_by`),
  KEY `idx-comps-sandbox_id` (`sandbox_id`),
  KEY `idx-comps-platform_id` (`platform_id`),
  CONSTRAINT `fk-comps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb3 COMMENT='Компьютеры';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comps`
--

LOCK TABLES `comps` WRITE;
/*!40000 ALTER TABLE `comps` DISABLE KEYS */;
INSERT INTO `comps` VALUES (1,1,'MSK-ESXi1','VMware ESXi, v6.7.0','{\"motherboard\":{\"manufacturer\":\"HP\", \"product\":\"ProLiant DL380 Gen9\", \"serial\":\"6CU541X8EB\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}}','','0.9powercli',NULL,0,'10.20.1.30',NULL,1,'','2024-03-25 23:20:01',NULL,'00155d3b7933',0,'',NULL,NULL,NULL),(2,1,'MSK-ESXi2','VMware ESXi, v6.7.0','{\"motherboard\":{\"manufacturer\":\"HP\", \"product\":\"ProLiant DL380 Gen9\", \"serial\":\"6CU541X8FA\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}}','','0.9powercli',NULL,0,'10.20.1.31',NULL,2,'','2024-03-26 00:40:01',NULL,'00155d3b7941',0,'',NULL,NULL,NULL),(3,1,'MSK-DC1','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},\r\n{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},\r\n{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},\r\n{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15',NULL,1,'10.20.75.10',NULL,1,'','2024-03-26 03:50:02',NULL,'00155d330500',0,'',NULL,NULL,NULL),(4,1,'MSK-FSRV','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-52\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"69\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"215\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','','0.15',NULL,1,'10.20.75.16',NULL,1,'','2025-05-08 11:30:01',NULL,'00155d330512',0,'',NULL,NULL,NULL),(5,1,'wks-03','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.20.100.23',NULL,5,'','2024-03-25 23:10:01',NULL,'005056b4d780',0,'',NULL,NULL,NULL),(6,1,'wks-04','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.20.100.26',NULL,6,'','2024-03-25 22:20:01',NULL,'024208d2b582',0,'',NULL,NULL,NULL),(7,1,'wks-05','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.20.100.25',NULL,7,'','2024-03-26 01:50:01',NULL,'1a9cc268ceb7',0,'',NULL,NULL,NULL),(8,1,'wks-06','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.20.100.30',NULL,17,'','2024-03-25 07:10:02',NULL,'024231533ef0',0,'',NULL,NULL,NULL),(9,1,'chel-pc-01','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.50.100.24',NULL,8,'','2025-05-08 12:30:01',NULL,'be6c0e44c1ac',0,'',NULL,NULL,NULL),(10,1,'chel-pc-02','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.50.100.28',NULL,9,'','2024-03-25 16:30:01',NULL,'ca817492a179',0,'',NULL,NULL,NULL),(11,1,'chel-pc-03','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.50.100.31',NULL,10,'','2025-05-08 11:20:01',NULL,'024475adf01a',0,'',NULL,NULL,NULL),(12,1,'chel-pc-04','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.50.100.40',NULL,11,'','2024-03-26 03:20:01',NULL,'f245fcbb16aa',0,'',NULL,NULL,NULL),(13,2,'laptop1','10.0.19042 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"LENOVO\", \"product\":\"LNVNB161216\", \"serial\":\"PF27X2FZ\"}},{\"processor\":\"Intel(R) Core(TM) i5-1035G4 CPU @ 1.10GHz\"},{\"memorybank\":{\"manufacturer\":\"Micron\", \"capacity\":\"8192\"}},{\"memorybank\":{\"manufacturer\":\"Micron\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"JetFlash Transcend 8GB USB Device\",\"size\":\"8\"}},{\"harddisk\":{\"model\":\"SAMSUNG MZVLB1T0HBLR-000L2\",\"size\":\"1024\"}},{\"videocard\":{\"name\":\"Intel(R) Iris(R) Plus Graphics\",\"ram\":\"1024\"}},{\"Monitor\":{\"DeviceID\":\"8895\",\"ManufactureDate\":\"1/2018\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"Not Present in EDID\",\"Version\":\"1.4\",\"VESAID\":\"LEN8895\",\"PNPID\":\"4&47c53e0&0&UID8388688\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.50.50.10\n192.168.0.1',NULL,24,'','2024-03-25 23:30:02',NULL,'dea9a5774774',0,'',NULL,NULL,NULL),(14,2,'laptop2','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"LENOVO\", \"product\":\"LNVNB161216\", \"serial\":\"PF27X2FZ\"}},{\"processor\":\"Intel(R) Core(TM) i5-1035G4 CPU @ 1.10GHz\"},{\"memorybank\":{\"manufacturer\":\"Micron\", \"capacity\":\"8192\"}},{\"memorybank\":{\"manufacturer\":\"Micron\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"JetFlash Transcend 8GB USB Device\",\"size\":\"8\"}},{\"harddisk\":{\"model\":\"SAMSUNG MZVLB1T0HBLR-000L2\",\"size\":\"1024\"}},{\"videocard\":{\"name\":\"Intel(R) Iris(R) Plus Graphics\",\"ram\":\"1024\"}},{\"Monitor\":{\"DeviceID\":\"A0BA\",\"ManufactureDate\":\"4/2016\",\"SerialNumber\":\"7MT0164D0ENL\",\"ModelName\":\"DELL U2415\",\"Version\":\"1.3\",\"VESAID\":\"DELA0BA\",\"PNPID\":\"4&47c53e0&0&UID36931\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15',NULL,0,'10.50.10.14',NULL,25,'','2024-03-26 02:40:01',NULL,'de3907302a54',0,'',NULL,NULL,NULL),(15,1,'msk-asterisk','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.20.7.3',NULL,1,'','2024-03-25 18:20:02',NULL,'005056834273',0,'',NULL,NULL,NULL),(16,1,'msk-inventory','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.20.75.20',NULL,1,'','2025-05-08 13:10:01',NULL,'3acecf3c4a27',0,'',NULL,NULL,NULL),(17,1,'msk-1c-app','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"8\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"75\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15',NULL,1,'10.20.101.10',NULL,1,'','2025-05-08 12:40:01',NULL,'9a233dab5b4d',0,'',NULL,NULL,NULL),(18,1,'msk-1c-db','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"8\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"100\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15',NULL,1,'10.20.101.12',NULL,2,'','2025-05-08 13:00:01',NULL,'16f57da0a43b',0,'',NULL,NULL,NULL),(19,1,'msk-1c-term','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"6\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"69\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15',NULL,1,'10.20.101.50',NULL,2,'','2024-03-25 23:40:01',NULL,'e246ed690c08',0,'',NULL,NULL,NULL),(20,1,'MSK-OVPN','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.0.0.2',NULL,1,'','2024-03-25 15:20:01',NULL,'b290658acc66',0,'',NULL,NULL,NULL),(21,1,'msk-gw','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'55.66.77.77\n55.66.77.81',NULL,2,'','2024-03-25 07:20:02',NULL,'325a1b3baa0c',0,'',NULL,NULL,NULL),(22,1,'CHL-ESXi1','VMware ESXi, v6.7.0','{\"motherboard\":{\"manufacturer\":\"HP\", \"product\":\"ProLiant DL380 Gen9\", \"serial\":\"6CU541X8EB\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}}','','0.9powercli',NULL,0,'10.50.1.31',NULL,33,'','2024-03-26 03:00:01',NULL,'02e5027e90fa',0,'',NULL,NULL,NULL),(23,1,'CHL-ESXi2','VMware ESXi, v6.7.0','{\"motherboard\":{\"manufacturer\":\"HP\", \"product\":\"ProLiant DL380 Gen9\", \"serial\":\"6CU541X8EB\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}}','','0.9powercli',NULL,0,'10.50.1.32',NULL,34,'','2025-05-08 11:10:01',NULL,'72e6bbae08ca',0,'',NULL,NULL,NULL),(24,1,'chl-gw','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'66.77.88.98',NULL,33,'','2025-05-08 12:00:01',NULL,'d62ce4c50d4a\nc6130dcc91bc',0,'',NULL,NULL,NULL),(25,1,'chl-ovpn','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.0.0.1',NULL,34,'','2025-05-08 11:06:45',NULL,'b290658aee66',0,'',NULL,NULL,NULL),(26,1,'msk-ilo-1','iLO 4 Sandard','','','',NULL,0,'10.20.1.10',NULL,1,'iLO: Управление и мониторинг сервером (Administrator:Administrator)\r\nИз 10ки не открывается (ERR_SSL_VERSION_OR_CIPHER_MISMATCH)\r\nИз 7ки - норм\r\nSystem ROM P70 03/01/2013 \r\nBackup System ROM 07/15/2012 \r\nIntegrated Remote Console .NET    Java  \r\nLicense Type iLO 4 Standard \r\niLO Firmware Version 1.20 Feb 01 2013 ','2024-03-25 13:20:01',NULL,'008dea5d80f6',0,'',NULL,NULL,NULL),(27,1,'msk-ilo-2','iLO 4 Sandard','','','',NULL,0,'10.20.1.11',NULL,2,'iLO: Управление и мониторинг сервером (Administrator:Administrator)\r\nИз 10ки не открывается (ERR_SSL_VERSION_OR_CIPHER_MISMATCH)\r\nИз 7ки - норм\r\nSystem ROM P70 03/01/2013 \r\nBackup System ROM 07/15/2012 \r\nIntegrated Remote Console .NET    Java  \r\nLicense Type iLO 4 Standard \r\niLO Firmware Version 1.20 Feb 01 2013 ','2024-03-26 01:10:01',NULL,'0044c45ebc70',0,'',NULL,NULL,NULL),(28,1,'chl-ilo-1','iLO 4 Sandard','','','',NULL,0,'10.50.1.10',NULL,33,'iLO: Управление и мониторинг сервером (Administrator:adm1n)\r\nИз 10ки не открывается (ERR_SSL_VERSION_OR_CIPHER_MISMATCH)\r\nИз 7ки - норм\r\nSystem ROM P70 03/01/2013 \r\nBackup System ROM 07/15/2012 \r\nIntegrated Remote Console .NET    Java  \r\nLicense Type iLO 4 Standard \r\niLO Firmware Version 1.20 Feb 01 2013 ','2024-03-25 22:50:02',NULL,'00b18e2044a3',0,'',NULL,NULL,NULL),(29,1,'chl-ilo-2','iLO 4 Sandard','','','',NULL,0,'10.50.1.11',NULL,34,'iLO: Управление и мониторинг сервером (Administrator:adm1n)\r\nИз 10ки не открывается (ERR_SSL_VERSION_OR_CIPHER_MISMATCH)\r\nИз 7ки - норм\r\nSystem ROM P70 03/01/2013 \r\nBackup System ROM 07/15/2012 \r\nIntegrated Remote Console .NET    Java  \r\nLicense Type iLO 4 Standard \r\niLO Firmware Version 1.20 Feb 01 2013 \r\n','2025-05-08 13:40:01',NULL,'0024ab55430d',0,'',NULL,NULL,NULL),(30,1,'mhk-vcenter','VMware Photon OS (64-bit)','{\"processor\": {\"model\":\"VMware\",\"cores\":\"4\"}},{\"memorybank\": {\"manufacturer\":\"VMware\",\"capacity\":\"4096\",\"serial\":\"\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"11\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"0\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"1\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"98\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"24\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"49\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"15\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}}','','0.9powercli',NULL,1,'10.50.1.50',NULL,2,'','2024-03-26 02:50:01',NULL,'00a7ba5fa826',0,'',NULL,NULL,NULL),(31,1,'chl-vcenter','VMware Photon OS (64-bit)','{\"processor\": {\"model\":\"VMware\",\"cores\":\"4\"}},{\"memorybank\": {\"manufacturer\":\"VMware\",\"capacity\":\"4096\",\"serial\":\"\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"11\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"0\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"1\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"98\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"24\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"49\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"15\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}},{\"harddisk\":{\"model\":\"VMware Virtual disk SCSI Disk Device\",\"size\":\"10\"}}','','0.9powercli',NULL,1,'10.20.1.50',NULL,34,'','2024-03-25 18:50:01',NULL,'00dfffde19ee',0,'',NULL,NULL,NULL),(32,1,'msk-dc2','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},\r\n{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},\r\n{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},\r\n{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','','0.15',NULL,1,'10.20.75.11',NULL,2,'','2025-05-08 13:20:02',NULL,'00a8988fd54d',0,'',NULL,NULL,NULL),(33,1,'CHL-ZABBIX','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.50.75.4',NULL,34,'','2024-03-26 00:20:01',NULL,'0083196e48af',0,'',NULL,NULL,NULL),(34,1,'MSK-PROXY','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.20.100.10',NULL,1,'','2025-05-08 13:30:01',NULL,'00e12324cedf',0,'',NULL,NULL,NULL),(35,1,'CHL-PROXY','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.50.100.10',NULL,34,'','2025-05-08 12:20:01',NULL,'00ef0bd7ed78',0,'',NULL,NULL,NULL),(36,1,'MSK-WWW','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,0,'55.66.77.90',NULL,1,'','2025-05-08 11:40:01',NULL,'00154bfcb14d',0,'',NULL,NULL,NULL),(37,1,'MSK-NS','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,0,'55.66.77.82',NULL,1,'','2024-03-26 03:10:01',NULL,'0091ffa06ea1',0,'',NULL,NULL,NULL),(39,2,'admin-lab7','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.50.30.64',NULL,2,'','2024-03-26 00:10:01',6,'0078e4940764',0,'',NULL,NULL,NULL),(40,1,'CHL-ADMIN0','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}','','0.6.4nix',NULL,1,'10.50.30.91',NULL,33,'','2024-03-26 00:30:02',1,'ba463cf7057a',0,'',NULL,NULL,NULL),(41,1,'msk-veeam','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},\r\n{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},\r\n{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},\r\n{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','','',NULL,1,'10.20.1.40',NULL,2,'','2025-05-09 14:41:55',NULL,'00a8988fe44e',0,'',NULL,NULL,NULL);
/*!40000 ALTER TABLE `comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comps_history`
--

DROP TABLE IF EXISTS `comps_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comps_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `arm_id` int DEFAULT NULL,
  `domain_id` int DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `os` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `raw_hw` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `raw_soft` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `raw_version` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mac` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ip_ignore` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `user_id` int DEFAULT NULL,
  `external_links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `archived` tinyint(1) DEFAULT NULL,
  `services_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `aces_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `acls_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lic_groups_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lic_items_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lic_keys_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `maintenance_reqs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `maintenance_jobs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sandbox_id` int DEFAULT NULL,
  `platform_id` int DEFAULT NULL,
  `admins_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `comps_history-master_id` (`master_id`),
  KEY `comps_history-updated_at` (`updated_at`),
  KEY `comps_history-updated_by` (`updated_by`),
  KEY `idx-comps_history-sandbox_id` (`sandbox_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comps_history`
--

LOCK TABLES `comps_history` WRITE;
/*!40000 ALTER TABLE `comps_history` DISABLE KEYS */;
INSERT INTO `comps_history` VALUES (1,41,'2025-05-09 14:41:55',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,ip,mac,archived',2,1,'msk-veeam','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},\r\n{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},\r\n{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},\r\n{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}',NULL,NULL,'10.20.1.40','00a8988fe44e',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,41,'2025-05-09 14:42:12',NULL,NULL,'services_ids',2,1,'msk-veeam','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},\r\n{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},\r\n{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},\r\n{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}',NULL,NULL,'10.20.1.40','00a8988fe44e',NULL,NULL,NULL,0,'26',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,3,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_soft,raw_version,ip,mac,services_ids,maintenance_jobs_ids',1,1,'MSK-DC1','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},\r\n{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},\r\n{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},\r\n{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15','10.20.75.10','00155d330500',NULL,NULL,NULL,0,'12,14,15',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(4,4,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',1,1,'MSK-FSRV','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-52\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"69\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"215\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}',NULL,'0.15','10.20.75.16','00155d330512',NULL,NULL,NULL,0,'13',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(5,15,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',1,1,'msk-asterisk','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}',NULL,'0.6.4nix','10.20.7.3','005056834273',NULL,NULL,NULL,0,'11',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(6,16,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',1,1,'msk-inventory','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}',NULL,'0.6.4nix','10.20.75.20','3acecf3c4a27',NULL,NULL,NULL,0,'24',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(7,17,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_soft,raw_version,ip,mac,services_ids,lic_groups_ids,maintenance_jobs_ids',1,1,'msk-1c-app','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"8\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"75\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15','10.20.101.10','9a233dab5b4d',NULL,NULL,NULL,0,'18',NULL,NULL,'25',NULL,NULL,NULL,'1',NULL,NULL,NULL),(8,18,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_soft,raw_version,ip,mac,services_ids,maintenance_jobs_ids',2,1,'msk-1c-db','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"8\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"100\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15','10.20.101.12','16f57da0a43b',NULL,NULL,NULL,0,'18',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(9,19,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_soft,raw_version,ip,mac,services_ids,acls_ids,maintenance_jobs_ids',2,1,'msk-1c-term','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"6\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"69\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15','10.20.101.50','e246ed690c08',NULL,NULL,NULL,0,'18',NULL,'1',NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(10,20,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',1,1,'MSK-OVPN','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}',NULL,'0.6.4nix','10.0.0.2','b290658acc66',NULL,NULL,NULL,0,'16,17',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(11,21,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',2,1,'msk-gw','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}',NULL,'0.6.4nix','55.66.77.77\n55.66.77.81','325a1b3baa0c',NULL,NULL,NULL,0,'6,7',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(12,24,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',33,1,'chl-gw','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}',NULL,'0.6.4nix','66.77.88.98','d62ce4c50d4a\nc6130dcc91bc',NULL,NULL,NULL,0,'6,7',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(13,25,'2025-05-15 10:55:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',34,1,'chl-ovpn','Debian GNU/Linux 10 (buster)','{\"processor\": \"virtual 2 cores\",\"cores\":\"2\"}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"8\",\"serial\":\"\"}}\r\n,\r\n{\"harddisk\": {\"model\":\"Virtual\",\"size\":\"0\",\"serial\":\"\"}}\r\n,\r\n{\"memorybank\": {\"manufacturer\":\"Not Specified\",\"capacity\":\"2048\",\"serial\":\"Not Specified\"}}',NULL,'0.6.4nix','10.0.0.1','b290658aee66',NULL,NULL,NULL,0,'16,17',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(14,3,'2025-05-29 11:32:40',NULL,NULL,'maintenance_jobs_ids',1,1,'MSK-DC1','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},\r\n{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},\r\n{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},\r\n{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15','10.20.75.10','00155d330500',NULL,NULL,NULL,0,'12,14,15',NULL,NULL,NULL,NULL,NULL,NULL,'1,3',NULL,NULL,NULL),(15,5,'2025-05-29 11:32:40',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_soft,raw_version,ip,mac,maintenance_jobs_ids',5,1,'wks-03','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15','10.20.100.23','005056b4d780',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3',NULL,NULL,NULL),(16,1,'2025-05-29 17:34:34',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',1,1,'MSK-ESXi1','VMware ESXi, v6.7.0','{\"motherboard\":{\"manufacturer\":\"HP\", \"product\":\"ProLiant DL380 Gen9\", \"serial\":\"6CU541X8EB\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}}',NULL,'0.9powercli','10.20.1.30','00155d3b7933',NULL,NULL,NULL,0,'9',NULL,NULL,NULL,NULL,NULL,NULL,'3',NULL,NULL,NULL),(17,2,'2025-05-29 17:34:34',NULL,NULL,'arm_id,domain_id,name,os,raw_hw,raw_version,ip,mac,services_ids,maintenance_jobs_ids',2,1,'MSK-ESXi2','VMware ESXi, v6.7.0','{\"motherboard\":{\"manufacturer\":\"HP\", \"product\":\"ProLiant DL380 Gen9\", \"serial\":\"6CU541X8FA\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"14\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}},{\"memorybank\":{\"manufacturer\":\"HP     \", \"capacity\":\"32768\"}}',NULL,'0.9powercli','10.20.1.31','00155d3b7941',NULL,NULL,NULL,0,'9',NULL,NULL,NULL,NULL,NULL,NULL,'3',NULL,NULL,NULL),(18,4,'2025-05-29 17:34:34',NULL,NULL,'maintenance_jobs_ids',1,1,'MSK-FSRV','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-52\"}},{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"69\"}},{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"215\"}},{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}',NULL,'0.15','10.20.75.16','00155d330512',NULL,NULL,NULL,0,'13',NULL,NULL,NULL,NULL,NULL,NULL,'1,3',NULL,NULL,NULL),(19,3,'2025-05-29 17:34:34',NULL,NULL,'maintenance_jobs_ids',1,1,'MSK-DC1','10.0.20348 Майкрософт Windows Server 2022 Standard','{\"motherboard\":{\"manufacturer\":\"Microsoft Corporation\", \"product\":\"Virtual Machine\", \"serial\":\"4945-8879-0977-6354-3000-3309-50\"}},\r\n{\"processor\":{\"model\":\"Intel(R) Xeon(R) CPU E5-2680 v4 @ 2.40GHz\", \"cores\":\"1\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"3968\"}},\r\n{\"memorybank\":{\"manufacturer\":\"Microsoft Corporation\", \"capacity\":\"128\"}},\r\n{\"harddisk\":{\"model\":\"Microsoft Virtual Disk\",\"size\":\"70\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Hyper-V Video\",\"ram\":\"\"}},\r\n{\"videocard\":{\"name\":\"Microsoft Remote Display Adapter\",\"ram\":\"\"}},\r\n{\"Monitor\":{\"DeviceID\":\"062E\",\"ManufactureDate\":\"11/2011\",\"SerialNumber\":\"Not Present in EDID\",\"ModelName\":\"HyperVMonitor\",\"Version\":\"1.4\",\"VESAID\":\"MSH062E\",\"PNPID\":\"5&1a097cd8&0&UID5527112\"}}','{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows Server 2022 Standard\"}','0.15','10.20.75.10','00155d330500',NULL,NULL,NULL,0,'12,14,15',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL),(20,5,'2025-05-29 17:34:34',NULL,NULL,'maintenance_jobs_ids',5,1,'wks-03','10.0.19045 Майкрософт Windows 10 Pro','{\"motherboard\":{\"manufacturer\":\"Intel Corporation\", \"product\":\"NUC7JYB\", \"serial\":\"GEJY14900ACN\"}},{\"processor\":{\"model\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\", \"cores\":\"2\"}},{\"memorybank\":{\"manufacturer\":\"Kingston\", \"capacity\":\"8192\"}},{\"harddisk\":{\"model\":\"KINGSTON SA400S37240G\",\"size\":\"240\"}},{\"videocard\":{\"name\":\"Intel(R) UHD Graphics 600\",\"ram\":\"1024\"}}','{\"publisher\":\"CANON INC.\", \"name\":\"Canon Laser Printer/Scanner/Fax Extended Survey Program\"},{\"publisher\":\"The Document Foundation\", \"name\":\"LibreOffice 7.4.1.2\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft Update Health Tools\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF240 Series\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Update for Windows 10 for x64-based Systems (KB5001716)\"},{\"publisher\":\"CANON INC.\", \"name\":\"Canon MF Scan Utility\"},{\"publisher\":\"\\\"Лаборатория Касперского\\\"\", \"name\":\"Агент администрирования Kaspersky Security Center\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Microsoft Edge\"},{\"publisher\":\"\", \"name\":\"Microsoft Edge Update\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Bitrix, Inc\", \"name\":\"Bitrix24\"},{\"publisher\":\"LiteManagerTeam\", \"name\":\"LiteManager Pro - Server\"},{\"publisher\":\"IBM\", \"name\":\"Lotus Notes 8.5.3 (Basic) ru\"},{\"publisher\":\"АО \\\"Лаборатория Касперского\\\"\", \"name\":\"Kaspersky Endpoint Security для Windows\"},{\"publisher\":\"Mail.ru LLC\", \"name\":\"ICQ (версия 23.2.0.48119)\"},{\"publisher\":\"Корпорация Майкрософт\", \"name\":\"Среда выполнения Microsoft Edge WebView2 Runtime\"},{\"publisher\":\"Microsoft Corporation\", \"name\":\"Microsoft OneDrive\"},{\"publisher\":\"Yandex\", \"name\":\"Yandex\"},{\"publisher\":\"Яндекс\", \"name\":\"Кнопка \\\"Яндекс\\\" на панели задач\"},{\"publisher\":\"Microsoft\", \"name\":\"Майкрософт Windows 10 Pro\"}','0.15','10.20.100.23','005056b4d780',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `comps_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comps_in_aces`
--

DROP TABLE IF EXISTS `comps_in_aces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comps_in_aces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comps_id` int NOT NULL,
  `aces_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-comps_in_aces_ace_id` (`aces_id`),
  KEY `idx-comps_in_aces_comp_id` (`comps_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comps_in_aces`
--

LOCK TABLES `comps_in_aces` WRITE;
/*!40000 ALTER TABLE `comps_in_aces` DISABLE KEYS */;
/*!40000 ALTER TABLE `comps_in_aces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comps_in_services`
--

DROP TABLE IF EXISTS `comps_in_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comps_in_services` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `comps_id` int NOT NULL,
  `services_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-comps_in_services-comps_id` (`comps_id`),
  KEY `idx-comps_in_services-services_id` (`services_id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comps_in_services`
--

LOCK TABLES `comps_in_services` WRITE;
/*!40000 ALTER TABLE `comps_in_services` DISABLE KEYS */;
INSERT INTO `comps_in_services` VALUES (1,24,6),(2,21,6),(5,24,7),(6,21,7),(7,28,8),(8,29,8),(9,26,8),(10,27,8),(15,22,9),(16,23,9),(17,1,9),(18,2,9),(36,25,16),(37,20,16),(57,36,22),(58,37,23),(60,3,15),(61,3,14),(62,3,12),(64,32,15),(65,32,14),(66,32,12),(78,17,18),(79,18,18),(80,19,18),(81,35,19),(82,34,19),(83,4,13),(84,15,11),(85,31,10),(86,30,10),(93,16,24),(94,33,20),(95,25,17),(96,20,17),(98,41,26);
/*!40000 ALTER TABLE `comps_in_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comps_rescan_queue`
--

DROP TABLE IF EXISTS `comps_rescan_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comps_rescan_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comps_id` int NOT NULL,
  `soft_id` int NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comps_rescan_queue_comps` (`comps_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comps_rescan_queue`
--

LOCK TABLES `comps_rescan_queue` WRITE;
/*!40000 ALTER TABLE `comps_rescan_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `comps_rescan_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `parent_id` int DEFAULT NULL COMMENT 'Родительский договор',
  `is_successor` int DEFAULT '0',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название документа',
  `date` date DEFAULT NULL COMMENT 'Начало периода действия',
  `end_date` date DEFAULT NULL COMMENT 'Конец периода действия',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Комментарий',
  `state_id` int DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `charge` decimal(15,2) DEFAULT NULL,
  `currency_id` int NOT NULL DEFAULT '1',
  `pay_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `techs_delivery` int DEFAULT NULL,
  `materials_delivery` int DEFAULT NULL,
  `lics_delivery` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent_id`),
  KEY `name` (`name`),
  KEY `dateFrom` (`date`),
  KEY `dateTo` (`end_date`),
  KEY `idx-contracts-state` (`state_id`),
  KEY `idx-contracts-currency_id` (`currency_id`),
  KEY `idx-contracts-pay_id` (`pay_id`),
  KEY `idx-contracts-techs_delivery` (`techs_delivery`),
  KEY `idx-contracts-materials_delivery` (`materials_delivery`),
  KEY `idx-contracts-lics_delivery` (`lics_delivery`),
  CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `contracts` (`id`),
  CONSTRAINT `fk-contracts-state` FOREIGN KEY (`state_id`) REFERENCES `contracts_states` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Договоры';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
INSERT INTO `contracts` VALUES (1,NULL,0,'Договор на услуги связи №0444/23','2023-01-09',NULL,'',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(2,NULL,0,'Договор на услуги связи №1010-22','2020-10-11',NULL,'',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(3,NULL,0,'Счет №456 Серверное оборудование в МСК','2020-11-17',NULL,'',6,1348790.71,224798.45,1,NULL,NULL,NULL,NULL,NULL,NULL),(4,NULL,0,'Счет№ 709 Оборудование в офисы','2021-12-21',NULL,'',6,2507596.70,417932.78,1,NULL,NULL,NULL,NULL,NULL,NULL),(5,NULL,0,'Договор поставки № 50428','2018-11-07',NULL,'Просто рыба с интернета, для правильной иерархии документов',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(6,5,0,'ДС №1 - Серверные шкафы','2019-02-01',NULL,'',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(7,6,0,'Счет № 213 - Серверные шкафы в Чел и Мск','2019-02-15',NULL,'',6,39611.00,6601.83,1,'',2,NULL,NULL,'2025-05-14 06:51:38',NULL),(8,7,0,'ТТН 306 Шкаф Чел','2019-03-05',NULL,'',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts_history`
--

DROP TABLE IF EXISTS `contracts_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `partners_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lics_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `techs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `services_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `materials_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `users_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `parent_id` int DEFAULT NULL,
  `is_successor` tinyint(1) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `total` decimal(15,2) DEFAULT NULL,
  `charge` decimal(15,2) DEFAULT NULL,
  `currency_id` int DEFAULT NULL,
  `pay_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `techs_delivery` int DEFAULT NULL,
  `materials_delivery` int DEFAULT NULL,
  `lics_delivery` int DEFAULT NULL,
  `children_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `contracts_history-master_id` (`master_id`),
  KEY `contracts_history-updated_at` (`updated_at`),
  KEY `contracts_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts_history`
--

LOCK TABLES `contracts_history` WRITE;
/*!40000 ALTER TABLE `contracts_history` DISABLE KEYS */;
INSERT INTO `contracts_history` VALUES (1,7,'2025-05-14 06:51:38',NULL,NULL,'partners_ids,techs_ids,parent_id,date,name,state_id,total,charge,currency_id,techs_delivery,children_ids','14',NULL,'18,19',NULL,NULL,NULL,6,NULL,'2019-02-15',NULL,'Счет № 213 - Серверные шкафы в Чел и Мск',6,NULL,39611.00,6601.83,1,NULL,2,NULL,NULL,'8'),(2,6,'2025-05-14 06:51:38',NULL,NULL,'partners_ids,parent_id,date,name,currency_id,children_ids','14',NULL,NULL,NULL,NULL,NULL,5,NULL,'2019-02-01',NULL,'ДС №1 - Серверные шкафы',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'7'),(3,8,'2025-05-14 06:51:38',NULL,NULL,'partners_ids,parent_id,date,name,currency_id','14',NULL,NULL,NULL,NULL,NULL,7,NULL,'2019-03-05',NULL,'ТТН 306 Шкаф Чел',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL),(4,3,'2025-05-23 11:27:09',NULL,NULL,'partners_ids,lics_ids,techs_ids,date,name,state_id,total,charge,currency_id','19','2','1,2',NULL,NULL,NULL,NULL,0,'2020-11-17',NULL,'Счет №456 Серверное оборудование в МСК',6,NULL,1348790.71,224798.45,1,NULL,NULL,NULL,NULL,NULL),(5,4,'2025-05-23 11:27:09',NULL,NULL,'partners_ids,lics_ids,techs_ids,materials_ids,date,name,state_id,total,charge,currency_id','19','1','3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,26,27,28,29,30,31,32,41,42,43,44,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63',NULL,'9',NULL,NULL,0,'2021-12-21',NULL,'Счет№ 709 Оборудование в офисы',6,NULL,2507596.70,417932.78,1,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `contracts_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts_in_arms`
--

DROP TABLE IF EXISTS `contracts_in_arms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts_in_arms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contracts_id` int NOT NULL,
  `arms_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_id` (`contracts_id`),
  KEY `arms_id` (`arms_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts_in_arms`
--

LOCK TABLES `contracts_in_arms` WRITE;
/*!40000 ALTER TABLE `contracts_in_arms` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts_in_arms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts_in_lics`
--

DROP TABLE IF EXISTS `contracts_in_lics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts_in_lics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contracts_id` int NOT NULL,
  `lics_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_id` (`contracts_id`),
  KEY `lics_id` (`lics_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts_in_lics`
--

LOCK TABLES `contracts_in_lics` WRITE;
/*!40000 ALTER TABLE `contracts_in_lics` DISABLE KEYS */;
INSERT INTO `contracts_in_lics` VALUES (1,4,1),(4,3,2);
/*!40000 ALTER TABLE `contracts_in_lics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts_in_materials`
--

DROP TABLE IF EXISTS `contracts_in_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts_in_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contracts_id` int NOT NULL,
  `materials_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts_in_materials`
--

LOCK TABLES `contracts_in_materials` WRITE;
/*!40000 ALTER TABLE `contracts_in_materials` DISABLE KEYS */;
INSERT INTO `contracts_in_materials` VALUES (2,4,1),(4,4,8),(5,4,9);
/*!40000 ALTER TABLE `contracts_in_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts_in_services`
--

DROP TABLE IF EXISTS `contracts_in_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts_in_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `services_id` int DEFAULT NULL,
  `contracts_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-contracts_in_services_cid` (`contracts_id`),
  KEY `idx-contracts_in_services_sid` (`services_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts_in_services`
--

LOCK TABLES `contracts_in_services` WRITE;
/*!40000 ALTER TABLE `contracts_in_services` DISABLE KEYS */;
INSERT INTO `contracts_in_services` VALUES (13,2,2),(14,1,1);
/*!40000 ALTER TABLE `contracts_in_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts_in_techs`
--

DROP TABLE IF EXISTS `contracts_in_techs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts_in_techs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contracts_id` int NOT NULL,
  `techs_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_id` (`contracts_id`),
  KEY `tech_id` (`techs_id`)
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts_in_techs`
--

LOCK TABLES `contracts_in_techs` WRITE;
/*!40000 ALTER TABLE `contracts_in_techs` DISABLE KEYS */;
INSERT INTO `contracts_in_techs` VALUES (18,4,3),(19,4,4),(41,4,16),(49,4,13),(58,4,6),(60,4,7),(64,4,8),(66,4,9),(68,4,10),(70,4,11),(71,4,26),(73,4,28),(74,4,29),(75,4,30),(78,4,32),(79,4,31),(83,4,17),(84,4,27),(88,4,44),(89,4,41),(90,4,42),(91,4,43),(96,4,47),(97,4,48),(98,4,49),(102,4,51),(103,4,52),(104,4,53),(106,4,55),(108,4,57),(109,4,58),(110,4,59),(111,4,60),(112,4,61),(113,4,62),(114,4,63),(132,4,5),(133,4,15),(134,4,46),(135,4,56),(137,4,50),(141,3,2),(144,4,14),(147,4,54),(157,3,1),(160,4,12),(176,7,18),(177,7,19);
/*!40000 ALTER TABLE `contracts_in_techs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts_states`
--

DROP TABLE IF EXISTS `contracts_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts_states` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Наименование',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Описание',
  `paid` tinyint(1) DEFAULT '0',
  `unpaid` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `name` (`name`),
  KEY `idx-contracts_states-paid` (`paid`),
  KEY `idx-contracts_states-unpaid` (`unpaid`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts_states`
--

LOCK TABLES `contracts_states` WRITE;
/*!40000 ALTER TABLE `contracts_states` DISABLE KEYS */;
INSERT INTO `contracts_states` VALUES (1,'state_required','Потребность','Счет на руках, потребность очевидна, но еще до подачи счета на согласование',0,0),(2,'state_paywait','На согласовании','Сформирована СЗ на согласование счета. Ожидаем решения',0,0),(3,'state_paywait_confirmed','Согласовано','Счет согласован и ожидает оплаты/предоплаты финансовым отделом',0,1),(4,'state_payed_partial','Предоплачено','После поступления частичной предоплаты на расчетный счет поставщика\nУказывает на необходимость получить услуги или товары у поставщика работающего по схеме частичной предоплаты',1,1),(5,'state_paywait_full','Ожидает оплаты','Ожидает полной оплаты\n- после получения при работе по частичной предоплате\n- или до получения при работе по полной предоплате',0,1),(6,'state_payed','Оплачено 100%','После поступления 100% стоимости на расчетный счет поставщика',1,0),(7,'state_revoked','Отказано','Не прошел процедуру согласования, был отклонен по каким-то иным причинам',0,0),(8,'state_fail','Сторнировано','Счет потерял актуальность, необходимо запросить новый',0,0);
/*!40000 ALTER TABLE `contracts_states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currency`
--

DROP TABLE IF EXISTS `currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency` (
  `id` int NOT NULL AUTO_INCREMENT,
  `symbol` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currency`
--

LOCK TABLES `currency` WRITE;
/*!40000 ALTER TABLE `currency` DISABLE KEYS */;
INSERT INTO `currency` VALUES (1,'₽','RUR','Российский рубль','Валюта по умолчанию',NULL),(2,'$','USD','Доллар США',NULL,NULL),(3,'€','EUR','Евро',NULL,NULL);
/*!40000 ALTER TABLE `currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Подразделение',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Комментарии',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Тестовое подразделение','Тестовое примечание к тестовому подразделению'),(2,'Второе тестовое подразделение','Минимум для теста нужно два');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `domains`
--

DROP TABLE IF EXISTS `domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `domains` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fqdn` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`fqdn`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Домены';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `domains`
--

LOCK TABLES `domains` WRITE;
/*!40000 ALTER TABLE `domains` DISABLE KEYS */;
INSERT INTO `domains` VALUES (1,'TABURETKA','taburetka.local','Основной домен табуретки'),(2,'LAB1','lab1.taburetka.local','Тестовая лаборатория MS AD');
/*!40000 ALTER TABLE `domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hw_ignore`
--

DROP TABLE IF EXISTS `hw_ignore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hw_ignore` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fingerprint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fingerprint` (`fingerprint`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Список игнорируемого железа';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hw_ignore`
--

LOCK TABLES `hw_ignore` WRITE;
/*!40000 ALTER TABLE `hw_ignore` DISABLE KEYS */;
INSERT INTO `hw_ignore` VALUES (1,'videocard\\|dameware\\|dameware development mirror driver 64-bit mib\\|','DameWare Development Mirror Driver'),(2,'monitor\\|HyperVMonitor\\|HyperVMonitor\\|Not Present in EDID','HyperV - это не физический монитор, скорее драйвер какой-то');
/*!40000 ALTER TABLE `hw_ignore` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ips_in_aces`
--

DROP TABLE IF EXISTS `ips_in_aces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ips_in_aces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ips_id` int NOT NULL,
  `aces_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-ips_in_aces_ace_id` (`aces_id`),
  KEY `idx-ips_in_aces_ip_id` (`ips_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ips_in_aces`
--

LOCK TABLES `ips_in_aces` WRITE;
/*!40000 ALTER TABLE `ips_in_aces` DISABLE KEYS */;
/*!40000 ALTER TABLE `ips_in_aces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ips_in_comps`
--

DROP TABLE IF EXISTS `ips_in_comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ips_in_comps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ips_id` int DEFAULT NULL,
  `comps_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-ips_in_comps-ips` (`ips_id`),
  KEY `idx-ips_in_comps-comps` (`comps_id`)
) ENGINE=InnoDB AUTO_INCREMENT=141 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ips_in_comps`
--

LOCK TABLES `ips_in_comps` WRITE;
/*!40000 ALTER TABLE `ips_in_comps` DISABLE KEYS */;
INSERT INTO `ips_in_comps` VALUES (3,3,1),(4,4,2),(30,23,23),(32,25,21),(33,26,21),(34,27,22),(35,24,24),(37,19,17),(38,20,18),(41,21,19),(42,18,16),(44,30,20),(45,31,25),(46,1,26),(47,2,27),(48,22,28),(49,34,29),(50,35,30),(51,36,31),(58,39,34),(59,38,33),(60,40,35),(61,50,36),(62,51,37),(63,58,15),(64,59,3),(65,60,4),(66,61,32),(127,78,5),(128,8,6),(129,67,7),(130,9,8),(131,10,9),(132,11,10),(133,12,11),(134,13,12),(135,14,13),(136,15,13),(137,16,14),(138,80,39),(139,82,40),(140,83,41);
/*!40000 ALTER TABLE `ips_in_comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ips_in_techs`
--

DROP TABLE IF EXISTS `ips_in_techs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ips_in_techs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ips_id` int DEFAULT NULL,
  `techs_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-ips_in_techs-ips` (`ips_id`),
  KEY `idx-ips_in_techs-techs` (`techs_id`)
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ips_in_techs`
--

LOCK TABLES `ips_in_techs` WRITE;
/*!40000 ALTER TABLE `ips_in_techs` DISABLE KEYS */;
INSERT INTO `ips_in_techs` VALUES (164,78,5),(165,28,12),(166,69,12),(167,70,12),(168,71,12),(169,72,12),(170,73,12),(171,74,12),(172,32,14),(173,29,22),(174,33,23),(175,22,33),(176,34,34),(177,41,37),(178,42,38),(179,43,39),(180,44,40),(181,47,41),(182,48,42),(183,49,43),(184,46,44),(185,52,45),(186,53,45),(187,54,46),(188,55,47),(189,56,48),(190,57,49),(191,62,50),(192,63,51),(193,64,52),(194,65,53),(195,81,54),(196,66,55);
/*!40000 ALTER TABLE `ips_in_techs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ips_in_users`
--

DROP TABLE IF EXISTS `ips_in_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ips_in_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ips_id` int DEFAULT NULL,
  `users_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-ips_in_users-ips` (`ips_id`),
  KEY `idx-ips_in_users-users` (`users_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ips_in_users`
--

LOCK TABLES `ips_in_users` WRITE;
/*!40000 ALTER TABLE `ips_in_users` DISABLE KEYS */;
INSERT INTO `ips_in_users` VALUES (1,75,12),(2,76,10),(3,77,9);
/*!40000 ALTER TABLE `ips_in_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_groups`
--

DROP TABLE IF EXISTS `lic_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_groups` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `lic_types_id` int DEFAULT NULL,
  `descr` varchar(255) NOT NULL COMMENT 'Описание',
  `comment` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lic_types_id` (`lic_types_id`),
  KEY `idx-lic_groups-services_id` (`services_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb3 COMMENT='Группы лицензий';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_groups`
--

LOCK TABLES `lic_groups` WRITE;
/*!40000 ALTER TABLE `lic_groups` DISABLE KEYS */;
INSERT INTO `lic_groups` VALUES (1,1,'Microsoft Windows 10 Pro OEM','Группа OEM лицензий на WIndows 10 Pro приобретенных с ПК ','2023-09-08 15:31:53',NULL,NULL),(2,2,'Win Server Datacenter 2012R2','','2023-09-08 15:31:54',NULL,NULL),(3,3,'MS SQL 2014 Enterprise','','2023-09-08 15:31:54',NULL,NULL),(4,4,'Лицензии на HelpDesk Litemanager для ИТ','','2023-09-08 15:31:54',NULL,NULL),(6,1,'Лицензии на КриптоПро CSP','','2023-09-08 15:31:54',NULL,NULL),(7,6,'Лицензии на SCAD Office 21 USB','','2023-09-08 15:31:54',NULL,NULL),(9,6,'Лицензии на Гранд-смету USB','','2023-09-08 15:31:54',NULL,NULL),(22,8,'MS Office 2016 Standard VL','MS Office 2016 Standard VL','2023-09-08 15:31:54',NULL,NULL),(23,8,'MS Visio 2016 Standard VL','MS Visio 2016 Standard VL','2023-09-08 15:31:54',NULL,NULL),(24,8,'MS Access 2016','MS Access 2016','2023-09-08 15:31:54',NULL,NULL),(25,1,'1С Предприятие 8.3','1С Предприятие 8.3\r\nКонфигурации: Бухгалтерия предприятия КОРП 3.0\r\nЗарплата и управление персоналом КОРП 2.5 клиентская лицензия','2025-08-29 07:24:02','admin',18),(29,1,'Adobe Photoshop CS6','Adobe Photoshop CS6','2023-09-08 15:31:55',NULL,NULL),(31,9,'КОМПАС-График v17','','2025-06-05 11:53:59',NULL,NULL),(33,1,'CorelDRAW Graphics 2017','','2023-09-08 15:31:55',NULL,NULL),(34,1,'CorelDRAW Graphics 2019','','2023-09-08 15:31:55',NULL,NULL),(42,1,'Microsoft Windows 7 Professional OEM','Microsoft Windows 7 Professional приобретенные вместе с ПК','2023-09-08 15:31:55',NULL,NULL),(43,1,'MS Office 2010 Std','MS Office 2010 Std','2023-09-08 15:31:55',NULL,NULL),(44,1,'MS Office 2013 Std','MS Office 2013 Std','2023-09-08 15:31:55',NULL,NULL),(45,1,'MS Project 2016 Std','MS Project 2016 Std','2023-09-08 15:31:55',NULL,NULL),(46,1,'MS Visio 2010 Std','MS Visio 2010 Std','2023-09-08 15:31:55',NULL,NULL),(47,1,'Microsoft Windows Pro 8.1 x64','Microsoft Windows Pro 8.1 x64','2023-09-08 15:31:55',NULL,NULL),(48,8,'Microsoft Windows Pro 8.1 x64 VLSC','Microsoft Windows Pro 8.1 x64 VLSC','2023-09-08 15:31:55',NULL,NULL),(54,10,'Kaspersky Endpoint Security для бизнеса','Kaspersky Endpoint Security для бизнеса\r\n','2023-09-08 15:31:55',NULL,NULL),(55,10,'Kaspersky Security для почтовых серверов','Kaspersky Security для почтовых серверов','2023-09-08 15:31:55',NULL,NULL),(56,10,'Kaspersky Security для банкоматов и точек мгновенной оплаты','Kaspersky Security для банкоматов и точек мгновенной оплаты','2023-09-08 15:31:55',NULL,NULL),(60,1,'Microsoft Windows 7 Professional 64-bit Рус.(OEM)','Microsoft Windows 7 Professional 64-bit Рус.(OEM)','2023-09-15 13:08:35',NULL,NULL),(62,1,'VMWare vSphere Essentials','Позволяет установить VMWare ESXi на три ноды и vCenter','2023-09-15 15:10:37','admin',NULL);
/*!40000 ALTER TABLE `lic_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_groups_history`
--

DROP TABLE IF EXISTS `lic_groups_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_groups_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  `updated_comment` varchar(255) DEFAULT NULL,
  `changed_attributes` text,
  `descr` varchar(255) DEFAULT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `lic_types_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `arms_ids` text,
  `comps_ids` text,
  `users_ids` text,
  PRIMARY KEY (`id`),
  KEY `lic_groups_history-master_id` (`master_id`),
  KEY `lic_groups_history-updated_at` (`updated_at`),
  KEY `lic_groups_history-updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_groups_history`
--

LOCK TABLES `lic_groups_history` WRITE;
/*!40000 ALTER TABLE `lic_groups_history` DISABLE KEYS */;
INSERT INTO `lic_groups_history` VALUES (1,25,'2025-08-29 07:24:02','admin',NULL,'descr,comment,lic_types_id,services_id,comps_ids','1С Предприятие 8.3','1С Предприятие 8.3\r\nКонфигурации: Бухгалтерия предприятия КОРП 3.0\r\nЗарплата и управление персоналом КОРП 2.5 клиентская лицензия',NULL,1,18,NULL,'17',NULL);
/*!40000 ALTER TABLE `lic_groups_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_groups_in_arms`
--

DROP TABLE IF EXISTS `lic_groups_in_arms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_groups_in_arms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `arms_id` int NOT NULL,
  `lic_groups_id` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `arms_id` (`arms_id`),
  KEY `lics_id` (`lic_groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_groups_in_arms`
--

LOCK TABLES `lic_groups_in_arms` WRITE;
/*!40000 ALTER TABLE `lic_groups_in_arms` DISABLE KEYS */;
/*!40000 ALTER TABLE `lic_groups_in_arms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_groups_in_comps`
--

DROP TABLE IF EXISTS `lic_groups_in_comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_groups_in_comps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lic_groups_id` int DEFAULT NULL,
  `comps_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_groups_in_comps`
--

LOCK TABLES `lic_groups_in_comps` WRITE;
/*!40000 ALTER TABLE `lic_groups_in_comps` DISABLE KEYS */;
INSERT INTO `lic_groups_in_comps` VALUES (1,25,17,'',1,'2023-09-15 15:15:15',1,'2023-09-15 15:15:15');
/*!40000 ALTER TABLE `lic_groups_in_comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_groups_in_users`
--

DROP TABLE IF EXISTS `lic_groups_in_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_groups_in_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lic_groups_id` int DEFAULT NULL,
  `users_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_groups_in_users`
--

LOCK TABLES `lic_groups_in_users` WRITE;
/*!40000 ALTER TABLE `lic_groups_in_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `lic_groups_in_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_items`
--

DROP TABLE IF EXISTS `lic_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_items` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `lic_group_id` int NOT NULL COMMENT 'В группе лицензий',
  `descr` varchar(255) NOT NULL COMMENT 'Описание закупки',
  `count` int NOT NULL COMMENT 'Количество приобретенных лицензий',
  `comment` text,
  `active_from` date DEFAULT NULL COMMENT 'Начало периода действия',
  `active_to` date DEFAULT NULL COMMENT 'Окончание периода действия',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время создания',
  `scans_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lic_group_id` (`lic_group_id`),
  KEY `idx-lic_items-updated_at` (`updated_at`),
  KEY `idx-lic_items-updated_by` (`updated_by`),
  KEY `idx-lic_items-services_id` (`services_id`),
  CONSTRAINT `lic_groups` FOREIGN KEY (`lic_group_id`) REFERENCES `lic_groups` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COMMENT='Лицензии';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_items`
--

LOCK TABLES `lic_items` WRITE;
/*!40000 ALTER TABLE `lic_items` DISABLE KEYS */;
INSERT INTO `lic_items` VALUES (1,1,'OEM Windows 10 в комплекте с NUC-ами',22,'',NULL,NULL,'2023-09-13 15:29:17',NULL,NULL,NULL,NULL),(2,62,'VMWare в Москву',3,'На самом деле лицензия одна, но на три узла',NULL,NULL,'2023-09-15 15:11:39',NULL,'2025-08-29 07:24:39',NULL,5),(4,6,'Для бухгалтерии',2,'',NULL,NULL,'2025-06-06 18:53:48',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `lic_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_items_history`
--

DROP TABLE IF EXISTS `lic_items_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_items_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  `updated_comment` varchar(255) DEFAULT NULL,
  `changed_attributes` text,
  `descr` varchar(255) DEFAULT NULL,
  `count` int DEFAULT NULL,
  `comment` text,
  `active_from` date DEFAULT NULL,
  `active_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `lic_group_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `contracts_ids` text,
  `arms_ids` text,
  `comps_ids` text,
  `users_ids` text,
  `licKeys_ids` text,
  PRIMARY KEY (`id`),
  KEY `lic_items_history-master_id` (`master_id`),
  KEY `lic_items_history-updated_at` (`updated_at`),
  KEY `lic_items_history-updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_items_history`
--

LOCK TABLES `lic_items_history` WRITE;
/*!40000 ALTER TABLE `lic_items_history` DISABLE KEYS */;
INSERT INTO `lic_items_history` VALUES (1,2,'2025-08-29 07:24:39',NULL,NULL,'descr,count,comment,created_at,lic_group_id,services_id,contracts_ids,arms_ids','VMWare в Москву',3,'На самом деле лицензия одна, но на три узла',NULL,NULL,'2023-09-15 15:11:39',62,5,'3','1,2',NULL,NULL,NULL);
/*!40000 ALTER TABLE `lic_items_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_items_in_arms`
--

DROP TABLE IF EXISTS `lic_items_in_arms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_items_in_arms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `arms_id` int NOT NULL,
  `lic_items_id` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `arms_id` (`arms_id`),
  KEY `lics_id` (`lic_items_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_items_in_arms`
--

LOCK TABLES `lic_items_in_arms` WRITE;
/*!40000 ALTER TABLE `lic_items_in_arms` DISABLE KEYS */;
INSERT INTO `lic_items_in_arms` VALUES (8,2,2,NULL,NULL,NULL,NULL,NULL),(9,1,2,NULL,NULL,NULL,NULL,NULL),(10,5,4,'',NULL,'2025-06-06 18:53:48',NULL,'2025-06-06 18:53:48'),(11,6,4,'',NULL,'2025-06-06 18:53:48',NULL,'2025-06-06 18:53:48');
/*!40000 ALTER TABLE `lic_items_in_arms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_items_in_comps`
--

DROP TABLE IF EXISTS `lic_items_in_comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_items_in_comps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lic_items_id` int DEFAULT NULL,
  `comps_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_items_in_comps`
--

LOCK TABLES `lic_items_in_comps` WRITE;
/*!40000 ALTER TABLE `lic_items_in_comps` DISABLE KEYS */;
/*!40000 ALTER TABLE `lic_items_in_comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_items_in_users`
--

DROP TABLE IF EXISTS `lic_items_in_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_items_in_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lic_items_id` int DEFAULT NULL,
  `users_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_items_in_users`
--

LOCK TABLES `lic_items_in_users` WRITE;
/*!40000 ALTER TABLE `lic_items_in_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `lic_items_in_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_keys`
--

DROP TABLE IF EXISTS `lic_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_keys` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `lic_items_id` int NOT NULL COMMENT 'Закупка',
  `key_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Наименование',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Комментарий',
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-lic_keys-lic_items` (`lic_items_id`),
  KEY `idx-lic_keys-updated_at` (`updated_at`),
  KEY `idx-lic_keys-updated_by` (`updated_by`),
  CONSTRAINT `fk-lic_keys_lic_items` FOREIGN KEY (`lic_items_id`) REFERENCES `lic_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_keys`
--

LOCK TABLES `lic_keys` WRITE;
/*!40000 ALTER TABLE `lic_keys` DISABLE KEYS */;
INSERT INTO `lic_keys` VALUES (1,1,'1975f377-87a5-49d3-b051-c9ed8b070d23','наклейка на корпусе',NULL,NULL),(2,1,'87f2c59b-bea6-462c-b0ab-27d561e8dc31','наклейка на корпусе',NULL,NULL),(3,1,'8c925267-b9a7-4b84-bb64-bbd742000fa7','наклейка на корпусе',NULL,NULL),(4,1,'0f8351f1-45a9-494c-958f-04b907e5d13d','наклейка на корпусе',NULL,NULL),(5,1,'8628efaa-5d7d-4896-b4a1-76cda5afc9c8','наклейка на корпусе',NULL,NULL),(6,1,'8af67a4a-5097-41e5-a35a-c0c19682e658','наклейка в серверной, надо наклеить',NULL,NULL),(7,1,'9e126ad6-e0c6-4ad7-a8cd-351f6466bd21','наклейка в серверной, надо наклеить',NULL,NULL),(8,1,'3d517bba-a08f-41a9-9deb-c5b4fdda5a39','прошит в БИОС',NULL,NULL),(9,1,'9fddab17-c867-4887-8abc-b7c0e989985f','прошит в БИОС',NULL,NULL),(10,1,'96f24a6d-62c8-4bce-ba04-7b64a59dc148','наклейка стерлась','2025-08-29 07:25:37',NULL);
/*!40000 ALTER TABLE `lic_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_keys_history`
--

DROP TABLE IF EXISTS `lic_keys_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_keys_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  `updated_comment` varchar(255) DEFAULT NULL,
  `changed_attributes` text,
  `key_text` text,
  `comment` text,
  `lic_items_id` int DEFAULT NULL,
  `arms_ids` text,
  `comps_ids` text,
  `users_ids` text,
  PRIMARY KEY (`id`),
  KEY `lic_keys_history-master_id` (`master_id`),
  KEY `lic_keys_history-updated_at` (`updated_at`),
  KEY `lic_keys_history-updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_keys_history`
--

LOCK TABLES `lic_keys_history` WRITE;
/*!40000 ALTER TABLE `lic_keys_history` DISABLE KEYS */;
INSERT INTO `lic_keys_history` VALUES (1,10,'2025-08-29 07:25:37',NULL,NULL,'key_text,comment,lic_items_id,arms_ids','96f24a6d-62c8-4bce-ba04-7b64a59dc148','наклейка стерлась',1,'7',NULL,NULL);
/*!40000 ALTER TABLE `lic_keys_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_keys_in_arms`
--

DROP TABLE IF EXISTS `lic_keys_in_arms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_keys_in_arms` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `lic_keys_id` int NOT NULL,
  `arms_id` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-lic_keys_in_arms-lic_keys_id` (`lic_keys_id`),
  KEY `idx-lic_keys_in_arms-lic_arms_id` (`arms_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_keys_in_arms`
--

LOCK TABLES `lic_keys_in_arms` WRITE;
/*!40000 ALTER TABLE `lic_keys_in_arms` DISABLE KEYS */;
INSERT INTO `lic_keys_in_arms` VALUES (1,1,3,'наклейка на корпусе',1,'2023-09-13 15:30:39',1,'2023-09-13 15:30:39'),(2,2,4,'',1,'2023-09-13 15:31:26',1,'2023-09-13 15:31:26'),(19,8,6,NULL,NULL,NULL,NULL,NULL),(21,10,7,NULL,NULL,NULL,NULL,NULL),(25,3,8,NULL,NULL,NULL,NULL,NULL),(27,5,9,NULL,NULL,NULL,NULL,NULL),(29,4,10,NULL,NULL,NULL,NULL,NULL),(31,6,11,NULL,NULL,NULL,NULL,NULL),(32,9,17,NULL,NULL,NULL,NULL,NULL),(37,7,5,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `lic_keys_in_arms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_keys_in_comps`
--

DROP TABLE IF EXISTS `lic_keys_in_comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_keys_in_comps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lic_keys_id` int DEFAULT NULL,
  `comps_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_keys_in_comps`
--

LOCK TABLES `lic_keys_in_comps` WRITE;
/*!40000 ALTER TABLE `lic_keys_in_comps` DISABLE KEYS */;
/*!40000 ALTER TABLE `lic_keys_in_comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_keys_in_users`
--

DROP TABLE IF EXISTS `lic_keys_in_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_keys_in_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lic_keys_id` int DEFAULT NULL,
  `users_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_keys_in_users`
--

LOCK TABLES `lic_keys_in_users` WRITE;
/*!40000 ALTER TABLE `lic_keys_in_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `lic_keys_in_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_types`
--

DROP TABLE IF EXISTS `lic_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lic_types` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descr` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `scans_id` int DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Типы лицензирования';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lic_types`
--

LOCK TABLES `lic_types` WRITE;
/*!40000 ALTER TABLE `lic_types` DISABLE KEYS */;
INSERT INTO `lic_types` VALUES (1,'per_machine','Лицензирование на машину (фикс)','Одна лицензия на одну инсталляцию на одну машина без автоматического занятия и высвобождения\r\nНу в целом классическая схема, хз что тут еще расписать','2021-04-22 17:56:44','',NULL,NULL),(2,'lic_winsrv_datacenter_socket','Лицензирование Windows Server Datacenter на сокеты','При лицензировании ноды гипервизора позволяет ставить на нее неограниченное число копий Windows Server лицензируемых редакций','2020-11-10 08:00:14','datasheet https://download.microsoft.com/download/7/C/E/7CED6910-C7B2-4196-8C55-208EE0B427E2/Windows_Server_2019_licensing_datasheet_EN_US.pdf',NULL,NULL),(3,'sql_corelic','Лицензирование MS SQL на ядра ЦПУ','Лицензируются все ядра выделенные ОС в которой работает ПО','2020-11-11 04:37:43','SQL Server 2014 Licensing Guide (PDF) http://go.microsoft.com/fwlink/?LinkId=230678\r\nSQL Server 2014 Licensing Datasheet (PDF) http://download.microsoft.com/download/6/6/F/66FF3259-1466-4BBA-A505-2E3DA5B2B1FA/SQL_Server_2014_Licensing_Datasheet.pdf',NULL,NULL),(4,'Litemanager_HelpDesk','Лицензирование Litemanager по схеме HelpDesk','HelpDesk лицензия — основана на количестве активных каналов (соединений), количество серверных (LM Server макс. 1000) и клиентских (LM Viewer) модулей не ограниченно, но не более 1000 ПК.\r\nОдин активный канал равен одному активному контакту в списке, работа с которым может вестись одновременно в нескольких режимах.\r\nHelpDesk лицензию можно использовать на нескольких клиентах LM Viewer одновременно, при этом на каждом LM Viewer будет по одному (или более) активному контакту.\r\nЛицензия на два активных канала позволяет вести работу с двумя контактами из списка одновременно, лицензия на 3 активных канала с 3-мя контактами и т.д.','2020-12-02 07:14:43','Страничка приобретения LM со схемами лицензирования: http://www.litemanager.ru/buy/',NULL,NULL),(5,'altium_lic','Схема лицензирования продуктов Altium','Да вот где бы ее взять?\r\nВроде как инфа такая:\r\nПродукты лицензируются на время как сервис. За это время можно ставить продукт любой версии.\r\nСами клиентские установки лицензируются через Сервер Altium Infrastructure Server','2021-03-17 05:58:50','Subscription vs. Perpetual Models in PCB Software Licensing https://resources.altium.com/p/pcb-software-licensing-subscription-vs-perpetual-models\r\nAltium License Types and Functions https://resources.altium.com/p/licensing-101-altium-license-types-and-functions\r\nAltium Designer Licensing System https://www.altium.com/documentation/altium-designer/altium-designer-licensing-system',NULL,NULL),(6,'usb-key','USB ключ пользователя','USB ключ с работой на одном пользовательском ПК. При этом установить ПО можно на многих ПК, но без ключа оно не работает.','2022-04-14 10:10:27','',NULL,NULL),(7,'lic_siemens','Siemens PLM Licensing','Лицензии выдаются одним файлом. Файл устанавливается на сервер лицензирования Siemens PLM License server, который прописывается в продуктах Siemens','2022-04-20 08:03:33','Установка сервера и файла лицензии https://wiki.azimuth.holding.local/nx:%D1%83%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0_%D1%81%D0%B5%D1%80%D0%B2%D0%B5%D1%80%D0%B0_%D0%B8_%D1%84%D0%B0%D0%B9%D0%BB%D0%B0_%D0%BB%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D0%B8',NULL,NULL),(8,'lic_ms_vl','MS Volume Licensing','Выдается пачка ключей, включая KMS, которыми можно активировать сервер лицензий.\r\nСами продукты можно устанавливать с публичными VL ключами и последующей активацией на сервере лицензий','2022-04-26 04:23:15','',NULL,NULL),(9,'Лицензирование сетевое (плавающа','Лицензирование сетевое (плавающая)','Лицензирование сетевое (плавающая)','2023-01-31 10:42:41','',NULL,NULL),(10,'Renewal License 1 year','Renewal License 1 year','продление имеющейся лицензии на следующий период','2023-03-22 10:12:19','https://www.kaspersky-security.ru/1736.html',NULL,NULL);
/*!40000 ALTER TABLE `lic_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_journal`
--

DROP TABLE IF EXISTS `login_journal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_journal` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время',
  `comp_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Компьютер',
  `comps_id` int DEFAULT NULL,
  `user_login` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Пользователь',
  `users_id` int DEFAULT NULL,
  `type` int DEFAULT '0',
  `local_time` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `calc_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comps_id` (`comps_id`),
  KEY `users_id` (`users_id`),
  KEY `login_journal_comp_name_idx` (`comp_name`),
  KEY `login_journal_user_login_idx` (`user_login`),
  KEY `login_journal_time_idx` (`time`),
  KEY `login_journal_type_idx` (`type`),
  KEY `login_journal_calc_time_idx` (`calc_time`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Журнал входа в систему';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_journal`
--

LOCK TABLES `login_journal` WRITE;
/*!40000 ALTER TABLE `login_journal` DISABLE KEYS */;
INSERT INTO `login_journal` VALUES (1,'2025-05-09 14:30:27','wks-03',5,'NinaBelozerova',2,0,NULL,NULL,'2025-05-09 14:30:27'),(2,'2025-06-05 14:09:41','wks-04',6,'SerafimaBrovina',4,0,NULL,NULL,'2025-06-05 14:09:41');
/*!40000 ALTER TABLE `login_journal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_jobs`
--

DROP TABLE IF EXISTS `maintenance_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `schedules_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_jobs-name` (`name`),
  KEY `maintenance_jobs-description` (`description`(768)),
  KEY `maintenance_jobs-schedules_id` (`schedules_id`),
  KEY `maintenance_jobs-services_id` (`services_id`),
  KEY `idx-maintenance_jobs-archived` (`archived`),
  KEY `idx-maintenance_jobs-parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_jobs`
--

LOCK TABLES `maintenance_jobs` WRITE;
/*!40000 ALTER TABLE `maintenance_jobs` DISABLE KEYS */;
INSERT INTO `maintenance_jobs` VALUES (1,'Veeam Backup GFS 7-3-0','0_о\r\n**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm730.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',9,26,NULL,'2025-06-06 05:17:37',NULL,0,NULL),(2,'Veeam Backup GFS 7-3-3','`PARENT`\r\n----\r\n**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3-3** по субботам и инкрементное по вс-пт в 20:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm733.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии, 3 ежегодных) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',NULL,NULL,NULL,'2025-05-27 11:48:16',NULL,0,1),(3,'klg-vbr\\mail_7-0-3-0','1111!!!',NULL,NULL,NULL,'2025-05-29 17:34:34',NULL,0,1);
/*!40000 ALTER TABLE `maintenance_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_jobs_history`
--

DROP TABLE IF EXISTS `maintenance_jobs_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_jobs_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `schedules_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `services_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comps_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `techs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `archived` tinyint(1) DEFAULT NULL,
  `reqs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `parent_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_jobs_history-master_id` (`master_id`),
  KEY `maintenance_jobs_history-updated_by` (`updated_by`),
  KEY `maintenance_jobs_history-updated_at` (`updated_at`),
  KEY `idx-maintenance_jobs_history-parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_jobs_history`
--

LOCK TABLES `maintenance_jobs_history` WRITE;
/*!40000 ALTER TABLE `maintenance_jobs_history` DISABLE KEYS */;
INSERT INTO `maintenance_jobs_history` VALUES (1,1,'Veeam Backup GFS 7-3','**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',NULL,NULL,NULL,NULL,NULL,NULL,'2025-05-09 14:43:53',NULL,NULL,'name,description,archived',0,NULL,NULL),(2,1,'Veeam Backup GFS 7-3','**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',NULL,26,NULL,NULL,NULL,NULL,'2025-05-09 14:44:11',NULL,NULL,'services_id,reqs_ids',0,'1',NULL),(3,1,'Veeam Backup GFS 7-3','**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',9,26,NULL,NULL,NULL,NULL,'2025-05-09 14:44:47',NULL,NULL,'schedules_id',0,'1',NULL),(4,1,'Veeam Backup GFS 7-3','**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',9,26,NULL,NULL,'3,4,15,16,17,18,19,20,21,24,25',NULL,'2025-05-15 10:55:40',NULL,NULL,'comps_ids',0,'1',NULL),(5,1,'Veeam Backup GFS 7-3-0','**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm730.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',9,26,NULL,NULL,'3,4,15,16,17,18,19,20,21,24,25',NULL,'2025-05-25 16:04:32',NULL,NULL,'name,description',0,'1',NULL),(6,2,'Veeam Backup GFS 7-3-3','**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3-3** по субботам и инкрементное по вс-пт в 20:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm733.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии, 3 ежегодных) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',NULL,26,NULL,NULL,NULL,NULL,'2025-05-25 16:05:43',NULL,NULL,'name,description,services_id,archived,reqs_ids',0,'2',NULL),(7,1,'Veeam Backup GFS 7-3-0','**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm730.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',9,26,NULL,NULL,'3,4,15,16,17,18,19,20,21,24,25',NULL,'2025-05-26 16:30:30',NULL,NULL,'parent_id',0,'1',2),(8,1,'Veeam Backup GFS 7-3-0','**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm730.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',9,26,NULL,NULL,'3,4,15,16,17,18,19,20,21,24,25',NULL,'2025-05-26 17:14:53',NULL,NULL,'parent_id',0,'1',NULL),(9,2,'Veeam Backup GFS 7-3-3','`PARENT`\r\n**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3-3** по субботам и инкрементное по вс-пт в 20:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm733.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии, 3 ежегодных) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',NULL,NULL,NULL,NULL,NULL,NULL,'2025-05-26 17:35:15',NULL,NULL,'description,services_id,reqs_ids,parent_id',0,NULL,1),(10,2,'Veeam Backup GFS 7-3-3','`PARENT`\r\n\r\n**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3-3** по субботам и инкрементное по вс-пт в 20:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm733.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии, 3 ежегодных) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',NULL,NULL,NULL,NULL,NULL,NULL,'2025-05-27 11:45:46',NULL,NULL,'description',0,NULL,1),(11,2,'Veeam Backup GFS 7-3-3','`PARENT`\r\n----\r\n**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3-3** по субботам и инкрементное по вс-пт в 20:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm733.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии, 3 ежегодных) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',NULL,NULL,NULL,NULL,NULL,NULL,'2025-05-27 11:48:16',NULL,NULL,'description',0,NULL,1),(12,3,'klg-vbr\\mail_7-0-3-0','Veeam Job klg-vbr\\mail_7-0-3-0\r\n`PARENT`\r\n',NULL,NULL,NULL,NULL,NULL,NULL,'2025-05-29 04:48:20',NULL,NULL,'name,description,archived,parent_id',0,NULL,1),(13,3,'klg-vbr\\mail_7-0-3-0','Veeam Job klg-vbr\\mail_7-0-3-0\r\n`PARENT`\r\n',NULL,NULL,NULL,NULL,'3,5',NULL,'2025-05-29 11:32:39',NULL,NULL,'comps_ids',0,NULL,1),(14,3,'klg-vbr\\mail_7-0-3-0','1111!!!',NULL,NULL,NULL,NULL,'1,2,4',NULL,'2025-05-29 17:34:34',NULL,NULL,'description,comps_ids',0,NULL,1),(15,1,'Veeam Backup GFS 7-3-0','0_о\r\n**Сервер выполнения** - msk-veeam. Выполняет полное резервное копирование серверов в задании **Backup GFS 7-3** по субботам и инкрементное по вс-пт в 22:00 МСК.  \r\n**Содержание бэкапа** - полный срез ВМ на момент создания снэпшота при бэкапе.  \r\n**Папка резервной копии** - \\\\msk-nas\\backups\\vm730.  \r\n**Прореживание** - осуществляется самим Veeam согласно политике GFS (7 ежедневных точек восстановления, 3 ежемесячных копии) в самом задании.  \r\n**Мониторинг** - ведется при помощи Zabbix проверкой статуса задания, если статус FAILED - зажигается триггер, оповещаются ответственные за резервное копирование.',9,26,NULL,NULL,'3,4,15,16,17,18,19,20,21,24,25',NULL,'2025-06-06 05:16:50',NULL,NULL,'description',0,'1',NULL);
/*!40000 ALTER TABLE `maintenance_jobs_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_jobs_in_comps`
--

DROP TABLE IF EXISTS `maintenance_jobs_in_comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_jobs_in_comps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comps_id` int DEFAULT NULL,
  `jobs_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_jobs_in_comps-m2m` (`comps_id`,`jobs_id`),
  KEY `maintenance_jobs_in_comps-comps_id` (`comps_id`),
  KEY `maintenance_jobs_in_comps-jobs_id` (`jobs_id`),
  CONSTRAINT `fk-maintenance_jobs_in_comps-comps_id` FOREIGN KEY (`comps_id`) REFERENCES `comps` (`id`),
  CONSTRAINT `fk-maintenance_jobs_in_comps-jobs_id` FOREIGN KEY (`jobs_id`) REFERENCES `maintenance_jobs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_jobs_in_comps`
--

LOCK TABLES `maintenance_jobs_in_comps` WRITE;
/*!40000 ALTER TABLE `maintenance_jobs_in_comps` DISABLE KEYS */;
INSERT INTO `maintenance_jobs_in_comps` VALUES (14,1,3),(15,2,3),(1,3,1),(2,4,1),(16,4,3),(3,15,1),(4,16,1),(5,17,1),(6,18,1),(7,19,1),(8,20,1),(9,21,1),(10,24,1),(11,25,1);
/*!40000 ALTER TABLE `maintenance_jobs_in_comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_jobs_in_services`
--

DROP TABLE IF EXISTS `maintenance_jobs_in_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_jobs_in_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `services_id` int DEFAULT NULL,
  `jobs_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_jobs_in_services-m2m` (`services_id`,`jobs_id`),
  KEY `maintenance_jobs_in_services-services_id` (`services_id`),
  KEY `maintenance_jobs_in_services-jobs_id` (`jobs_id`),
  CONSTRAINT `fk-maintenance_jobs_in_services-jobs_id` FOREIGN KEY (`jobs_id`) REFERENCES `maintenance_jobs` (`id`),
  CONSTRAINT `fk-maintenance_jobs_in_services-services_id` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_jobs_in_services`
--

LOCK TABLES `maintenance_jobs_in_services` WRITE;
/*!40000 ALTER TABLE `maintenance_jobs_in_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_jobs_in_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_jobs_in_techs`
--

DROP TABLE IF EXISTS `maintenance_jobs_in_techs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_jobs_in_techs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `techs_id` int DEFAULT NULL,
  `jobs_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_jobs_in_techs-m2m` (`techs_id`,`jobs_id`),
  KEY `maintenance_jobs_in_techs-techs_id` (`techs_id`),
  KEY `maintenance_jobs_in_techs-jobs_id` (`jobs_id`),
  CONSTRAINT `fk-maintenance_jobs_in_techs-jobs_id` FOREIGN KEY (`jobs_id`) REFERENCES `maintenance_jobs` (`id`),
  CONSTRAINT `fk-maintenance_jobs_in_techs-techs_id` FOREIGN KEY (`techs_id`) REFERENCES `techs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_jobs_in_techs`
--

LOCK TABLES `maintenance_jobs_in_techs` WRITE;
/*!40000 ALTER TABLE `maintenance_jobs_in_techs` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_jobs_in_techs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reqs`
--

DROP TABLE IF EXISTS `maintenance_reqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_reqs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_backup` tinyint(1) DEFAULT '0',
  `spread_comps` tinyint(1) DEFAULT '1',
  `spread_techs` tinyint(1) DEFAULT '1',
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_reqs-name` (`name`),
  KEY `maintenance_reqs-description` (`description`(768)),
  KEY `idx-maintenance_reqs-archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reqs`
--

LOCK TABLES `maintenance_reqs` WRITE;
/*!40000 ALTER TABLE `maintenance_reqs` DISABLE KEYS */;
INSERT INTO `maintenance_reqs` VALUES (1,'Бэкап ВМ 7/3/0','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежемесячных, 7 ежедневных точек восстановления**',1,1,0,NULL,'2025-05-25 16:02:54',NULL,NULL),(2,'Бэкап ВМ 7/3/3','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежегодных, 3 ежемесячных, 7 ежедневных точек восстановления**',1,1,0,NULL,'2025-05-25 16:03:37',NULL,NULL);
/*!40000 ALTER TABLE `maintenance_reqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reqs_history`
--

DROP TABLE IF EXISTS `maintenance_reqs_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_reqs_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `spread_comps` tinyint(1) DEFAULT NULL,
  `spread_techs` tinyint(1) DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `services_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comps_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `techs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `includes_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `included_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `jobs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_backup` tinyint(1) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_reqs_history-master_id` (`master_id`),
  KEY `maintenance_reqs_history-updated_by` (`updated_by`),
  KEY `maintenance_reqs_history-updated_at` (`updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reqs_history`
--

LOCK TABLES `maintenance_reqs_history` WRITE;
/*!40000 ALTER TABLE `maintenance_reqs_history` DISABLE KEYS */;
INSERT INTO `maintenance_reqs_history` VALUES (1,1,'Бэкап ВМ 7/3','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежемесячных, 7 ежедневных точек восстановления**',1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-05-09 14:33:30',NULL,NULL,'name,description,spread_comps,spread_techs,is_backup',1,NULL),(2,1,'Бэкап ВМ 7/3','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежемесячных, 7 ежедневных точек восстановления**',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'1','2025-05-09 14:44:11',NULL,NULL,'jobs_ids',1,NULL),(3,1,'Бэкап ВМ 7/3/0','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежемесячных, 7 ежедневных точек восстановления**',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'1','2025-05-25 16:02:54',NULL,NULL,'name',1,NULL),(4,2,'Бэкап ВМ 7/3/3','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежегодных, 3 ежемесячных, 7 ежедневных точек восстановления**',1,0,NULL,NULL,NULL,NULL,'1',NULL,NULL,'2025-05-25 16:03:37',NULL,NULL,'name,description,spread_comps,spread_techs,includes_ids,is_backup',1,NULL),(5,1,'Бэкап ВМ 7/3/0','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежемесячных, 7 ежедневных точек восстановления**',1,0,NULL,NULL,NULL,NULL,NULL,'2','1','2025-05-25 16:03:37',NULL,NULL,'included_ids',1,NULL),(6,2,'Бэкап ВМ 7/3/3','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежегодных, 3 ежемесячных, 7 ежедневных точек восстановления**',1,0,NULL,NULL,NULL,NULL,'1',NULL,'2','2025-05-25 16:05:43',NULL,NULL,'jobs_ids',1,NULL),(7,2,'Бэкап ВМ 7/3/3','Кратковременное хранение с ежедневным бэкапом за неделю  \r\n**стратегия GFS  - 3 ежегодных, 3 ежемесячных, 7 ежедневных точек восстановления**',1,0,NULL,NULL,NULL,NULL,'1',NULL,NULL,'2025-05-26 17:35:15',NULL,NULL,'jobs_ids',1,NULL);
/*!40000 ALTER TABLE `maintenance_reqs_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reqs_in_comps`
--

DROP TABLE IF EXISTS `maintenance_reqs_in_comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_reqs_in_comps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reqs_id` int DEFAULT NULL,
  `comps_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_reqs_in_comps-m2m` (`reqs_id`,`comps_id`),
  KEY `maintenance_reqs_in_comps-reqs_id` (`reqs_id`),
  KEY `maintenance_reqs_in_comps-comps_id` (`comps_id`),
  CONSTRAINT `fk-maintenance_reqs_in_comps-comps_id` FOREIGN KEY (`comps_id`) REFERENCES `comps` (`id`),
  CONSTRAINT `fk-maintenance_reqs_in_comps-reqs_id` FOREIGN KEY (`reqs_id`) REFERENCES `maintenance_reqs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reqs_in_comps`
--

LOCK TABLES `maintenance_reqs_in_comps` WRITE;
/*!40000 ALTER TABLE `maintenance_reqs_in_comps` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_reqs_in_comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reqs_in_jobs`
--

DROP TABLE IF EXISTS `maintenance_reqs_in_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_reqs_in_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reqs_id` int DEFAULT NULL,
  `jobs_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_reqs_in_jobs-m2m` (`reqs_id`,`jobs_id`),
  KEY `maintenance_reqs_in_jobs-reqs_id` (`reqs_id`),
  KEY `maintenance_reqs_in_jobs-jobs_id` (`jobs_id`),
  CONSTRAINT `fk-maintenance_reqs_in_jobs-jobs_id` FOREIGN KEY (`jobs_id`) REFERENCES `maintenance_jobs` (`id`),
  CONSTRAINT `fk-maintenance_reqs_in_jobs-reqs_id` FOREIGN KEY (`reqs_id`) REFERENCES `maintenance_reqs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reqs_in_jobs`
--

LOCK TABLES `maintenance_reqs_in_jobs` WRITE;
/*!40000 ALTER TABLE `maintenance_reqs_in_jobs` DISABLE KEYS */;
INSERT INTO `maintenance_reqs_in_jobs` VALUES (10,1,1);
/*!40000 ALTER TABLE `maintenance_reqs_in_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reqs_in_reqs`
--

DROP TABLE IF EXISTS `maintenance_reqs_in_reqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_reqs_in_reqs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reqs_id` int DEFAULT NULL,
  `includes_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_reqs_in_reqs-m2m` (`reqs_id`,`includes_id`),
  KEY `maintenance_reqs_in_reqs-reqs_id` (`reqs_id`),
  KEY `maintenance_reqs_in_reqs-includes_id` (`includes_id`),
  CONSTRAINT `fk-maintenance_reqs_in_reqs-includes_id` FOREIGN KEY (`includes_id`) REFERENCES `maintenance_reqs` (`id`),
  CONSTRAINT `fk-maintenance_reqs_in_reqs-reqs_id` FOREIGN KEY (`reqs_id`) REFERENCES `maintenance_reqs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reqs_in_reqs`
--

LOCK TABLES `maintenance_reqs_in_reqs` WRITE;
/*!40000 ALTER TABLE `maintenance_reqs_in_reqs` DISABLE KEYS */;
INSERT INTO `maintenance_reqs_in_reqs` VALUES (1,2,1);
/*!40000 ALTER TABLE `maintenance_reqs_in_reqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reqs_in_services`
--

DROP TABLE IF EXISTS `maintenance_reqs_in_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_reqs_in_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reqs_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_reqs_in_services-m2m` (`reqs_id`,`services_id`),
  KEY `maintenance_reqs_in_services-reqs_id` (`reqs_id`),
  KEY `maintenance_reqs_in_services-services_id` (`services_id`),
  CONSTRAINT `fk-maintenance_reqs_in_services-reqs_id` FOREIGN KEY (`reqs_id`) REFERENCES `maintenance_reqs` (`id`),
  CONSTRAINT `fk-maintenance_reqs_in_services-services_id` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reqs_in_services`
--

LOCK TABLES `maintenance_reqs_in_services` WRITE;
/*!40000 ALTER TABLE `maintenance_reqs_in_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_reqs_in_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reqs_in_techs`
--

DROP TABLE IF EXISTS `maintenance_reqs_in_techs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_reqs_in_techs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reqs_id` int DEFAULT NULL,
  `techs_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_reqs_in_techs-m2m` (`reqs_id`,`techs_id`),
  KEY `maintenance_reqs_in_techs-reqs_id` (`reqs_id`),
  KEY `maintenance_reqs_in_techs-techs_id` (`techs_id`),
  CONSTRAINT `fk-maintenance_reqs_in_techs-reqs_id` FOREIGN KEY (`reqs_id`) REFERENCES `maintenance_reqs` (`id`),
  CONSTRAINT `fk-maintenance_reqs_in_techs-techs_id` FOREIGN KEY (`techs_id`) REFERENCES `techs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reqs_in_techs`
--

LOCK TABLES `maintenance_reqs_in_techs` WRITE;
/*!40000 ALTER TABLE `maintenance_reqs_in_techs` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_reqs_in_techs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manufacturers`
--

DROP TABLE IF EXISTS `manufacturers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manufacturers` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=544 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Производители ПО и железа';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manufacturers`
--

LOCK TABLES `manufacturers` WRITE;
/*!40000 ALTER TABLE `manufacturers` DISABLE KEYS */;
INSERT INTO `manufacturers` VALUES (1,'Logitech','Logitech International S.A.','Производитель компьютерной периферии','2023-08-28 12:52:06',NULL),(2,'AMD','Advanced Micro Devices, Inc.','','2023-08-28 12:52:06',NULL),(3,'Microsoft','Microsoft corporation','Микрософт','2023-08-28 12:52:06',NULL),(4,'Oracle','Oracle corporation','производители джавы и баз данных','2023-08-28 12:52:06',NULL),(5,'GIMP team','GIMP team','Команда разработчиков GIMP','2023-08-28 12:52:06',NULL),(6,'Far Group','Far Group','','2023-08-28 12:52:06',NULL),(7,'Google','Google Inc.','','2023-08-28 12:52:06',NULL),(8,'Mozilla','Mozilla Corporation','Разработчики движка Gecko, и продуктов на нем (Firefox, Thunderbird, etc.)','2023-08-28 12:52:06',NULL),(9,'VMware','VMware, Inc.','','2023-08-28 12:52:06',NULL),(10,'pdfforge','pdfforge GmbH','','2023-08-28 12:52:06',NULL),(11,'Adobe','Adobe Systems Incorporated','','2023-08-28 12:52:06',NULL),(12,'Nullsoft','Nullsoft, Inc','Разработчики Winamp и Nullsoft installer','2023-08-28 12:52:06',NULL),(13,'Audacity Team','Audacity Team','Команда разработчиков Audacity','2023-08-28 12:52:06',NULL),(14,'Igor Pavlov','Игорь Павлов','Разработчик 7zip','2023-08-28 12:52:06',NULL),(15,'Notepad++ Team','Notepad++ Team','Команда разработчиков Notepad++','2023-08-28 12:52:06',NULL),(16,'Realtek','Realtek Semiconductor Corp.','','2023-08-28 12:52:06',NULL),(17,'3CX','3CX','Производитель VoIP решений','2023-08-28 12:52:06',NULL),(18,'Siemens','Siemens AG','','2023-08-28 12:52:06',NULL),(19,'OpenVPN Technologies','OpenVPN Technologies, Inc.','','2023-08-28 12:52:06',NULL),(20,'Avaya','Avaya Inc.','','2023-08-28 12:52:06',NULL),(21,'Intel','Intel corporation','Производитель железа и ПО','2023-08-28 12:52:06',NULL),(22,'Citrix','Citrix Systems, Inc.','','2023-08-28 12:52:06',NULL),(23,'qBittorrent proj.','The qBittorrent project','','2023-08-28 12:52:06',NULL),(24,'GlavSoft','GlavSoft LLC.','Разработчик TightVNC','2023-08-28 12:52:06',NULL),(25,'SmartBear Software','SmartBear Software','Разработчик SoapUI','2023-08-28 12:52:06',NULL),(26,'Imperative Software','Imperative Software Pty Ltd','Разработчик InputDirector','2023-08-28 12:52:06',NULL),(27,'Фаматек','Фаматек','','2023-08-28 12:52:06',NULL),(28,'TeamViewer','TeamViewer','','2023-08-28 12:52:06',NULL),(29,'Tarifer.ru','Tarifer.ru','','2023-08-28 12:52:06',NULL),(30,'VideoLAN','VideoLAN Organization','','2023-08-28 12:52:06',NULL),(31,'Symantec','Symantec Corporation','','2023-08-28 12:52:06',NULL),(32,'Nvidia','NVIDIA Corporation','','2023-08-28 12:52:06',NULL),(33,'LunarG','LunarG, Inc.','LunarG is the developer of the LunarG® Vulkan® SDK for Windows® and Linux operating systems.','2023-08-28 12:52:06',NULL),(34,'Andrew Zhezherun','Андрей Жежерун','Разработчик WinDjView','2023-08-28 12:52:06',NULL),(35,'HP','Hewlett-Packard','','2023-08-28 12:52:06',NULL),(36,'SyncTrayzor dev Team','SyncTrayzor dev Team','','2023-08-28 12:52:06',NULL),(37,'TechSmith','TechSmith Corporation','','2023-08-28 12:52:06',NULL),(38,'389 Project','389 Project','','2023-08-28 12:52:06',NULL),(39,'SolarWinds','SolarWinds Inc.','Разработчик систем мониторинга и удаленного управления','2023-08-28 12:52:06',NULL),(40,'pdf24.org','www.pdf24.org','','2023-08-28 12:52:06',NULL),(41,'KLCP','KLCP Team','','2023-08-28 12:52:06',NULL),(42,'LDAPSoft','LDAPSoft','','2023-08-28 12:52:06',NULL),(43,'Dia developers','Dia developers','','2023-08-28 12:52:06',NULL),(44,'Krzysztof Kowalczyk','Krzysztof Kowalczyk','https://blog.kowalczyk.info/resume.html','2023-08-28 12:52:06',NULL),(45,'StarWind','StarWind Software','','2023-08-28 12:52:06',NULL),(46,'XiaoMi','Xiaomi Inc.','小米科技','2023-08-28 12:52:06',NULL),(47,'Altium','Altium Limited','','2023-08-28 12:52:06',NULL),(48,'iSpring','iSpring Solutions Inc.','','2023-08-28 12:52:06',NULL),(49,'Open Design Alliance','Open Design Alliance','некоммерческое объединение (консорциум) компаний-производителей программного обеспечения','2023-08-28 12:52:06',NULL),(50,'OCS Inventory NG','OCS Inventory NG Team','','2023-08-28 12:52:06',NULL),(51,'Foxit','Foxit Software, Inc','','2023-08-28 12:52:06',NULL),(52,'Futuremark','Futuremark Corporation','Производитель бенчмарков','2023-08-28 12:52:06',NULL),(53,'Prolific Technology Inc.','Prolific Technology Inc.','','2023-08-28 12:52:06',NULL),(54,'DameWare','DameWare','','2023-08-28 12:52:06',NULL),(55,'ASUS','AsusTek Computer Inc.','','2023-08-28 12:52:06',NULL),(56,'The Document Foundation','The Document Foundation','','2023-08-28 12:52:06',NULL),(57,'АСКОН','АСКОН','','2023-08-28 12:52:06',NULL),(58,'CDBurnerXP','CDBurnerXP','','2023-08-28 12:52:06',NULL),(59,'AIMP DevTeam','AIMP DevTeam','','2023-08-28 12:52:06',NULL),(60,'Nokia','Nokia','','2023-08-28 12:52:06',NULL),(61,'Simon Tatham','Simon Tatham','Разработчик Putty','2023-08-28 12:52:06',NULL),(62,'Borland','Borland Software Corporation','','2023-08-28 12:52:06',NULL),(63,'Bitrix','Bitrix, Inc','','2023-08-28 12:52:06',NULL),(64,'TortoiseSVN','TortoiseSVN dev. team','','2023-08-28 12:52:06',NULL),(65,'Python Software Foundation','Python Software Foundation','','2023-08-28 12:52:06',NULL),(66,'Colin Harrison','Colin Harrison','','2023-08-28 12:52:06',NULL),(67,'Saturn PCB Design','Saturn PCB Design, Inc','www.saturnpcb.com','2023-08-28 12:52:06',NULL),(68,'Juergen Riegel','Juergen Riegel','','2023-08-28 12:52:06',NULL),(69,'GNU Octave','GNU Octave Dev Team','','2023-08-28 12:52:06',NULL),(70,'Яндекс','Яндекс','','2023-08-28 12:52:06',NULL),(71,'22 ЦНИИИ МО РФ','ФГУП 22 ЦНИИИ МО РФ','','2023-08-28 12:52:06',NULL),(72,'Moxa','Moxa Inc.','','2023-08-28 12:52:06',NULL),(73,'inkscape.org','inkscape.org','','2023-08-28 12:52:06',NULL),(74,'Arduino LLC','Arduino LLC','Разработчики одноименного одноплатного компьютера','2023-08-28 12:52:06',NULL),(75,'LibreCAD Team','LibreCAD Team','','2023-08-28 12:52:06',NULL),(76,'Aktiv Co.','Aktiv Co.','Компания \"Актив\"','2023-08-28 12:52:06',NULL),(77,'Струнов В.В.','Струнов Вячеслав Владимирович','http://nc-corrector.inf.ua/index.htm','2023-08-28 12:52:06',NULL),(78,'Rohde & Schwarz','Rohde & Schwarz','Международная компания, работающая в таких направлениях, как контрольно-измерительное оборудование, радиомониторинг и пеленгование, цифровое и аналоговое теле- и радиовещание, системы радиосвязи, защита информации и безопасность связи, специальные техниче','2023-08-28 12:52:06',NULL),(79,'Viber Media S.A.R.L','Viber Media Inc.','','2023-08-28 12:52:06',NULL),(80,'Unity Technologies ApS','Unity Technologies ApS','','2023-08-28 12:52:06',NULL),(81,'Mail.Ru','Mail.Ru LLC','','2023-08-28 12:52:06',NULL),(82,'Azimut','АО Азимут','$this','2023-08-28 12:52:06',NULL),(83,'Планар','ООО \"ПЛАНАР\"','','2023-08-28 12:52:06',NULL),(84,'Silicon Laboratories','Silicon Laboratories','Компания-производитель полупроводниковых компонентов. Центральный офис компании расположен в городе Остин, штат Техас','2023-08-28 12:52:06',NULL),(85,'Cadence Design Systems','Cadence Design Systems','','2023-08-28 12:52:06',NULL),(86,'Digitalcore','Digitalcore','','2023-08-28 12:52:06',NULL),(87,'Sauris','Sauris GmbH','','2023-08-28 12:52:06',NULL),(88,'VisualGPS','VisualGPS LLC','','2023-08-28 12:52:06',NULL),(89,'Rafal Powierski','Rafal Powierski','https://ru.zofzpcb.com/','2023-08-28 12:52:06',NULL),(90,'PentaLogix','PentaLogix','','2023-08-28 12:52:06',NULL),(91,'AkelSoft','AkelSoft','','2023-08-28 12:52:06',NULL),(92,'Cadence Design','Cadence Design Systems, Inc.','','2023-08-28 12:52:06',NULL),(93,'LiteManager Team','LiteManager Team','','2023-08-28 12:52:06',NULL),(94,'MediaTek','MediaTek, Inc.','','2023-08-28 12:52:06',NULL),(95,'СKБ Контур','ЗАО «ПФ «CKБ Контур»','','2023-08-28 12:52:06',NULL),(96,'Firebird Project','Firebird Project','','2023-08-28 12:52:06',NULL),(97,'Крипто-ПРО','Компания КРИПТО-ПРО','','2023-08-28 12:52:06',NULL),(98,'VIA Technologies','VIA Technologies, Inc.','','2023-08-28 12:52:06',NULL),(99,'KYOCERA','KYOCERA Document Solutions Inc.','','2023-08-28 12:52:06',NULL),(100,'ФНС России','ФГУП ГНИВЦ ФНС России','','2023-08-28 12:52:06',NULL),(101,'SolidWorks','SolidWorks corp.','','2023-08-28 12:52:06',NULL),(102,'Интермех','Компания Интермех','','2023-08-28 12:52:06',NULL),(103,'Altera','Altera corp.','Altera — один из крупнейших разработчиков ASIC, программируемых логических интегральных схем (ПЛИС), была основана в 1983 г.','2023-08-28 12:52:06',NULL),(104,'Christian Werner','Christian Werner','http://www.ch-werner.de/','2023-08-28 12:52:06',NULL),(105,'Rogers Corporation','Rogers Corporation','продавцы ВЧ материала','2023-08-28 12:52:06',NULL),(106,'3Dconnexion','3Dconnexion','Производитель 3Д манипуляторов для САПР систем','2023-08-28 12:52:06',NULL),(107,'Softland','Softland','','2023-08-28 12:52:06',NULL),(108,'Andrea Vacondio','Andrea Vacondio','','2023-08-28 12:52:06',NULL),(109,'forum.Ru-Board.com','http://forum.ru-board.com','Всякие сборки от Васяна с руборды','2023-08-28 12:52:06',NULL),(110,'Ростелеком','ПАО Ростелеком','','2023-08-28 12:52:06',NULL),(111,'Renesas Electronics','Renesas Electronics Corporation','Японский производитель полупроводниковых компонентов. Штаб-квартира находится в Токио, компания имеет представительства и производственные мощности более чем в 20 странах.','2023-08-28 12:52:06',NULL),(112,'Canon','Canon Inc.','','2023-08-28 12:52:06',NULL),(113,'STDUtility','STDUtility','Разработчик STDU Viewer','2023-08-28 12:52:06',NULL),(114,'CollabNet','CollabNet','','2023-08-28 12:52:06',NULL),(115,'Gougelet Pierre-e','Gougelet Pierre-e','','2023-08-28 12:52:06',NULL),(116,'Samsung','Samsung','','2023-08-28 12:52:06',NULL),(117,'DIGITEO','DIGITEO','','2023-08-28 12:52:06',NULL),(118,'Guardant','Guardant','','2023-08-28 12:52:06',NULL),(119,'Авторская группа разработчиков АВС','Авторская группа разработчиков АВС','http://www.abccenter.ru/pages/about/about_partners.php','2023-08-28 12:52:06',NULL),(120,'SafeNet','SafeNet Inc.','','2023-08-28 12:52:06',NULL),(121,'Riverbed Technology','Riverbed Technology, Inc.','','2023-08-28 12:52:06',NULL),(122,'Wireshark dev community','The Wireshark developer community','https://www.wireshark.org','2023-08-28 12:52:06',NULL),(123,'FTDI','Future Technology Devices International',' шотландская частная компания, торгующая полупроводниковыми устройствами. Специализируется в области связанной с шиной USB.','2023-08-28 12:52:06',NULL),(124,'dotPDN','dotPDN LLC','','2023-08-28 12:52:06',NULL),(125,'CPUID','CPUID','','2023-08-28 12:52:06',NULL),(126,'Antenna House','Antenna House','','2023-08-28 12:52:06',NULL),(127,'JustSystems Canada, Inc.','JustSystems Canada, Inc.','','2023-08-28 12:52:06',NULL),(128,'SAP','SAP SE','SAP AG до 2014г','2023-08-28 12:52:06',NULL),(129,'Dassault Systèmes','Dassault Systèmes SE','','2023-08-28 12:52:06',NULL),(130,'ParallelGraphics','ParallelGraphics','','2023-08-28 12:52:06',NULL),(131,'ДубльГИС','ООО \"ДубльГИС\"','','2023-08-28 12:52:06',NULL),(132,'Cisco WebEx','Cisco WebEx LLC','Компания, разрабатывающая инструменты для проведения интерактивных онлайн-конференций, совещаний и трансляций. ','2023-08-28 12:52:06',NULL),(133,'Peter Pawlowski','Peter Pawlowski','','2023-08-28 12:52:06',NULL),(134,'CutePDF.com','CutePDF.com','','2023-08-28 12:52:06',NULL),(135,'Медиа Мир','ООО \"Медиа Мир\"','','2023-08-28 12:52:06',NULL),(136,'Vargus','Vargus Ltd','Производитель инструмента','2023-08-28 12:52:06',NULL),(137,'DVDVideoSoft','DVDVideoSoft Ltd.','','2023-08-28 12:52:06',NULL),(138,'Fraisa SA','Fraisa SA','','2023-08-28 12:52:06',NULL),(139,'Компания Digt','Компания Digt','','2023-08-28 12:52:06',NULL),(140,'Contex','Contex','Производитель оборудования (не презервативов)','2023-08-28 12:52:06',NULL),(141,'Dimitri van Heesch','Dimitri van Heesch','','2023-08-28 12:52:06',NULL),(142,'Indigo Byte Systems, LLC','Indigo Byte Systems, LLC','','2023-08-28 12:52:06',NULL),(143,'Martin Prikryl','Martin Prikryl','','2023-08-28 12:52:06',NULL),(144,'Bruno Pagès','Bruno Pagès','','2023-08-28 12:52:06',NULL),(145,'jrsoftware.org','jrsoftware.org','','2023-08-28 12:52:06',NULL),(146,'Digia','Digia Plc','Финский системный интегратор и разработчик программного обеспечения. Акции Digia котируются на фондовой бирже Хельсинки NASDAQ OMX Helsinki. Офисы компании расположены в Финляндии, Швеции, Норвегии, Германии, США, Китае и России. Компания основана в 1990 ','2023-08-28 12:52:06',NULL),(147,'Plastic Software','Plastic Software, Inc.','','2023-08-28 12:52:06',NULL),(148,'MPC-HC Team','MPC-HC Team','Разработчик Mediaplayer Classic - Home Cinema','2023-08-28 12:52:06',NULL),(149,'Rainlendar.net','Rainlendar.net','','2023-08-28 12:52:06',NULL),(150,'Model Technology','Model Technology','','2023-08-28 12:52:06',NULL),(151,'ABBYY','ABBYY','Разработчик Finereader','2023-08-28 12:52:06',NULL),(152,'Texas Instruments','Texas Instruments, Inc.','','2023-08-28 12:52:06',NULL),(153,'Michael Golikov','Michael Golikov','','2023-08-28 12:52:06',NULL),(154,'Apple','Apple Inc.','','2023-08-28 12:52:06',NULL),(155,'IVI Foundation','Interchangeable Virtual Instruments Foundation','','2023-08-28 12:52:06',NULL),(156,'NVisionGroup','NVisionGroup','«Энвижн Груп» - одна из крупнейших российских ИТ-компаний, ведущий разработчик и поставщик информационно-коммуникационных решений, услуг и сервисов.','2023-08-28 12:52:06',NULL),(157,'MosChip','MosChip Semiconductor Technology','MosChip is a complete engineering solution consulting company with over 16+ years of extensive expertise in SEMICONDUCTOR / SYSTEMS / IoT engineering from SoC (Systems on Chip), Embedded Systems Design, Cloud and Mobile Software development catering to th','2023-08-28 12:52:06',NULL),(158,'Agilent','Agilent Technologies, Inc.','Agilent Technologies, или Agilent, американская компания-производитель измерительного оборудования, электронно-медицинского оборудования и оборудования для химического анализа.','2023-08-28 12:52:06',NULL),(159,'ЗАО НИИИТ-РТС','ЗАО НИИИТ-РТС','','2023-08-28 12:52:06',NULL),(160,'Autodesk','Autodesk','','2023-08-28 12:52:06',NULL),(161,'FARO','FARO Scanner Production','is the world’s most trusted source for 3D measurement, imaging and realization technology.','2023-08-28 12:52:06',NULL),(162,'No Name','Безымянные и не определяющиеся производители','А как без этого','2023-08-28 12:52:06',NULL),(163,'WM Transfer','WM Transfer Ltd.','Компания WM Transfer Ltd — владелец и администратор системы расчетов WebMoney Transfer. Система WebMoney Transfer существует с 1998 года.','2023-08-28 12:52:06',NULL),(164,'MetaCreations','MetaCreations','','2023-08-28 12:52:06',NULL),(165,'CJSC Computing Forces','CJSC Computing Forces','','2023-08-28 12:52:06',NULL),(166,'Softomate','Softomate','','2023-08-28 12:52:06',NULL),(167,'TortoiseGit','TortoiseGit','','2023-08-28 12:52:06',NULL),(168,'AlterGeo','AlterGeo','','2023-08-28 12:52:06',NULL),(169,'Sony','Sony','','2023-08-28 12:52:06',NULL),(170,'Marek Jasinski','Marek Jasinski','','2023-08-28 12:52:06',NULL),(171,'Piriform','Piriform','','2023-08-28 12:52:06',NULL),(172,'Thingamahoochie Software','Thingamahoochie Software','','2023-08-28 12:52:06',NULL),(173,'Florian Heidenreich','Florian Heidenreich','','2023-08-28 12:52:06',NULL),(174,'ПАО Сбербанк','ПАО Сбербанк','','2023-08-28 12:52:06',NULL),(175,'isotousb.com','isotousb.com','','2023-08-28 12:52:06',NULL),(176,'Steve Borho and others','Steve Borho and others','','2023-08-28 12:52:06',NULL),(177,'FileZilla Project','FileZilla Project','','2023-08-28 12:52:06',NULL),(178,'BitTorrent Inc.','BitTorrent Inc.','','2023-08-28 12:52:06',NULL),(179,'IAR Systems','IAR Systems','','2023-08-28 12:52:06',NULL),(180,'Cisco','Cisco Systems, Inc.','','2023-08-28 12:52:06',NULL),(181,'Famatech','Famatech Advanced IP Scanner','','2023-08-28 12:52:06',NULL),(182,'PERCo','PERCo','','2023-08-28 12:52:06',NULL),(183,'Ashampoo','Ashampoo GmbH & Co. KG','','2023-08-30 05:39:15','reviakin.a'),(184,'Gerhard Zehetbauer','Gerhard Zehetbauer','','2023-08-28 12:52:06',NULL),(185,'WestByte','WestByte','','2023-08-28 12:52:06',NULL),(186,'Axis','Axis Communications AB','','2023-08-28 12:52:06',NULL),(187,'Gigabyte','Gigabyte Technology Co., Ltd.','Производитель материнских плат','2023-08-28 12:52:06',NULL),(188,'IGC','IGC','','2023-08-28 12:52:06',NULL),(189,'АО ГНИВЦ','АО ГНИВЦ','','2023-08-28 12:52:06',NULL),(190,'Центр ГРАНД','Центр ГРАНД','','2023-08-28 12:52:06',NULL),(191,'A4TECH','A4TECH','','2023-08-28 12:52:06',NULL),(192,'Nanosoft','Nanosoft','','2023-08-28 12:52:06',NULL),(193,'Lenovo ','Lenovo ','','2023-08-28 12:52:06',NULL),(194,'FreeFileSync.org','FreeFileSync.org','','2023-08-28 12:52:06',NULL),(195,'SolidDocuments','SolidDocuments','','2023-08-28 12:52:06',NULL),(196,'XMind Ltd.','XMind Ltd.','','2023-08-28 12:52:06',NULL),(197,'SyncRO Soft','SyncRO Soft','','2023-08-28 12:52:06',NULL),(198,'Design Science, Inc.','Design Science, Inc.','','2023-08-28 12:52:06',NULL),(199,'Benito van der Zander','Benito van der Zander','','2023-08-28 12:52:06',NULL),(200,'Andrey Ivashov','Andrey Ivashov','','2023-08-28 12:52:06',NULL),(201,'Pandora TV','©7sh3. (Сборка от 13.04.2015)','','2023-08-28 12:52:06',NULL),(202,'КРЕДО-ДИАЛОГ','КРЕДО-ДИАЛОГ','','2023-08-28 12:52:06',NULL),(203,'NEC','NEC Corporation','日本電気株式会社','2023-08-28 12:52:06',NULL),(204,'SHARP','SHARP','','2023-08-28 12:52:06',NULL),(205,'DIAL GmbH','DIAL GmbH','','2023-08-28 12:52:06',NULL),(206,'Oce-Technologies','Oce-Technologies','','2023-08-28 12:52:06',NULL),(207,'ФГУП СОНИИР','ФГУП СОНИИР','','2023-08-28 12:52:06',NULL),(208,'Tracker Software','Tracker Software','','2023-08-28 12:52:06',NULL),(209,'win.rar GmbH','win.rar GmbH','','2023-08-28 12:52:06',NULL),(210,'ProjectLibre','ProjectLibre','','2023-08-28 12:52:06',NULL),(211,'Vatra','Vatra','','2023-08-28 12:52:06',NULL),(212,'GALAD','GALAD','','2023-08-28 12:52:06',NULL),(213,'OSRAM','OSRAM','','2023-08-28 12:52:06',NULL),(214,'Lighting Technologies','Lighting Technologies','','2023-08-28 12:52:06',NULL),(215,'EXP Systems LLC','EXP Systems LLC','','2023-08-28 12:52:06',NULL),(216,'EleWise','EleWise','','2023-08-28 12:52:06',NULL),(217,'НПО ПАС','НПО ПАС','','2023-08-28 12:52:06',NULL),(218,'Компания ИнСАТ','Компания ИнСАТ','','2023-08-28 12:52:06',NULL),(219,'HTC','HTC Corporation','','2023-08-28 12:52:06',NULL),(220,'Стройэкспертиза','Стройэкспертиза','','2023-08-28 12:52:06',NULL),(221,'Disc Soft','Disc Soft Ltd','','2023-08-30 13:02:52','reviakin.a'),(222,'Mindjet LLC','Mindjet LLC','','2023-08-28 12:52:06',NULL),(223,'Accmeware Corporation','Accmeware Corporation','','2023-08-28 12:52:06',NULL),(224,'SlySoft','SlySoft','Теперь это компания RedFox Project','2023-08-28 12:52:06',NULL),(225,'CodeGear','CodeGear','','2023-08-28 12:52:06',NULL),(226,'Opera Software ASA','Opera Software ASA','','2023-08-28 12:52:06',NULL),(227,'PDF Creator','PDF Creator','','2023-08-28 12:52:06',NULL),(228,'Ritlabs, SRL','Ritlabs, SRL','','2023-08-28 12:52:06',NULL),(229,'Lazarus Team','Lazarus Team','','2023-08-28 12:52:06',NULL),(230,'PandoraTV','PandoraTV','','2023-08-28 12:52:06',NULL),(231,'EaseUS','EaseUS','','2023-08-28 12:52:06',NULL),(232,'ООО \"Лидер\"','ООО \"Лидер\"','','2023-08-28 12:52:06',NULL),(233,'SCAD Soft','SCAD Soft','','2023-08-28 12:52:06',NULL),(234,'Bizagi Limited','Bizagi Limited','','2023-08-28 12:52:06',NULL),(235,'Toshiba','Toshiba','','2023-08-28 12:52:06',NULL),(236,'AGG Software','AGG Software','','2023-08-28 12:52:06',NULL),(237,'ICQ','ICQ','','2023-08-28 12:52:06',NULL),(238,'Acro Software Inc.','Acro Software Inc.','','2023-08-28 12:52:06',NULL),(239,'LIGHTNING UK!','LIGHTNING UK!','','2023-08-28 12:52:06',NULL),(240,'CIMCO Integration I/S','CIMCO Integration I/S','','2023-08-28 12:52:06',NULL),(241,'Spigot, Inc.','Spigot, Inc.','потенциально нежелательная программа','2023-08-28 12:52:06',NULL),(242,'BlueStack Systems, Inc.','BlueStack Systems, Inc.','','2023-08-28 12:52:06',NULL),(243,'Unitronics','Unitronics','unitronics.com','2023-08-28 12:52:06',NULL),(244,'thecybershadow.net','thecybershadow.net','','2023-08-28 12:52:06',NULL),(245,'ARM Ltd','ARM Ltd','','2023-08-28 12:52:06',NULL),(246,'GE_Healthcare','GE_Healthcare','','2023-08-28 12:52:06',NULL),(247,'Stealth Software','Stealth Software','','2023-08-28 12:52:06',NULL),(248,'Vargus LTD.','Vargus LTD.','','2023-08-28 12:52:06',NULL),(249,'JetBrains','JetBrains s.r.o.','','2023-08-28 12:52:06',NULL),(250,'Blender Foundation','The Blender Foundation','www.blender.org','2023-08-28 12:52:06',NULL),(251,'Scendix Software-Vertriebsges. mbH','Scendix Software-Vertriebsges. mbH','','2023-08-28 12:52:06',NULL),(252,'ImageWriter Developers','ImageWriter Developers','','2023-08-28 12:52:06',NULL),(253,'1C','1C','','2023-08-28 12:52:06',NULL),(254,'Helmut Buhler','Helmut Buhler','','2023-08-28 12:52:06',NULL),(255,'Apache Software Foundation','Apache Software Foundation','','2023-08-28 12:52:06',NULL),(256,'Sober Lemur S.a.s. di Vacondio Andrea','Sober Lemur S.a.s. di Vacondio Andrea','','2023-08-28 12:52:06',NULL),(257,'NiceKit','NiceKit','','2023-08-28 12:52:06',NULL),(258,'Informatic Corp','Informatic Corp','','2023-08-28 12:52:06',NULL),(259,'PCB Matrix Corp.','PCB Matrix Corp.','','2023-08-28 12:52:06',NULL),(260,'Corel','Corel Corporation','','2023-08-30 11:39:31','reviakin.a'),(261,'Texas Instruments Inc.','Texas Instruments Inc.','','2023-08-28 12:52:06',NULL),(262,'STMicroelectronics','STMicroelectronics','','2023-08-28 12:52:06',NULL),(263,'Segger','Segger','','2023-08-28 12:52:06',NULL),(264,'CamStudio Open Source','CamStudio Open Source','','2023-08-28 12:52:06',NULL),(265,'Atmel','Atmel','','2023-08-28 12:52:06',NULL),(266,'dxfviewer.com','dxfviewer.com','','2023-08-28 12:52:06',NULL),(267,'libusb-win32','libusb-win32','','2023-08-28 12:52:06',NULL),(268,'Megaify Software','Megaify Software','','2023-08-28 12:52:06',NULL),(269,'Linear Technology Corporation','Linear Technology Corporation','','2023-08-28 12:52:06',NULL),(270,'Tomasz Mon','Tomasz Mon','','2023-08-28 12:52:06',NULL),(271,'AutoDWG','AutoDWG','','2023-08-28 12:52:06',NULL),(272,'Blackhawk','Blackhawk','','2023-08-28 12:52:06',NULL),(273,'AVAST Software','AVAST Software','','2023-08-28 12:52:06',NULL),(274,'ARM Holdings','ARM Holdings','','2023-08-28 12:52:06',NULL),(275,'Liviu Ionescu','Liviu Ionescu','http://gnu-mcu-eclipse.github.io','2023-08-28 12:52:06',NULL),(276,'Catalina Group Ltd','Catalina Group Ltd','','2023-08-28 12:52:06',NULL),(277,'Christian Kindahl and others','Christian Kindahl and others','','2023-08-28 12:52:06',NULL),(278,'Nero','Nero','','2023-08-28 12:52:06',NULL),(279,'Christian Taubenheim','Christian Taubenheim','','2023-08-28 12:52:06',NULL),(280,'Avant Force','Avant Force','','2023-08-28 12:52:06',NULL),(281,'Xiph.Org','Xiph.Org','','2023-08-28 12:52:06',NULL),(282,'qip.ru','qip.ru','','2023-08-28 12:52:06',NULL),(283,'Elan Digital Systems Ltd','Elan Digital Systems Ltd','','2023-08-28 12:52:06',NULL),(284,'dmc','dmc','','2023-08-28 12:52:06',NULL),(285,'entechtaiwan.com','entechtaiwan.com','','2023-08-28 12:52:06',NULL),(286,'Riman company','Riman company','','2023-08-28 12:52:06',NULL),(287,'Embedded Systems Academy, Inc.','Embedded Systems Academy, Inc.','','2023-08-28 12:52:06',NULL),(288,'Microchip Technology Inc.','Microchip Technology Inc.','','2023-08-28 12:52:06',NULL),(289,'Mobile','Mobile','','2023-08-28 12:52:06',NULL),(290,'Rig Expert Ukraine Ltd.','Rig Expert Ukraine Ltd.','','2023-08-28 12:52:06',NULL),(291,'Kingston Digital, Inc','Kingston Digital, Inc','','2023-08-28 12:52:06',NULL),(292,'Phyton','Phyton','','2023-08-28 12:52:06',NULL),(293,'Alexey Nicolaychuk','Alexey Nicolaychuk','','2023-08-28 12:52:06',NULL),(294,'tcompressor.com','tcompressor.com','','2023-08-28 12:52:06',NULL),(295,'Simply Super Software','Simply Super Software','','2023-08-28 12:52:06',NULL),(296,'ООО «ТехноКом»','ООО «ТехноКом»','','2023-08-28 12:52:06',NULL),(297,'Trend Micro','Trend Micro','','2023-08-28 12:52:06',NULL),(298,'3DVIA','3DVIA','','2023-08-28 12:52:06',NULL),(299,'Power Integrations','Power Integrations','','2023-08-28 12:52:06',NULL),(300,'DMM','DMM','','2023-08-28 12:52:06',NULL),(301,'DSE Development','DSE Development','','2023-08-28 12:52:06',NULL),(302,'MiKTeX.org','MiKTeX.org','','2023-08-28 12:52:06',NULL),(303,'SEW-EURODRIVE GmbH & Co KG','SEW-EURODRIVE GmbH & Co KG','','2023-08-28 12:52:06',NULL),(304,'USBlyzer','USBlyzer.com','','2023-08-28 12:52:06',NULL),(305,'Philippe Jounin','Philippe Jounin','tftpd64.com','2023-08-28 12:52:06',NULL),(306,'JMICRON Technology Corp.','JMICRON Technology Corp.','','2023-08-28 12:52:06',NULL),(307,'The Qt Company Ltd','The Qt Company Ltd','','2023-08-28 12:52:06',NULL),(308,'OpenJUMP ','OpenJUMP ','','2023-08-28 12:52:06',NULL),(309,'Planoplan','Planoplan','','2023-08-28 12:52:06',NULL),(310,'XnSoft','XnSoft','','2023-08-28 12:52:06',NULL),(311,'WIBU-SYSTEMS AG','WIBU-SYSTEMS AG','','2023-08-28 12:52:06',NULL),(312,'Topcon','Topcon','','2023-08-28 12:52:06',NULL),(313,'WebM Project','WebM Project','','2023-08-28 12:52:06',NULL),(314,'Leica Geosystems','Leica Geosystems','','2023-08-28 12:52:06',NULL),(315,'Persistence of Vision Raytracer Pty. Ltd.','Persistence of Vision Raytracer Pty. Ltd.','','2023-08-28 12:52:06',NULL),(316,'Ibadov Tariel','Ibadov Tariel','','2023-08-28 12:52:06',NULL),(317,'Лаборатория Касперского','АО \"Лаборатория Касперского\"','','2023-08-28 12:52:06',NULL),(318,'IdeaMK','IdeaMK','','2023-08-28 12:52:06',NULL),(319,'LancOS','LancOS','','2023-08-28 12:52:06',NULL),(320,'Atheros','Atheros Communications Inc.','','2023-08-30 05:42:07','reviakin.a'),(321,'IVT Corporation','IVT Corporation','','2023-08-28 12:52:06',NULL),(322,'ATI Technologies','ATI Technologies','','2023-08-28 12:52:06',NULL),(323,'Evernote Corp.','Evernote Corp.','','2023-08-28 12:52:06',NULL),(324,'WinImage','WinImage','www.winimage.com','2023-08-28 12:52:06',NULL),(325,'NCH Software','NCH Software','','2023-08-28 12:52:06',NULL),(326,'Computerinsel GmbH','Computerinsel GmbH','pl32.com','2023-08-28 12:52:06',NULL),(327,'Michael Johnson','Michael Johnson','','2023-08-28 12:52:06',NULL),(328,'Sublime HQ Pty Ltd','Sublime HQ Pty Ltd','','2023-08-28 12:52:06',NULL),(329,'Cypress','Cypress','','2023-08-28 12:52:06',NULL),(330,'Trimble Navigation Limited','Trimble Navigation Limited','','2023-08-28 12:52:06',NULL),(331,'coocox.org','coocox.org','','2023-08-28 12:52:06',NULL),(332,'HDDGURU','HDDGURU','','2023-08-28 12:52:06',NULL),(333,'Hetman Software','Hetman Software','https://hetmanrecovery.com','2023-08-28 12:52:06',NULL),(334,'KDE','KDE','','2023-08-28 12:52:06',NULL),(335,'qutIM','qutIM','','2023-08-28 12:52:06',NULL),(336,'sdcc.sourceforge.net','sdcc.sourceforge.net','','2023-08-28 12:52:06',NULL),(337,'SoftSoft Ltd','SoftSoft Ltd','','2023-08-28 12:52:06',NULL),(338,'Image Resizer','Image Resizer','','2023-08-28 12:52:06',NULL),(339,'Semtech Corporation','Semtech Corporation','semtech.com','2023-08-28 12:52:06',NULL),(340,'DL5SWB','DL5SWB','','2023-08-28 12:52:06',NULL),(341,'Kingston','Kingston Technology','','2023-08-28 12:52:06',NULL),(342,'Cerulean Studios, LLC','Cerulean Studios, LLC','','2023-08-28 12:52:06',NULL),(343,'winavr.sourceforge.net','winavr.sourceforge.net','open source software development tools','2023-08-28 12:52:06',NULL),(344,'SEW Eurodrive GmbH','SEW Eurodrive GmbH','','2023-08-28 12:52:06',NULL),(345,'SEW Eurodrive GmbH & Co. KG','SEW Eurodrive GmbH & Co. KG','','2023-08-28 12:52:06',NULL),(346,'STM','STM','','2023-08-28 12:52:06',NULL),(347,'GnuWin32','gnuwin32.sourceforge.net','gnuwin32.sourceforge.net','2023-08-28 12:52:06',NULL),(348,'COSMIC Software','COSMIC Software','','2023-08-28 12:52:06',NULL),(349,'Mathew Sachin','Mathew Sachin','','2023-08-28 12:52:06',NULL),(350,'Nmap Project','Nmap Project','','2023-08-28 12:52:06',NULL),(351,'Adem Group','Adem Group','','2023-08-28 12:52:06',NULL),(352,'GAL-ANA','GAL-ANA','','2023-08-28 12:52:06',NULL),(353,'http://www.FlashGet.com','http://www.FlashGet.com','','2023-08-28 12:52:06',NULL),(354,'SWMole','SWMole','','2023-08-28 12:52:06',NULL),(355,'LLC \"MSMP\"','LLC \"MSMP\"','','2023-08-28 12:52:06',NULL),(356,'Auslogics Software Pty Ltd','Auslogics Software Pty Ltd','','2023-08-28 12:52:06',NULL),(357,'WIDCOMM, Inc.','WIDCOMM, Inc.','','2023-08-28 12:52:06',NULL),(358,'Beepa Pty Ltd','Beepa Pty Ltd','','2023-08-28 12:52:06',NULL),(359,'ODM','ODM','','2023-08-28 12:52:06',NULL),(360,'Банк ВТБ','ОАО Банк ВТБ','У них есть интернет клиент','2023-08-30 05:28:18','reviakin.a'),(361,'Heiko Sommerfeldt','Heiko Sommerfeldt','','2023-08-28 12:52:06',NULL),(362,'Skillbrains','Skillbrains','','2023-08-28 12:52:06',NULL),(363,'Asmedia Technology','Asmedia Technology','','2023-08-28 12:52:06',NULL),(364,'Milestone Systems A/S','Milestone Systems A/S','','2023-08-28 12:52:06',NULL),(365,'Softperfect','Softperfect','','2023-08-28 12:52:06',NULL),(366,'MSI Co., LTD','MSI Co., LTD','','2023-08-28 12:52:06',NULL),(367,'Martin Malнk - REALiX','Martin Malнk - REALiX','','2023-08-28 12:52:06',NULL),(368,'QUARTA','QUARTA','','2023-08-28 12:52:06',NULL),(369,'Anaconda, Inc.','Anaconda, Inc.','','2023-08-28 12:52:06',NULL),(370,'Irfan Skiljan','Irfan Skiljan','','2023-08-28 12:52:06',NULL),(371,'uvnc bvba','uvnc bvba','','2023-08-28 12:52:06',NULL),(372,'Альт-Инвест','Альт-Инвест','','2023-08-28 12:52:06',NULL),(373,'Kaiba Zax','Kaiba Zax','','2023-08-28 12:52:06',NULL),(374,'Спутник','Спутник','','2023-08-28 12:52:06',NULL),(375,'HHD Software','HHD Software, Ltd.','','2023-08-30 13:40:17','reviakin.a'),(376,'Peter Mead','Peter Mead','','2023-08-28 12:52:06',NULL),(377,'Bullzip','Bullzip','','2023-08-28 12:52:06',NULL),(378,'Company','Company','','2023-08-28 12:52:06',NULL),(379,'Nextcloud GmbH','Nextcloud GmbH','','2023-08-28 12:52:06',NULL),(380,'Maël Hörz','Maël Hörz','','2023-08-28 12:52:06',NULL),(381,'Wolfram Research, Inc.','Wolfram Research, Inc.','','2023-08-28 12:52:06',NULL),(382,'US Department of Commerce NTIA/ITS','US Department of Commerce NTIA/ITS','','2023-08-28 12:52:06',NULL),(383,'GAL-ANT','GAL-ANT','','2023-08-28 12:52:06',NULL),(384,'Lsuper','Lsuper','','2023-08-28 12:52:06',NULL),(385,'OriginLab Corporation','OriginLab Corporation','','2023-08-28 12:52:06',NULL),(386,'Adafruit Industries LLC','Adafruit Industries LLC','','2023-08-28 12:52:06',NULL),(387,'Linino','Linino','','2023-08-28 12:52:06',NULL),(388,'CraftUnique','CraftUnique ltd','','2023-08-30 12:53:36','reviakin.a'),(389,'Novarm','Novarm','','2023-08-28 12:52:06',NULL),(390,'NetBeans.org','NetBeans.org','','2023-08-28 12:52:06',NULL),(391,'Stardock Software, Inc.','Stardock Software, Inc.','','2023-08-28 12:52:06',NULL),(392,'UNetLab','UNetLab','','2023-08-28 12:52:06',NULL),(393,'ActiveState','ActiveState','','2023-08-28 12:52:06',NULL),(394,'ConEmu-Maximus5','ConEmu-Maximus5','','2023-08-28 12:52:06',NULL),(395,'AnVir Software','AnVir Software','','2023-08-28 12:52:06',NULL),(396,'Nikolaus Brennig','Nikolaus Brennig','','2023-08-28 12:52:06',NULL),(397,'Bloodshed Software','Bloodshed Software','','2023-08-28 12:52:06',NULL),(398,'www.microsip.org','www.microsip.org','','2023-08-28 12:52:06',NULL),(399,'Evolus Co., Ltd.','Evolus Co., Ltd.','','2023-08-28 12:52:06',NULL),(400,'syntevo GmbH','syntevo GmbH','','2023-08-28 12:52:06',NULL),(401,'TaoFramework','TaoFramework','','2023-08-28 12:52:06',NULL),(402,'SafelyRemove.com','SafelyRemove.com','','2023-08-28 12:52:06',NULL),(403,'Avanset','Avanset','','2023-08-28 12:52:06',NULL),(404,'Null Team Impex SRL','Null Team Impex SRL','','2023-08-28 12:52:06',NULL),(405,'Labcenter Electronics','Labcenter Electronics','','2023-08-28 12:52:06',NULL),(406,'Kovid Goyal','Kovid Goyal','','2023-08-28 12:52:06',NULL),(407,'QUALCOMM Incorporated','QUALCOMM Incorporated','','2023-08-28 12:52:06',NULL),(408,'CitrixOnline','CitrixOnline','','2023-08-28 12:52:06',NULL),(409,'WhatsApp','WhatsApp','','2023-08-28 12:52:06',NULL),(410,'Bandisoft','Bandisoft','','2023-08-28 12:52:06',NULL),(411,'QUARTA-RAD','QUARTA-RAD','','2023-08-28 12:52:06',NULL),(412,'MinGW-W64','MinGW-W64','','2023-08-28 12:52:06',NULL),(413,'Digital Wave Ltd','Digital Wave Ltd','','2023-08-28 12:52:06',NULL),(414,'X2Go Project','X2Go Project','','2023-08-28 12:52:06',NULL),(415,'Atlassian','Atlassian','','2023-08-28 12:52:06',NULL),(416,'Wargaming.net','Wargaming.net','','2023-08-28 12:52:06',NULL),(417,'Airtel-ATN','Airtel-ATN','','2023-08-28 12:52:06',NULL),(418,'Telegram Messenger LLP','Telegram Messenger LLP','','2023-08-28 12:52:06',NULL),(419,'almico.com','almico.com','','2023-08-28 12:52:06',NULL),(420,'JSC Azimut','JSC Azimut','','2023-08-28 12:52:06',NULL),(421,'Iteamma Development Team','Iteamma Development Team','','2023-08-28 12:52:06',NULL),(422,'Joachim Eibl','Joachim Eibl','','2023-08-28 12:52:06',NULL),(423,'Pinnacle Systems','Pinnacle Systems','','2023-08-28 12:52:06',NULL),(424,'Reganam','Reganam','','2023-08-28 12:52:06',NULL),(425,'Eremex','Eremex','','2023-08-28 12:52:06',NULL),(426,'Gemalto','Gemalto','','2023-08-28 12:52:06',NULL),(427,'ГК Адепт','ГК Адепт','','2023-08-28 12:52:06',NULL),(428,'Veeam','Veeam Software Group GmbH','Частная компания, специализирующаяся на разработке программного обеспечения для резервного копирования виртуальных машин и мониторинга виртуальных сред на базе платформ VMware и Hyper-V','2023-08-28 12:52:06',NULL),(429,'Artifex','Artifex Software Inc.','','2023-08-28 12:52:06',NULL),(430,'Online Center ltd','Online Center ltd','','2023-08-28 12:52:06',NULL),(431,'SarovDaqGroupTeam','SarovDaqGroupTeam','','2023-08-28 12:52:06',NULL),(432,'Ascensio System SIA.','Ascensio System SIA.','','2023-08-30 05:38:09','reviakin.a'),(433,'Rocket.Chat Technologies Corp.','Rocket.Chat Technologies Corp.','','2023-08-28 12:52:06',NULL),(434,'Bernhard Seifert and Oliver Schneider','Bernhard Seifert and Oliver Schneider','windirstat.net','2023-08-28 12:52:06',NULL),(435,'Zoom Video Communications, Inc.','Zoom Video Communications, Inc.','','2023-08-28 12:52:06',NULL),(436,'PTC','PTC','','2023-08-28 12:52:06',NULL),(437,'Ghisler Software','Ghisler Software GmbH','','2023-08-30 13:24:38','reviakin.a'),(438,'AT&T Research Labs.','AT&T Research Labs.','','2023-08-28 12:52:06',NULL),(439,'dwimperl.com','dwimperl.com','Автор Габор Сабо','2023-08-28 12:52:06',NULL),(440,'The Orbitum Authors','The Orbitum Authors','','2023-08-28 12:52:06',NULL),(441,'Qt Project','Qt Project','','2023-08-28 12:52:06',NULL),(442,'Analog Devices','Analog Devices','','2023-08-28 12:52:06',NULL),(443,'KEIL - Tools By ARM','KEIL - Tools By ARM','','2023-08-28 12:52:06',NULL),(444,'SEGGER Microcontroller GmbH','SEGGER Microcontroller GmbH','','2023-08-28 12:52:06',NULL),(445,'Logitech Europe S.A.','Logitech Europe S.A.','','2023-08-28 12:52:06',NULL),(446,'SIMetrix Technologies Ltd','SIMetrix Technologies Ltd','','2023-08-28 12:52:06',NULL),(447,'Tim Kosse','Tim Kosse','','2023-08-28 12:52:06',NULL),(448,'Copper Mountain Technologies','Copper Mountain Technologies','http://www.coppermountaintech.com','2023-08-30 06:14:56','reviakin.a'),(449,'CrystalIDEA Software, Inc.','CrystalIDEA Software, Inc.','','2023-08-28 12:52:06',NULL),(450,'VS Revo Group, Ltd.','VS Revo Group, Ltd.','','2023-08-28 12:52:06',NULL),(451,'Akamai Technologies, Inc','Akamai Technologies, Inc','','2023-08-28 12:52:06',NULL),(452,'Silicon Laboratories, Inc.','Silicon Laboratories, Inc.','','2023-08-28 12:52:06',NULL),(453,'MySQL AB','MySQL AB','','2023-08-28 12:52:06',NULL),(454,'The Code::Blocks Team','The Code::Blocks Team','','2023-08-28 12:52:06',NULL),(455,'X-Tek Corporation','X-Tek Corporation','','2023-08-28 12:52:06',NULL),(456,'Mail.Ru Group','Mail.Ru Group','','2023-08-28 12:52:06',NULL),(457,'Applied Radio Labs','Applied Radio Labs','','2023-08-28 12:52:06',NULL),(458,'Contaware.com','Contaware.com','','2023-08-28 12:52:06',NULL),(459,'ComAp','ComAp','www.comap-control.com','2023-08-28 12:52:06',NULL),(460,'Два Пилота','Два Пилота','Color Pilot','2023-08-28 12:52:06',NULL),(461,'Creative','Creative Technology Limited','','2023-08-30 12:57:02','reviakin.a'),(462,'RealVNC Ltd','RealVNC Ltd','','2023-08-28 12:52:06',NULL),(463,'Rivet Networks','Rivet Networks','к мат. плате','2023-08-28 12:52:06',NULL),(464,'AVerMedia','AVerMedia TECHNOLOGIES, Inc.','','2023-08-30 05:58:38','reviakin.a'),(465,'ГК СТУ','ГК СТУ','','2023-08-28 12:52:06',NULL),(466,'Ritlabs','Ritlabs','','2023-08-28 12:52:06',NULL),(467,'Git Dev Team ','The Git Development Community','Команда разработчиков GIT','2023-08-28 12:52:06',NULL),(468,'International GeoGebra Institute','International GeoGebra Institute','','2023-08-28 12:52:06',NULL),(469,'Xilinx, Inc.','Xilinx, Inc.','','2023-08-28 12:52:06',NULL),(470,'IvoSoft','IvoSoft','Ivo Beltchev','2023-08-28 12:52:06',NULL),(471,'Ocbase.com','Ocbase.com','https://www.ocbase.com/','2023-08-28 12:52:06',NULL),(472,'Digilent, Inc.','Digilent, Inc.','','2023-08-28 12:52:06',NULL),(473,'National Instruments','National Instruments','Американская компания, насчитывающая свыше 6000 сотрудников и имеющая представительства в 41 стране мира. Штаб-квартира компании расположена в г. Остин, Техас.','2023-08-28 12:52:06',NULL),(474,'Softplicity, Inc.','Softplicity, Inc.','','2023-08-28 12:52:06',NULL),(475,'Software Companions','Software Companions','','2023-08-28 12:52:06',NULL),(476,'SDI Solution','SDI Solution','','2023-08-28 12:52:06',NULL),(477,'Фирма «Интеграл»','Фирма «Интеграл»','','2023-08-28 12:52:06',NULL),(478,'VisualSVN Ltd.','VisualSVN Ltd.','','2023-08-28 12:52:06',NULL),(479,'Advantech','Advantech Automation Co., LTD','','2023-08-28 12:52:06',NULL),(480,'Flachmann und Heggelbacher GbR','Flachmann und Heggelbacher GbR','','2023-08-28 12:52:06',NULL),(481,'LeCroy','LeCroy','','2023-08-28 12:52:06',NULL),(482,'OPC Foundation','OPC Foundation','','2023-08-28 12:52:06',NULL),(483,'LIRA SAPR','LIRA SAPR','','2023-08-28 12:52:06',NULL),(484,'Base','Base','','2023-08-28 12:52:06',NULL),(485,'ООО \"ФОК-СОФТ\"','ООО \"ФОК-СОФТ\"','','2023-08-28 12:52:06',NULL),(486,'SOFOS','SOFOS','','2023-08-28 12:52:06',NULL),(487,'MSI','Micro Start International','Тайваньская компания по производству электроники. Основана в 1986 году, владеет собственными производственными мощностями. Является одним из лидеров по срокам предложения новинок на IT-рынке.','2023-08-28 12:52:06',NULL),(488,'DevID','DevID','','2023-08-28 12:52:06',NULL),(489,'Ralink','Ralink Technology, Corp.','Производитель Wi-Fi чипсетов.','2023-08-28 12:52:06',NULL),(490,'ЗАО НВП Болид','ЗАО НВП Болид','','2023-08-28 12:52:06',NULL),(491,'Dominik Reichl','Dominik Reichl','','2023-08-28 12:52:06',NULL),(492,'geek software','geek software GmbH','','2023-08-30 13:21:47','reviakin.a'),(493,'Р7','АО \"Р7\"','Р7-Офис','2023-08-30 14:36:12','reviakin.a'),(494,'NetApp','NetApp','Производитель железа и ПО','2023-08-28 12:52:06',NULL),(495,'ЗАО «Смарт Лайн Инк»','ЗАО «Смарт Лайн Инк»','DeviceLock ','2023-08-28 12:52:06',NULL),(496,'Huawei','Huawei Technologies Co.,Ltd','','2023-08-28 12:52:06',NULL),(497,'Mikrotik','Mikrotik','','2023-08-28 12:52:06',NULL),(498,'Grandstream','Grandstream Networks','SIP-телефония и SIP-видеонаблюдение','2023-08-28 12:52:06',NULL),(499,'D-Link','D-Link Corporation','Производитель сетевого оборудования','2023-08-28 12:52:06',NULL),(500,'APC','American Power Conversion','Поглощена Schneider Electric в 2007. Сохраняется как торговая марка.','2023-08-28 12:52:06',NULL),(501,'Ubiquiti','Ubiquiti Networks','производит продукты для беспроводной передачи данных для предприятий и провайдеров беспроводного широкополосного доступа с основным упором на малообслуживаемость и развивающиеся рынки','2023-08-28 12:52:06',NULL),(502,'Ippon','Ippon','Производитель ИБП','2023-08-28 12:52:06',NULL),(503,'Beward','НПП \"Бeвард\"','разработчик и производитель оборудования для систем видеонаблюдения.','2023-08-28 12:52:06',NULL),(504,'DEPO','DEPO','','2023-08-28 12:52:06',NULL),(505,'Powerman','Powerman','','2023-08-28 12:52:06',NULL),(506,'ExeGate','ExeGate','Компания ExeGate основана в 2009 году в Гонконге группой инженеров компьютерной отрасли. Специализация ExeGate - производство компьютерных комплектующих и аксессуаров.','2023-08-28 12:52:06',NULL),(507,'CyberPower','CyberPower','','2023-08-28 12:52:06',NULL),(508,'EATON','EATON','','2023-08-28 12:52:06',NULL),(509,'Acer','Acer','','2023-08-28 12:52:06',NULL),(510,'QNAP','QNAP','QNAP','2023-08-28 12:52:06',NULL),(511,'Parsec','Parsec','СКУД','2023-08-28 12:52:06',NULL),(512,'MERCUSYS','MERCUSYS','Desktop Switch','2023-08-28 12:52:06',NULL),(513,'Brocade','Brocade Communications Systems','Американская компания. Проектирует, производит и продаёт комплексные решения, а также программное обеспечение для управления сетями хранения данных','2023-08-28 12:52:06',NULL),(514,'RICOH','RICOH','ricoh.ru','2023-08-28 12:52:06',NULL),(515,'3COM','3COM','','2023-08-28 12:52:06',NULL),(516,'Yealink','Yealink Network Technology','китайская компания, производитель и разработчик SIP-телефонов, систем видео-конференц-связи и аксессуаров к ним','2023-08-28 12:52:06',NULL),(517,'Sky Control','Sky Control s.r.o','Sky Control s.r.o — высокотехнологическая компания, основанная в Словакии (Братислава).','2023-08-28 12:52:06',NULL),(518,'H3C','H3C','H3C','2023-08-28 12:52:06',NULL),(519,'Brother','Brother','','2023-08-28 12:52:06',NULL),(520,'Philips','Koninklijke Philips N.V.','Нидерландская транснациональная компания. Производство потребительских товаров и медицинского оборудования.','2023-08-28 12:52:06',NULL),(521,'Dell','Dell Technologies Inc.','','2023-08-28 12:52:06',NULL),(522,'Supermicro','Super Micro Computer, Inc.','американская компания, крупный производитель материнских плат, корпусов, источников питания, систем охлаждения, контроллеров SAS, Ethernet и InfiniBand. Компания специализируется на выпуске x86-серверных платформ и различных комплектующих для серверов, ра','2023-08-28 12:52:06',NULL),(523,'AOC','AOC International','','2023-08-28 12:52:06',NULL),(524,'ViewSonic','ViewSonic','','2023-08-28 12:52:06',NULL),(525,'BenQ','BenQ','','2023-08-28 12:52:06',NULL),(526,'LG','LG','','2023-08-28 12:52:06',NULL),(527,'TP-LINK','TP-Link','','2023-08-28 12:52:06',NULL),(528,'Brady','Brady','Общество с Ограниченной Ответственностью \"Центр Промышленной Маркировки\"','2023-08-28 12:52:06',NULL),(529,'Zebra','Zebra Technologies Corporation','','2023-08-28 12:52:06',NULL),(530,'GETAC','GETAC','','2023-08-28 12:52:06',NULL),(531,'ELO','ELO','','2023-08-28 12:52:06',NULL),(532,'PowerCom','PowerCom','','2023-08-28 12:52:06',NULL),(533,'Panasonic','Panasonic Corporation','','2023-08-28 12:52:06',NULL),(534,'Hisseu','Hisseu','','2023-08-28 12:52:06',NULL),(535,'Net work NVR','Network Video Recorder','','2023-08-28 12:52:06',NULL),(536,'Cabeus','Cabeus','Cabeus является производителем полного спектра продукции для построения Структурированной Кабельной Системы (СКС).','2023-08-28 12:52:06',NULL),(537,'RDW','RDW Computers','Российский производитель компьютеров','2023-08-28 12:52:06',NULL),(538,'Vutlan','Система контроля Vutlan','мониторинг серверной комнаты','2023-08-28 12:52:06',NULL),(539,'ДистКонтрол','ООО \"ДистКонтрол\"','Производитель USB концентраторов','2023-09-15 05:19:13','admin'),(540,'Epson','Epson','','2023-08-28 12:52:06',NULL),(541,'RoverScan','RoverScan','Мониторы','2023-08-28 12:52:06',NULL),(542,'Импульс','ООО \"ЦРИ \"ИМПУЛЬС\"','https://impuls.energy/','2023-09-06 10:01:12','reviakin.a');
/*!40000 ALTER TABLE `manufacturers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manufacturers_dict`
--

DROP TABLE IF EXISTS `manufacturers_dict`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manufacturers_dict` (
  `id` int NOT NULL AUTO_INCREMENT,
  `word` varchar(255) NOT NULL COMMENT 'Вариант написания',
  `manufacturers_id` int NOT NULL COMMENT 'Производитель',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=InnoDB AUTO_INCREMENT=654 DEFAULT CHARSET=utf8mb3 COMMENT='Словарь производителей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manufacturers_dict`
--

LOCK TABLES `manufacturers_dict` WRITE;
/*!40000 ALTER TABLE `manufacturers_dict` DISABLE KEYS */;
INSERT INTO `manufacturers_dict` VALUES (1,'logitech',1,'2023-08-28 12:52:08',NULL),(2,'logitech inc.',1,'2023-08-28 12:52:08',NULL),(3,'amd',2,'2023-08-28 12:52:08',NULL),(4,'advanced micro devices, inc.',2,'2023-08-28 12:52:08',NULL),(5,'ati',2,'2023-08-28 12:52:08',NULL),(6,'ati technologies, inc.',2,'2023-08-28 12:52:08',NULL),(7,'ati technologies inc.',2,'2023-08-28 12:52:08',NULL),(8,'microsoft corporation',3,'2023-08-28 12:52:08',NULL),(9,'microsoft',3,'2023-08-28 12:52:08',NULL),(10,'skype technologies s.a.',3,'2023-08-28 12:52:08',NULL),(11,'корпорация майкрософт',3,'2023-08-28 12:52:08',NULL),(12,'microsoft corp',3,'2023-08-28 12:52:08',NULL),(13,'корпорация майкрософт (microsoft corporation)',3,'2023-08-28 12:52:08',NULL),(14,'microsoft corp.',3,'2023-08-28 12:52:08',NULL),(15,'майкрософт',3,'2023-08-28 12:52:08',NULL),(16,'oracle',4,'2023-08-28 12:52:08',NULL),(17,'oracle corporation',4,'2023-08-28 12:52:08',NULL),(18,'oracle and/or its affiliates',4,'2023-08-28 12:52:08',NULL),(19,'sun microsystems, inc.',4,'2023-08-28 12:52:08',NULL),(20,'the gimp team',5,'2023-08-28 12:52:08',NULL),(21,'eugene roshal & far group',6,'2023-08-28 12:52:08',NULL),(22,'google inc.',7,'2023-08-28 12:52:08',NULL),(23,'google, inc.',7,'2023-08-28 12:52:08',NULL),(24,'google',7,'2023-08-28 12:52:08',NULL),(25,'компания google',7,'2023-08-28 12:52:08',NULL),(26,'google llc',7,'2023-08-28 12:52:08',NULL),(27,'mozilla',8,'2023-08-28 12:52:08',NULL),(28,'frontmotion',8,'2023-08-28 12:52:08',NULL),(29,'vmware,',9,'2023-08-28 12:52:08',NULL),(30,'vmware',9,'2023-08-28 12:52:08',NULL),(31,'vmware, inc.',9,'2023-08-28 12:52:08',NULL),(32,'vmware, inc',9,'2023-08-28 12:52:08',NULL),(33,'vmware virtual ram',9,'2023-08-28 12:52:08',NULL),(34,'pdfforge gmbh',10,'2023-08-28 12:52:08',NULL),(35,'pdfforge',10,'2023-08-28 12:52:08',NULL),(36,'frank heindцrfer, philip chinery',10,'2023-08-28 12:52:08',NULL),(37,'adobe systems incorporated',11,'2023-08-28 12:52:08',NULL),(38,'adobe systems, inc.',11,'2023-08-28 12:52:08',NULL),(39,'adobe systems, inc',11,'2023-08-28 12:52:08',NULL),(40,'adobe systems inc.',11,'2023-08-28 12:52:08',NULL),(41,'adobe',11,'2023-08-28 12:52:08',NULL),(42,'nullsoft, inc',12,'2023-08-28 12:52:08',NULL),(43,'audacity team',13,'2023-08-28 12:52:08',NULL),(44,'igor pavlov',14,'2023-08-28 12:52:08',NULL),(45,'notepad++ team',15,'2023-08-28 12:52:08',NULL),(46,'realtek semiconductor corp.',16,'2023-08-28 12:52:08',NULL),(47,'realtek',16,'2023-08-28 12:52:08',NULL),(48,'3cx',17,'2023-08-28 12:52:08',NULL),(49,'siemens',18,'2023-08-28 12:52:08',NULL),(50,'siemens plm software',18,'2023-08-28 12:52:08',NULL),(51,'siemens corporation',18,'2023-08-28 12:52:08',NULL),(52,'siemens ag',18,'2023-08-28 12:52:08',NULL),(53,'siemens product lifecycle management software inc.',18,'2023-08-28 12:52:08',NULL),(54,'openvpn technologies, inc.',19,'2023-08-28 12:52:08',NULL),(55,'avaya',20,'2023-08-28 12:52:08',NULL),(56,'intel(r)',21,'2023-08-28 12:52:08',NULL),(57,'intel corporation',21,'2023-08-28 12:52:08',NULL),(58,'intel',21,'2023-08-28 12:52:08',NULL),(59,'корпорация intel',21,'2023-08-28 12:52:08',NULL),(60,'intel(r) corporation',21,'2023-08-31 03:50:28','reviakin.a'),(61,'intel (r)',21,'2023-08-30 13:53:30','reviakin.a'),(62,'intel ®',21,'2023-08-30 13:57:32','reviakin.a'),(63,'intel®',21,'2023-08-30 13:57:39','reviakin.a'),(64,'citrix systems, inc.',22,'2023-08-28 12:52:08',NULL),(65,'citrix',22,'2023-08-28 12:52:08',NULL),(66,'citrix systems inc.',22,'2023-08-28 12:52:08',NULL),(67,'the qbittorrent project',23,'2023-08-28 12:52:08',NULL),(68,'glavsoft llc.',24,'2023-08-28 12:52:08',NULL),(69,'smartbear software',25,'2023-08-28 12:52:08',NULL),(70,'imperative software pty ltd',26,'2023-08-28 12:52:08',NULL),(71,'@oem24.inf,%dwmirrordrv%',27,'2023-08-28 12:52:08',NULL),(72,'teamviewer',28,'2023-08-28 12:52:08',NULL),(73,'tarifer.ru',29,'2023-08-28 12:52:08',NULL),(74,'videolan',30,'2023-08-28 12:52:08',NULL),(75,'symantec corporation',31,'2023-08-28 12:52:08',NULL),(76,'nvidia',32,'2023-08-28 12:52:08',NULL),(77,'nvidia corporation',32,'2023-08-28 12:52:08',NULL),(78,'nvidia corp.',32,'2023-08-28 12:52:08',NULL),(79,'lunarg, inc.',33,'2023-08-28 12:52:08',NULL),(80,'andrew zhezherun',34,'2023-08-28 12:52:08',NULL),(81,'neo',34,'2023-08-28 12:52:08',NULL),(82,'hewlett-packard',35,'2023-08-28 12:52:08',NULL),(83,'hp',35,'2023-08-28 12:52:08',NULL),(84,'hp inc.',35,'2023-08-28 12:52:08',NULL),(85,'hewlett-packard co.',35,'2023-08-28 12:52:08',NULL),(86,'hewlett packard development company l.p.',35,'2023-08-28 12:52:08',NULL),(87,'hewlett-packard company',35,'2023-08-28 12:52:08',NULL),(88,'hewlett-packard development company, lp.',35,'2023-08-28 12:52:08',NULL),(89,'hewlett-packard development company, l.p.',35,'2023-08-28 12:52:08',NULL),(90,'synctrayzor',36,'2023-08-28 12:52:08',NULL),(91,'techsmith corporation',37,'2023-08-28 12:52:08',NULL),(92,'389 project',38,'2023-08-28 12:52:08',NULL),(93,'solarwinds',39,'2023-08-28 12:52:08',NULL),(94,'www.pdf24.org',40,'2023-08-28 12:52:08',NULL),(95,'pdf24.org',40,'2023-08-28 12:52:08',NULL),(96,'klcp',41,'2023-08-28 12:52:08',NULL),(97,'ldapsoft',42,'2023-08-28 12:52:08',NULL),(98,'krzysztof kowalczyk',44,'2023-08-28 12:52:08',NULL),(99,'starwind software',45,'2023-08-28 12:52:08',NULL),(100,'xiaomi',46,'2023-08-28 12:52:08',NULL),(101,'altium limited',47,'2023-08-28 12:52:08',NULL),(102,'altium bv',47,'2023-08-28 12:52:08',NULL),(103,'ispring solutions inc.',48,'2023-08-28 12:52:08',NULL),(104,'open design alliance',49,'2023-08-28 12:52:08',NULL),(105,'ocs inventory ng team',50,'2023-08-28 12:52:08',NULL),(106,'foxit software inc.',51,'2023-08-28 12:52:08',NULL),(107,'foxit software company',51,'2023-08-28 12:52:08',NULL),(108,'foxit software',51,'2023-08-28 12:52:08',NULL),(109,'foxit corporation',51,'2023-08-28 12:52:08',NULL),(110,'futuremark',52,'2023-08-28 12:52:08',NULL),(111,'prolific technology inc.',53,'2023-08-28 12:52:08',NULL),(112,'prolific technology inc',53,'2023-08-28 12:52:08',NULL),(113,'dameware',54,'2023-08-28 12:52:08',NULL),(114,'@oem16.inf,%dwmirrordrv%',54,'2023-08-28 12:52:08',NULL),(115,'asustek computer inc.',55,'2023-08-28 12:52:08',NULL),(116,'asustek',55,'2023-08-28 12:52:08',NULL),(117,'asus',55,'2023-08-28 12:52:08',NULL),(118,'the document foundation',56,'2023-08-28 12:52:08',NULL),(119,'аскон',57,'2023-08-28 12:52:08',NULL),(120,'cdburnerxp',58,'2023-08-28 12:52:08',NULL),(121,'aimp devteam',59,'2023-08-28 12:52:08',NULL),(122,'aimp',59,'2023-08-28 12:52:08',NULL),(123,'nokia',60,'2023-08-28 12:52:08',NULL),(124,'nokia corporation and/or its subsidiary(-ies)',60,'2023-08-31 05:48:27','reviakin.a'),(125,'simon tatham',61,'2023-08-28 12:52:08',NULL),(126,'borland software corporation',62,'2023-08-28 12:52:08',NULL),(127,'bitrix, inc',63,'2023-08-28 12:52:08',NULL),(128,'tortoisesvn',64,'2023-08-28 12:52:08',NULL),(129,'python software foundation',65,'2023-08-28 12:52:08',NULL),(130,'kay hayen',65,'2023-08-28 12:52:08',NULL),(131,'colin harrison',66,'2023-08-28 12:52:08',NULL),(132,'saturn pcb design, inc. - www.saturnpcb.com',67,'2023-08-28 12:52:08',NULL),(133,'saturn pcb design, inc.',67,'2023-08-28 12:52:08',NULL),(134,'juergen riegel',68,'2023-08-28 12:52:08',NULL),(135,'gnu octave',69,'2023-08-28 12:52:08',NULL),(136,'яндекс',70,'2023-08-28 12:52:08',NULL),(137,'yandex llc',70,'2023-08-28 12:52:08',NULL),(138,'ооо «яндекс»',70,'2023-08-31 04:54:47','reviakin.a'),(139,'yandex',70,'2023-08-31 04:55:23','reviakin.a'),(140,'moxa inc.',72,'2023-08-28 12:52:08',NULL),(141,'moxa technologies co., ltd.',72,'2023-08-28 12:52:08',NULL),(142,'moxa',72,'2023-08-28 12:52:08',NULL),(143,'inkscape.org',73,'2023-08-28 12:52:08',NULL),(144,'inkscape project',73,'2023-08-28 12:52:08',NULL),(145,'arduino llc',74,'2023-08-28 12:52:08',NULL),(146,'arduino srl (www.arduino.org)',74,'2023-08-28 12:52:08',NULL),(147,'arduino llc (www.arduino.cc)',74,'2023-08-28 12:52:08',NULL),(148,'librecad team',75,'2023-08-28 12:52:08',NULL),(149,'aktiv co.',76,'2023-08-28 12:52:08',NULL),(150,'компания \"актив\"',76,'2023-08-28 12:52:08',NULL),(151,'aktiv company',76,'2023-08-30 05:25:01','reviakin.a'),(152,'rohde & schwarz',78,'2023-08-28 12:52:08',NULL),(153,'rohde_schwarz',78,'2023-08-28 12:52:08',NULL),(154,'rohde & schwarz gmbh & co. kg',78,'2023-08-28 12:52:08',NULL),(155,'viber media inc.',79,'2023-08-28 12:52:08',NULL),(156,'viber мессенджер',79,'2023-08-28 12:52:08',NULL),(157,'viber media s.a.r.l',79,'2023-08-28 12:52:08',NULL),(158,'viber media inc',79,'2023-08-28 12:52:08',NULL),(159,'2010-2021 viber media s.a.r.l',79,'2023-08-28 12:52:08',NULL),(160,'unity technologies aps',80,'2023-08-28 12:52:08',NULL),(161,'mail.ru',81,'2023-08-28 12:52:08',NULL),(162,'mail.ru llc',81,'2023-08-28 12:52:08',NULL),(163,'azimut',82,'2023-08-28 12:52:08',NULL),(164,'planar',83,'2023-08-28 12:52:08',NULL),(165,'planar, http://planarchel.ru',83,'2023-08-28 12:52:08',NULL),(166,'planar, inc.',83,'2023-08-28 12:52:08',NULL),(167,'copper mountain technologies, inc.',83,'2023-08-28 12:52:08',NULL),(168,'silicon laboratories',84,'2023-08-28 12:52:08',NULL),(169,'cadence design systems',85,'2023-08-28 12:52:08',NULL),(170,'digitalcore',86,'2023-08-28 12:52:08',NULL),(171,'visualgpsllc',88,'2023-08-28 12:52:08',NULL),(172,'zofzpcb',89,'2023-08-28 12:52:08',NULL),(173,'pentalogix',90,'2023-08-28 12:52:08',NULL),(174,'akelsoft',91,'2023-08-28 12:52:08',NULL),(175,'cadence design systems, inc.',92,'2023-08-28 12:52:08',NULL),(176,'litemanagerteam',93,'2023-08-28 12:52:08',NULL),(177,'mediatek, inc.',94,'2023-08-28 12:52:08',NULL),(178,'зао «пф «ckб контур»',95,'2023-08-28 12:52:08',NULL),(179,'зао «пф «скб контур»',95,'2023-08-28 12:52:08',NULL),(180,'pf skb kontur zao',95,'2023-08-28 12:52:08',NULL),(181,'ао «пф «скб контур»',95,'2023-08-28 12:52:08',NULL),(182,'компания \"актив\", зао «пф «скб контур»',95,'2023-08-28 12:52:08',NULL),(183,'firebird project',96,'2023-08-28 12:52:08',NULL),(184,'the firebird project',96,'2023-08-28 12:52:08',NULL),(185,'firebird',96,'2023-08-28 12:52:08',NULL),(186,'компания крипто-про',97,'2023-08-28 12:52:08',NULL),(187,'крипто-про',97,'2023-08-28 12:52:08',NULL),(188,'компания криптопро',97,'2023-08-28 12:52:08',NULL),(189,'via technologies, inc.',98,'2023-08-28 12:52:08',NULL),(190,'kyocera document solutions inc.',99,'2023-08-28 12:52:08',NULL),(191,'kyocera mita corporation',99,'2023-08-28 12:52:08',NULL),(192,'фгуп гнивц фнс россии',100,'2023-08-28 12:52:08',NULL),(193,'фгуп гнивц фнс рф в пфо',100,'2023-08-28 12:52:08',NULL),(194,'rosreestrxml',100,'2023-08-28 12:52:08',NULL),(195,'solidworks',101,'2023-08-28 12:52:08',NULL),(196,'solidworks corporation',101,'2023-08-28 12:52:08',NULL),(197,'dassault systemes solidworks corp',101,'2023-08-28 12:52:08',NULL),(198,'dassault systèmes solidworks corp.',101,'2023-08-28 12:52:08',NULL),(199,'intermech',102,'2023-08-28 12:52:08',NULL),(200,'altera',103,'2023-08-28 12:52:08',NULL),(201,'altera corporation',103,'2023-08-28 12:52:08',NULL),(202,'rogers corporation',105,'2023-08-28 12:52:08',NULL),(203,'3dconnexion',106,'2023-08-28 12:52:08',NULL),(204,'softland',107,'2023-08-28 12:52:08',NULL),(205,'andrea vacondio',108,'2023-08-28 12:52:08',NULL),(206,'©7sh3. (сборка от 08.11.2012)',109,'2023-08-28 12:52:08',NULL),(207,'rostelecom',110,'2023-08-28 12:52:08',NULL),(208,'renesas electronics corporation',111,'2023-08-28 12:52:08',NULL),(209,'canon inc.',112,'2023-08-28 12:52:08',NULL),(210,'stdutility',113,'2023-08-28 12:52:08',NULL),(211,'collabnet',114,'2023-08-28 12:52:08',NULL),(212,'gougelet pierre-e',115,'2023-08-28 12:52:08',NULL),(213,'syncmaster',116,'2023-08-28 12:52:08',NULL),(214,'s24d590',116,'2023-08-28 12:52:08',NULL),(215,'sme2220nw',116,'2023-08-28 12:52:08',NULL),(216,'samsung',116,'2023-08-28 12:52:08',NULL),(217,'sms27a550h',116,'2023-08-28 12:52:08',NULL),(218,'smb2430l',116,'2023-08-28 12:52:08',NULL),(219,'sms23a350h',116,'2023-08-28 12:52:08',NULL),(220,'sms27a650',116,'2023-08-28 12:52:08',NULL),(221,'sms24a450',116,'2023-08-28 12:52:08',NULL),(222,'samsung electronics co., ltd.',116,'2023-08-28 12:52:08',NULL),(223,'s24d300',116,'2023-08-28 12:52:08',NULL),(224,'digiteo',117,'2023-08-28 12:52:08',NULL),(225,'guardant',118,'2023-08-28 12:52:08',NULL),(226,'авторская группа разработчиков авс',119,'2023-08-28 12:52:08',NULL),(227,'safenet inc.',120,'2023-08-28 12:52:08',NULL),(228,'safenet, inc.',120,'2023-08-28 12:52:08',NULL),(229,'riverbed technology, inc.',121,'2023-08-28 12:52:08',NULL),(230,'the wireshark developer community, https://www.wireshark.org',122,'2023-08-28 12:52:08',NULL),(231,'ftdi',123,'2023-08-28 12:52:08',NULL),(232,'future technology devices international ltd.',123,'2023-08-28 12:52:08',NULL),(233,'ftdi ltd',123,'2023-08-28 12:52:08',NULL),(234,'dotpdn llc',124,'2023-08-28 12:52:08',NULL),(235,'cpuid',125,'2023-08-28 12:52:08',NULL),(236,'antenna house',126,'2023-08-28 12:52:08',NULL),(237,'justsystems canada, inc.',127,'2023-08-28 12:52:08',NULL),(238,'sap',128,'2023-08-28 12:52:08',NULL),(239,'sap se',128,'2023-08-28 12:52:08',NULL),(240,'sap ag',128,'2023-08-28 12:52:08',NULL),(241,'sap businessobjects',128,'2023-08-28 12:52:08',NULL),(242,'parallelgraphics',130,'2023-08-28 12:52:08',NULL),(243,'paragraphics ltd.',130,'2023-08-28 12:52:08',NULL),(244,'ооо \"дубльгис\"',131,'2023-08-28 12:52:08',NULL),(245,'cisco webex llc',132,'2023-08-28 12:52:08',NULL),(246,'peter pawlowski',133,'2023-08-28 12:52:08',NULL),(247,'cutepdf.com',134,'2023-08-28 12:52:08',NULL),(248,'ooo media mir',135,'2023-08-28 12:52:08',NULL),(249,'vargus ltd',136,'2023-08-28 12:52:08',NULL),(250,'dvdvideosoft ltd.',137,'2023-08-28 12:52:08',NULL),(251,'fraisa sa',138,'2023-08-28 12:52:08',NULL),(252,'компания digt',139,'2023-08-28 12:52:08',NULL),(253,'contex',140,'2023-08-28 12:52:08',NULL),(254,'dimitri van heesch',141,'2023-08-28 12:52:08',NULL),(255,'indigo byte systems, llc',142,'2023-08-28 12:52:08',NULL),(256,'martin prikryl',143,'2023-08-28 12:52:08',NULL),(257,'bruno pagиs',144,'2023-08-28 12:52:08',NULL),(258,'jrsoftware.org',145,'2023-08-28 12:52:08',NULL),(259,'digia plc',146,'2023-08-28 12:52:08',NULL),(260,'plastic software, inc.',147,'2023-08-28 12:52:08',NULL),(261,'mpc-hc team',148,'2023-08-28 12:52:08',NULL),(262,'model technology',150,'2023-08-28 12:52:08',NULL),(263,'abbyy',151,'2023-08-28 12:52:08',NULL),(264,'abbyy production llc',151,'2023-08-28 12:52:08',NULL),(265,'abbyy production',151,'2023-08-28 12:52:08',NULL),(266,'texas instruments, inc.',152,'2023-08-28 12:52:08',NULL),(267,'texas instruments',152,'2023-08-28 12:52:08',NULL),(268,'michael golikov',153,'2023-08-28 12:52:08',NULL),(269,'apple inc.',154,'2023-08-28 12:52:08',NULL),(270,'ivi foundation',155,'2023-08-28 12:52:08',NULL),(271,'nvisiongroup',156,'2023-08-28 12:52:08',NULL),(272,'agilent technologies',158,'2023-08-28 12:52:08',NULL),(273,'agilent technologies, inc.',158,'2023-08-28 12:52:08',NULL),(274,'зао нииит-ртс',159,'2023-08-28 12:52:08',NULL),(275,'зао \"нииит-ртс\"',159,'2023-08-28 12:52:08',NULL),(276,'autodesk',160,'2023-08-28 12:52:08',NULL),(277,'autodesk, inc.',160,'2023-08-28 12:52:08',NULL),(278,'autodesk corporation',160,'2023-08-28 12:52:08',NULL),(279,'faro scanner production',161,'2023-08-28 12:52:08',NULL),(280,'0000',162,'2023-08-28 12:52:08',NULL),(281,'manufacturer0',162,'2023-08-28 12:52:08',NULL),(282,'manufacturer2',162,'2023-08-28 12:52:08',NULL),(283,'manufacturer1',162,'2023-08-28 12:52:08',NULL),(284,'manufacturer3',162,'2023-08-28 12:52:08',NULL),(285,'wm transfer ltd.',163,'2023-08-28 12:52:08',NULL),(286,'cjsc computing forces',165,'2023-08-28 12:52:08',NULL),(287,'softomate',166,'2023-08-28 12:52:08',NULL),(288,'tortoisegit',167,'2023-08-28 12:52:08',NULL),(289,'altergeo',168,'2023-08-28 12:52:08',NULL),(290,'sony',169,'2023-08-28 12:52:08',NULL),(291,'sony corporation',169,'2023-08-28 12:52:08',NULL),(292,'sdm-s205f/k',169,'2023-08-28 12:52:08',NULL),(293,'sony computer entertainment inc.',169,'2023-08-30 14:13:31','reviakin.a'),(294,'marek jasinski',170,'2023-08-28 12:52:08',NULL),(295,'piriform',171,'2023-08-28 12:52:08',NULL),(296,'thingamahoochie software',172,'2023-08-28 12:52:08',NULL),(297,'florian heidenreich',173,'2023-08-28 12:52:08',NULL),(298,'сбербанк',174,'2023-08-28 12:52:08',NULL),(299,'isotousb.com',175,'2023-08-28 12:52:08',NULL),(300,'steve borho and others',176,'2023-08-28 12:52:08',NULL),(301,'filezilla project',177,'2023-08-28 12:52:08',NULL),(302,'bittorrent inc.',178,'2023-08-28 12:52:08',NULL),(303,'iar systems',179,'2023-08-28 12:52:08',NULL),(304,'iar',179,'2023-08-31 05:26:29','reviakin.a'),(305,'cisco systems, inc.',180,'2023-08-28 12:52:08',NULL),(306,'cisco',180,'2023-08-28 12:52:08',NULL),(307,'cisco systems',180,'2023-08-28 12:52:08',NULL),(308,'famatech',181,'2023-08-28 12:52:08',NULL),(309,'perco',182,'2023-08-28 12:52:08',NULL),(310,'ashampoo gmbh & co. kg',183,'2023-08-28 12:52:08',NULL),(311,'gerhard zehetbauer',184,'2023-08-28 12:52:08',NULL),(312,'westbyte',185,'2023-08-28 12:52:08',NULL),(313,'axis communications ab',186,'2023-08-28 12:52:08',NULL),(314,'gigabyte technology co., ltd.',187,'2023-08-28 12:52:08',NULL),(315,'gigabyte',187,'2023-08-28 12:52:08',NULL),(316,'gigabyte technologies, inc.',187,'2023-08-28 12:52:08',NULL),(317,'igc',188,'2023-08-28 12:52:08',NULL),(318,'ао гнивц',189,'2023-08-28 12:52:08',NULL),(319,'центр гранд',190,'2023-08-28 12:52:08',NULL),(320,'мгк гранд',190,'2023-08-28 12:52:08',NULL),(321,'a4tech',191,'2023-08-28 12:52:08',NULL),(322,'nanosoft',192,'2023-08-28 12:52:08',NULL),(323,'кодекс',192,'2023-08-28 12:52:08',NULL),(324,'lenovo group limited',193,'2023-08-28 12:52:08',NULL),(325,'lenovo',193,'2023-08-28 12:52:08',NULL),(326,'broadcom corporation',193,'2023-08-28 12:52:08',NULL),(327,'conexant',193,'2023-08-28 12:52:08',NULL),(328,'freefilesync.org',194,'2023-08-28 12:52:08',NULL),(329,'soliddocuments',195,'2023-08-28 12:52:08',NULL),(330,'solid documents',195,'2023-08-28 12:52:08',NULL),(331,'xmind ltd.',196,'2023-08-28 12:52:08',NULL),(332,'syncro soft',197,'2023-08-28 12:52:08',NULL),(333,'syncro soft srl',197,'2023-08-28 12:52:08',NULL),(334,'design science, inc.',198,'2023-08-28 12:52:08',NULL),(335,'benito van der zander',199,'2023-08-28 12:52:08',NULL),(336,'andrey ivashov',200,'2023-08-28 12:52:08',NULL),(337,'pandora tv',201,'2023-08-28 12:52:08',NULL),(338,'©7sh3. (сборка от 13.04.2015)',201,'2023-08-28 12:52:08',NULL),(339,'©7sh3. (сборка от 01.02.2015)',201,'2023-08-28 12:52:08',NULL),(340,'repack by cuta',201,'2023-08-28 12:52:08',NULL),(341,'сп «кредо-диалог» — ооо',202,'2023-08-28 12:52:08',NULL),(342,'компания «кредо-диалог»',202,'2023-08-28 12:52:08',NULL),(343,'lcd2190uxp',203,'2023-08-28 12:52:08',NULL),(344,'ea192m',203,'2023-08-28 12:52:08',NULL),(345,'nec 90gx2',203,'2023-08-28 12:52:08',NULL),(346,'90gx2',203,'2023-08-28 12:52:08',NULL),(347,'nec electronics corporation',203,'2023-08-28 12:52:08',NULL),(348,'ea244wmi',203,'2023-08-28 12:52:08',NULL),(349,'sharp',204,'2023-08-28 12:52:08',NULL),(350,'dial gmbh',205,'2023-08-28 12:52:08',NULL),(351,'oce-technologies b.v.',206,'2023-08-28 12:52:08',NULL),(352,'фгуп сониир',207,'2023-08-28 12:52:08',NULL),(353,'tracker software',208,'2023-08-28 12:52:08',NULL),(354,'win.rar gmbh',209,'2023-08-28 12:52:08',NULL),(355,'projectlibre',210,'2023-08-28 12:52:08',NULL),(356,'vatra',211,'2023-08-28 12:52:08',NULL),(357,'galad',212,'2023-08-28 12:52:08',NULL),(358,'osram',213,'2023-08-28 12:52:08',NULL),(359,'lighting technologies',214,'2023-08-28 12:52:08',NULL),(360,'exp systems llc',215,'2023-08-28 12:52:08',NULL),(361,'elewise',216,'2023-08-28 12:52:08',NULL),(362,'нпо пас',217,'2023-08-28 12:52:08',NULL),(363,'компания инсат',218,'2023-08-28 12:52:08',NULL),(364,'htc corporation',219,'2023-08-28 12:52:08',NULL),(365,'htc',219,'2023-08-28 12:52:08',NULL),(366,'ооо псп «стройэкспертиза»',220,'2023-08-28 12:52:08',NULL),(367,'dt soft ltd',221,'2023-08-30 13:02:43','reviakin.a'),(368,'disc soft ltd',221,'2023-08-28 12:52:08',NULL),(369,'dt soft ltd.',221,'2023-08-28 12:52:08',NULL),(370,'http://www.daemon-tools.cc',221,'2023-08-28 12:52:08',NULL),(371,'mindjet llc',222,'2023-08-28 12:52:08',NULL),(372,'accmeware corporation',223,'2023-08-28 12:52:08',NULL),(373,'slysoft',224,'2023-08-28 12:52:08',NULL),(374,'codegear',225,'2023-08-28 12:52:08',NULL),(375,'opera software asa',226,'2023-08-28 12:52:08',NULL),(376,'opera software',226,'2023-08-28 12:52:08',NULL),(377,'pdf creator',227,'2023-08-28 12:52:08',NULL),(378,'ritlabs, srl',228,'2023-08-28 12:52:08',NULL),(379,'lazarus team',229,'2023-08-28 12:52:08',NULL),(380,'pandoratv',230,'2023-08-28 12:52:08',NULL),(381,'easeus',231,'2023-08-28 12:52:08',NULL),(382,'ооо \"лидер\"',232,'2023-08-28 12:52:08',NULL),(383,'scad soft',233,'2023-08-28 12:52:08',NULL),(384,'bizagi limited',234,'2023-08-28 12:52:08',NULL),(385,'toshiba',235,'2023-08-28 12:52:08',NULL),(386,'toshiba corporation',235,'2023-08-28 12:52:08',NULL),(387,'agg software',236,'2023-08-28 12:52:08',NULL),(388,'icq',237,'2023-08-28 12:52:08',NULL),(389,'acro software inc.',238,'2023-08-28 12:52:08',NULL),(390,'lightning uk!',239,'2023-08-28 12:52:08',NULL),(391,'cimco integration i/s',240,'2023-08-28 12:52:08',NULL),(392,'spigot, inc.',241,'2023-08-28 12:52:08',NULL),(393,'bluestack systems, inc.',242,'2023-08-28 12:52:08',NULL),(394,'thecybershadow.net',244,'2023-08-28 12:52:08',NULL),(395,'arm ltd',245,'2023-08-28 12:52:08',NULL),(396,'ge_healthcare',246,'2023-08-28 12:52:08',NULL),(397,'stealth software',247,'2023-08-28 12:52:08',NULL),(398,'vargus ltd.',248,'2023-08-28 12:52:08',NULL),(399,'jetbrains s.r.o.',249,'2023-08-28 12:52:08',NULL),(400,'scendix software-vertriebsges. mbh',251,'2023-08-28 12:52:08',NULL),(401,'imagewriter developers',252,'2023-08-28 12:52:08',NULL),(402,'1c',253,'2023-08-28 12:52:08',NULL),(403,'1с-софт',253,'2023-08-28 12:52:08',NULL),(404,'helmut buhler',254,'2023-08-28 12:52:08',NULL),(405,'apache software foundation',255,'2023-08-28 12:52:08',NULL),(406,'sober lemur s.a.s. di vacondio andrea',256,'2023-08-28 12:52:08',NULL),(407,'nicekit',257,'2023-08-28 12:52:08',NULL),(408,'informatic corp',258,'2023-08-28 12:52:08',NULL),(409,'pcb matrix corp.',259,'2023-08-28 12:52:08',NULL),(410,'corel corporation',260,'2023-08-28 12:52:08',NULL),(411,'winzip computing, inc.',260,'2023-08-28 12:52:08',NULL),(412,'texas instruments inc.',261,'2023-08-28 12:52:08',NULL),(413,'stmicroelectronics',262,'2023-08-28 12:52:08',NULL),(414,'segger',263,'2023-08-28 12:52:08',NULL),(415,'camstudio open source',264,'2023-08-28 12:52:08',NULL),(416,'atmel corporation',265,'2023-08-30 05:43:32','reviakin.a'),(417,'atmel',265,'2023-08-28 12:52:08',NULL),(418,'dxfviewer.com',266,'2023-08-28 12:52:08',NULL),(419,'libusb-win32',267,'2023-08-28 12:52:08',NULL),(420,'megaify software',268,'2023-08-28 12:52:08',NULL),(421,'linear technology corporation',269,'2023-08-28 12:52:08',NULL),(422,'tomasz mon',270,'2023-08-28 12:52:08',NULL),(423,'autodwg',271,'2023-08-28 12:52:08',NULL),(424,'blackhawk',272,'2023-08-28 12:52:08',NULL),(425,'avast software',273,'2023-08-28 12:52:08',NULL),(426,'arm holdings',274,'2023-08-28 12:52:08',NULL),(427,'catalina group ltd',276,'2023-08-28 12:52:08',NULL),(428,'christian taubenheim',279,'2023-08-28 12:52:08',NULL),(429,'avant force',280,'2023-08-28 12:52:08',NULL),(430,'xiph.org',281,'2023-08-28 12:52:08',NULL),(431,'elan digital systems ltd',283,'2023-08-28 12:52:08',NULL),(432,'dmc',284,'2023-08-28 12:52:08',NULL),(433,'riman company',286,'2023-08-28 12:52:08',NULL),(434,'embedded systems academy, inc.',287,'2023-08-28 12:52:08',NULL),(435,'microchip technology inc.',288,'2023-08-28 12:52:08',NULL),(436,'mobile',289,'2023-08-28 12:52:08',NULL),(437,'rig expert ukraine ltd.',290,'2023-08-28 12:52:08',NULL),(438,'kingston digital, inc',291,'2023-08-28 12:52:08',NULL),(439,'phyton',292,'2023-08-28 12:52:08',NULL),(440,'alexey nicolaychuk',293,'2023-08-28 12:52:08',NULL),(441,'simply super software',295,'2023-08-28 12:52:08',NULL),(442,'ооо «техноком»',296,'2023-08-28 12:52:08',NULL),(443,'trend micro',297,'2023-08-28 12:52:08',NULL),(444,'3dvia',298,'2023-08-28 12:52:08',NULL),(445,'power integrations',299,'2023-08-28 12:52:08',NULL),(446,'dmm',300,'2023-08-28 12:52:08',NULL),(447,'dse development',301,'2023-08-28 12:52:08',NULL),(448,'miktex.org',302,'2023-08-28 12:52:08',NULL),(449,'sew-eurodrive gmbh & co kg',303,'2023-08-28 12:52:08',NULL),(450,'jmicron technology corp.',306,'2023-08-28 12:52:08',NULL),(451,'the qt company ltd',307,'2023-08-28 12:52:08',NULL),(452,'planoplan',309,'2023-08-28 12:52:08',NULL),(453,'wibu-systems ag',311,'2023-08-28 12:52:08',NULL),(454,'topcon',312,'2023-08-28 12:52:08',NULL),(455,'webm project',313,'2023-08-28 12:52:08',NULL),(456,'leica geosystems',314,'2023-08-28 12:52:08',NULL),(457,'persistence of vision raytracer pty. ltd.',315,'2023-08-28 12:52:08',NULL),(458,'ibadov tariel',316,'2023-08-28 12:52:08',NULL),(459,'ао \"лаборатория касперского\"',317,'2023-08-28 12:52:08',NULL),(460,'лаборатория касперского',317,'2023-08-28 12:52:08',NULL),(461,'\"лаборатория касперского\"',317,'2023-08-28 12:52:08',NULL),(462,'ao kaspersky lab',317,'2023-08-28 12:52:08',NULL),(463,'ideamk',318,'2023-08-28 12:52:08',NULL),(464,'lancos',319,'2023-08-28 12:52:08',NULL),(465,'atheros communications inc.',320,'2023-08-28 12:52:08',NULL),(466,'ati technologies',322,'2023-08-28 12:52:08',NULL),(467,'evernote corp.',323,'2023-08-28 12:52:08',NULL),(468,'nch software',325,'2023-08-28 12:52:08',NULL),(469,'michael johnson',327,'2023-08-28 12:52:08',NULL),(470,'sublime hq pty ltd',328,'2023-08-28 12:52:08',NULL),(471,'cypress',329,'2023-08-28 12:52:08',NULL),(472,'trimble navigation limited',330,'2023-08-28 12:52:08',NULL),(473,'coocox.org',331,'2023-08-28 12:52:08',NULL),(474,'hddguru',332,'2023-08-28 12:52:08',NULL),(475,'kde',334,'2023-08-28 12:52:08',NULL),(476,'qutim',335,'2023-08-28 12:52:08',NULL),(477,'sdcc.sourceforge.net',336,'2023-08-28 12:52:08',NULL),(478,'image resizer',338,'2023-08-28 12:52:08',NULL),(479,'dl5swb',340,'2023-08-28 12:52:08',NULL),(480,'kingston',341,'2023-08-28 12:52:08',NULL),(481,'cerulean studios, llc',342,'2023-08-28 12:52:08',NULL),(482,'sew eurodrive gmbh',344,'2023-08-28 12:52:08',NULL),(483,'sew eurodrive gmbh & co. kg',345,'2023-08-28 12:52:08',NULL),(484,'stm',346,'2023-08-28 12:52:08',NULL),(485,'gnuwin32',347,'2023-08-28 12:52:08',NULL),(486,'cosmic software',348,'2023-08-28 12:52:08',NULL),(487,'mathew sachin',349,'2023-08-28 12:52:08',NULL),(488,'nmap project',350,'2023-08-28 12:52:08',NULL),(489,'adem group',351,'2023-08-28 12:52:08',NULL),(490,'gal-ana',352,'2023-08-28 12:52:08',NULL),(491,'http://www.flashget.com',353,'2023-08-28 12:52:08',NULL),(492,'llc \"msmp\"',355,'2023-08-28 12:52:08',NULL),(493,'auslogics software pty ltd',356,'2023-08-28 12:52:08',NULL),(494,'auslogics, inc.',356,'2023-08-28 12:52:08',NULL),(495,'widcomm, inc.',357,'2023-08-28 12:52:08',NULL),(496,'odm',359,'2023-08-28 12:52:08',NULL),(497,'heiko sommerfeldt',361,'2023-08-28 12:52:08',NULL),(498,'skillbrains',362,'2023-08-28 12:52:08',NULL),(499,'asmedia technology',363,'2023-08-28 12:52:08',NULL),(500,'milestone systems a/s',364,'2023-08-28 12:52:08',NULL),(501,'softperfect',365,'2023-08-28 12:52:08',NULL),(502,'msi co., ltd',366,'2023-08-28 12:52:08',NULL),(503,'martin malнk - realix',367,'2023-08-28 12:52:08',NULL),(504,'quarta',368,'2023-08-28 12:52:08',NULL),(505,'anaconda, inc.',369,'2023-08-28 12:52:08',NULL),(506,'irfan skiljan',370,'2023-08-28 12:52:08',NULL),(507,'uvnc bvba',371,'2023-08-28 12:52:08',NULL),(508,'альт-инвест',372,'2023-08-28 12:52:08',NULL),(509,'kaiba zax',373,'2023-08-28 12:52:08',NULL),(510,'спутник',374,'2023-08-28 12:52:08',NULL),(511,'hhd software, ltd.',375,'2023-08-28 12:52:08',NULL),(512,'peter mead',376,'2023-08-28 12:52:08',NULL),(513,'bullzip',377,'2023-08-28 12:52:08',NULL),(514,'company',378,'2023-08-28 12:52:08',NULL),(515,'nextcloud gmbh',379,'2023-08-28 12:52:08',NULL),(516,'maël hörz',380,'2023-08-28 12:52:08',NULL),(517,'wolfram research, inc.',381,'2023-08-28 12:52:08',NULL),(518,'us department of commerce ntia/its',382,'2023-08-28 12:52:08',NULL),(519,'gal-ant',383,'2023-08-28 12:52:08',NULL),(520,'lsuper',384,'2023-08-28 12:52:08',NULL),(521,'originlab corporation',385,'2023-08-28 12:52:08',NULL),(522,'adafruit industries llc',386,'2023-08-28 12:52:08',NULL),(523,'linino',387,'2023-08-28 12:52:08',NULL),(524,'craftunique ltd.',388,'2023-08-30 12:54:06','reviakin.a'),(525,'craftunique ltd',388,'2023-08-28 12:52:08',NULL),(526,'novarm',389,'2023-08-28 12:52:08',NULL),(527,'novarm limited',389,'2023-08-28 12:52:08',NULL),(528,'netbeans.org',390,'2023-08-28 12:52:08',NULL),(529,'stardock software, inc.',391,'2023-08-28 12:52:08',NULL),(530,'unetlab',392,'2023-08-28 12:52:08',NULL),(531,'activestate',393,'2023-08-28 12:52:08',NULL),(532,'conemu-maximus5',394,'2023-08-28 12:52:08',NULL),(533,'anvir software',395,'2023-08-28 12:52:08',NULL),(534,'nikolaus brennig',396,'2023-08-28 12:52:08',NULL),(535,'bloodshed software',397,'2023-08-28 12:52:08',NULL),(536,'www.microsip.org',398,'2023-08-28 12:52:08',NULL),(537,'evolus co., ltd.',399,'2023-08-28 12:52:08',NULL),(538,'syntevo gmbh',400,'2023-08-28 12:52:08',NULL),(539,'taoframework',401,'2023-08-28 12:52:08',NULL),(540,'safelyremove.com',402,'2023-08-28 12:52:08',NULL),(541,'avanset',403,'2023-08-28 12:52:08',NULL),(542,'null team impex srl',404,'2023-08-28 12:52:08',NULL),(543,'labcenter electronics',405,'2023-08-28 12:52:08',NULL),(544,'kovid goyal',406,'2023-08-28 12:52:08',NULL),(545,'qualcomm incorporated',407,'2023-08-28 12:52:08',NULL),(546,'citrixonline',408,'2023-08-28 12:52:08',NULL),(547,'whatsapp',409,'2023-08-28 12:52:08',NULL),(548,'bandisoft',410,'2023-08-28 12:52:08',NULL),(549,'quarta-rad',411,'2023-08-28 12:52:08',NULL),(550,'mingw-w64',412,'2023-08-28 12:52:08',NULL),(551,'digital wave ltd',413,'2023-08-28 12:52:08',NULL),(552,'x2go project',414,'2023-08-28 12:52:08',NULL),(553,'atlassian',415,'2023-08-28 12:52:08',NULL),(554,'wargaming.net',416,'2023-08-28 12:52:08',NULL),(555,'airtel-atn',417,'2023-08-28 12:52:08',NULL),(556,'telegram messenger llp',418,'2023-08-28 12:52:08',NULL),(557,'jsc azimut',420,'2023-08-28 12:52:08',NULL),(558,'iteamma development team',421,'2023-08-28 12:52:08',NULL),(559,'pinnacle systems',423,'2023-08-28 12:52:08',NULL),(560,'reganam',424,'2023-08-28 12:52:08',NULL),(561,'eremex',425,'2023-08-28 12:52:08',NULL),(562,'gemalto',426,'2023-08-28 12:52:08',NULL),(563,'адепт проект',427,'2023-08-28 12:52:08',NULL),(564,'адепт',427,'2023-08-28 12:52:08',NULL),(565,'veeam software group gmbh',428,'2023-08-28 12:52:08',NULL),(566,'veeam software ag',428,'2023-08-28 12:52:08',NULL),(567,'artifex software inc.',429,'2023-08-28 12:52:08',NULL),(568,'online center ltd',430,'2023-08-28 12:52:08',NULL),(569,'sarovdaqgroupteam',431,'2023-08-28 12:52:08',NULL),(570,'ascensio system sia.',432,'2023-08-28 12:52:08',NULL),(571,'rocket.chat support',433,'2023-08-28 12:52:08',NULL),(572,'windirstat.net',434,'2023-08-28 12:52:08',NULL),(573,'zoom video communications, inc.',435,'2023-08-28 12:52:08',NULL),(574,'ptc',436,'2023-08-28 12:52:08',NULL),(575,'ghisler software gmbh',437,'2023-08-28 12:52:08',NULL),(576,'at&t research labs.',438,'2023-08-28 12:52:08',NULL),(577,'the orbitum authors',440,'2023-08-28 12:52:08',NULL),(578,'qt project',441,'2023-08-28 12:52:08',NULL),(579,'analog devices',442,'2023-08-28 12:52:08',NULL),(580,'analog devices inc.',442,'2023-08-30 05:32:02','reviakin.a'),(581,'keil - tools by arm',443,'2023-08-28 12:52:08',NULL),(582,'segger microcontroller gmbh',444,'2023-08-28 12:52:08',NULL),(583,'logitech europe s.a.',445,'2023-08-28 12:52:08',NULL),(584,'simetrix technologies ltd',446,'2023-08-28 12:52:08',NULL),(585,'tim kosse',447,'2023-08-28 12:52:08',NULL),(586,'copper mountain technologies, http://www.coppermountaintech.com',448,'2023-08-28 12:52:08',NULL),(587,'crystalidea software, inc.',449,'2023-08-28 12:52:08',NULL),(588,'vs revo group, ltd.',450,'2023-08-28 12:52:08',NULL),(589,'akamai technologies, inc',451,'2023-08-28 12:52:08',NULL),(590,'silicon laboratories, inc.',452,'2023-08-28 12:52:08',NULL),(591,'mysql ab',453,'2023-08-28 12:52:08',NULL),(592,'the code::blocks team',454,'2023-08-28 12:52:08',NULL),(593,'x-tek corporation',455,'2023-08-28 12:52:08',NULL),(594,'mail.ru group',456,'2023-08-28 12:52:08',NULL),(595,'applied radio labs',457,'2023-08-28 12:52:08',NULL),(596,'contaware.com',458,'2023-08-28 12:52:08',NULL),(597,'comap',459,'2023-08-28 12:52:08',NULL),(598,'color pilot',460,'2023-08-28 12:52:08',NULL),(599,'colorpilot',460,'2023-08-28 12:52:08',NULL),(600,'creative technology limited',461,'2023-08-28 12:52:08',NULL),(601,'realvnc ltd',462,'2023-08-28 12:52:08',NULL),(602,'rivet networks',463,'2023-08-28 12:52:08',NULL),(603,'avermedia technologies, inc.',464,'2023-08-28 12:52:08',NULL),(604,'гк сту',465,'2023-08-28 12:52:08',NULL),(605,'ritlabs',466,'2023-08-28 12:52:08',NULL),(606,'the git development community',467,'2023-08-28 12:52:08',NULL),(607,'international geogebra institute',468,'2023-08-28 12:52:08',NULL),(608,'xilinx, inc.',469,'2023-08-28 12:52:08',NULL),(609,'ivosoft',470,'2023-08-28 12:52:08',NULL),(610,'ocbase.com',471,'2023-08-28 12:52:08',NULL),(611,'digilent, inc.',472,'2023-08-28 12:52:08',NULL),(612,'national instruments',473,'2023-08-28 12:52:08',NULL),(613,'softplicity, inc.',474,'2023-08-28 12:52:08',NULL),(614,'software companions',475,'2023-08-28 12:52:08',NULL),(615,'sdi solution',476,'2023-08-28 12:52:08',NULL),(616,'фирма «интеграл»',477,'2023-08-28 12:52:08',NULL),(617,'фирма \"интеграл\"',477,'2023-08-28 12:52:08',NULL),(618,'visualsvn ltd.',478,'2023-08-28 12:52:08',NULL),(619,'advantech automation co., ltd',479,'2023-08-28 12:52:08',NULL),(620,'advantech',479,'2023-08-28 12:52:08',NULL),(621,'flachmann und heggelbacher gbr',480,'2023-08-28 12:52:08',NULL),(622,'lecroy',481,'2023-08-28 12:52:08',NULL),(623,'opc foundation',482,'2023-08-28 12:52:08',NULL),(624,'lira sapr',483,'2023-08-28 12:52:08',NULL),(625,'base',484,'2023-08-28 12:52:08',NULL),(626,'ооо \"фок-софт\"',485,'2023-08-28 12:52:08',NULL),(627,'sofos',486,'2023-08-28 12:52:08',NULL),(628,'msi',487,'2023-08-28 12:52:08',NULL),(629,'devid',488,'2023-08-28 12:52:08',NULL),(630,'ralink',489,'2023-08-28 12:52:08',NULL),(631,'зао нвп болид',490,'2023-08-28 12:52:08',NULL),(632,'© зао нвп болид',490,'2023-08-28 12:52:08',NULL),(633,'bolid',490,'2023-08-28 12:52:08',NULL),(634,'dominik reichl',491,'2023-08-28 12:52:08',NULL),(635,'geek software gmbh',492,'2023-08-28 12:52:08',NULL),(636,'ао \"р7\"',493,'2023-08-28 12:52:08',NULL),(637,'huawei technologies co.,ltd',496,'2023-08-28 12:52:08',NULL),(638,'d-link corporation',499,'2023-08-28 12:52:08',NULL),(639,'philips',520,'2023-08-28 12:52:08',NULL),(640,'dell',521,'2023-08-28 12:52:08',NULL),(641,'2369',523,'2023-08-28 12:52:08',NULL),(642,'2460x',523,'2023-08-28 12:52:08',NULL),(643,'2757m',523,'2023-08-28 12:52:08',NULL),(644,'аос 2470sw',523,'2023-08-28 12:52:08',NULL),(645,'2470sw',523,'2023-08-28 12:52:08',NULL),(646,'2050w',523,'2023-08-28 12:52:08',NULL),(647,'vg2436',524,'2023-08-28 12:52:08',NULL),(648,'vg150m',524,'2023-08-28 12:52:08',NULL),(649,'benq',525,'2023-08-28 12:52:08',NULL),(650,'ips236',526,'2023-08-28 12:52:08',NULL),(651,'zebra technologies corporation',529,'2023-08-28 12:52:08',NULL),(652,'vutlan',538,'2023-08-28 12:52:08',NULL);
/*!40000 ALTER TABLE `manufacturers_dict` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `parent_id` int DEFAULT NULL COMMENT 'источник',
  `date` date NOT NULL COMMENT 'Дата поступления',
  `count` int NOT NULL COMMENT 'Количество',
  `type_id` int NOT NULL COMMENT 'тип материалов',
  `model` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'наименование',
  `places_id` int DEFAULT NULL COMMENT 'помещение',
  `it_staff_id` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cost` float DEFAULT NULL,
  `charge` float DEFAULT NULL,
  `currency_id` int NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-materials-it_staff_id` (`it_staff_id`),
  KEY `idx-materials-places_id` (`places_id`),
  KEY `idx-materials-date` (`date`),
  KEY `idx-materials-currency_id` (`currency_id`),
  KEY `idx-materials-updated_at` (`updated_at`),
  KEY `idx-materials-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES (2,NULL,'2022-09-13',300,2,'Cabeus UTP-4 cat 5.e',6,6,'По результатам инвентаризации. Монтажники походу оставили',NULL,NULL,NULL,1,NULL,NULL),(3,NULL,'2022-09-13',150,2,'Cabeus UTP-4 cat 5.e',6,6,'Походу монтажники оставили. Пол бухты примерно',NULL,NULL,NULL,1,NULL,NULL),(4,3,'2023-03-02',150,2,'Cabeus UTP-4 cat 5.e',8,1,'Передали в Чел. на всякий случай',NULL,NULL,NULL,1,'2025-05-14 06:52:45',NULL),(5,NULL,'2022-02-13',1,3,'Cablexpert TK-NCT-01 обжимка+тестер',8,1,'Купил сам',NULL,1900,316.67,1,NULL,NULL),(9,NULL,'2021-12-28',25,4,'Набор MK270',6,6,'',NULL,28175,4695.83,1,NULL,NULL);
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_history`
--

DROP TABLE IF EXISTS `materials_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `parent_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `count` int DEFAULT NULL,
  `type_id` int DEFAULT NULL,
  `model` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `places_id` int DEFAULT NULL,
  `it_staff_id` int DEFAULT NULL,
  `currency_id` int DEFAULT NULL,
  `cost` float DEFAULT NULL,
  `charge` float DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `contracts_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `usages_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `materials_history-master_id` (`master_id`),
  KEY `materials_history-updated_at` (`updated_at`),
  KEY `materials_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_history`
--

LOCK TABLES `materials_history` WRITE;
/*!40000 ALTER TABLE `materials_history` DISABLE KEYS */;
INSERT INTO `materials_history` VALUES (1,4,'2025-05-14 06:52:45',NULL,NULL,'parent_id,date,count,type_id,model,places_id,it_staff_id,currency_id,comment',3,'2023-03-02',150,2,'Cabeus UTP-4 cat 5.e',8,1,1,NULL,NULL,'Передали в Чел. на всякий случай',NULL,NULL,NULL),(2,9,'2025-05-14 09:15:53',NULL,NULL,'date,count,type_id,model,places_id,it_staff_id,currency_id,cost,charge,contracts_ids,usages_ids',NULL,'2021-12-28',25,4,'Набор MK270',6,6,1,28175,4695.83,NULL,NULL,'4','1'),(3,2,'2025-05-25 16:07:23',NULL,NULL,'date,count,type_id,model,places_id,it_staff_id,currency_id,comment,usages_ids',NULL,'2022-09-13',300,2,'Cabeus UTP-4 cat 5.e',6,6,1,NULL,NULL,'По результатам инвентаризации. Монтажники походу оставили',NULL,NULL,'2');
/*!40000 ALTER TABLE `materials_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_types`
--

DROP TABLE IF EXISTS `materials_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_types` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `code` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `units` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `scans_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-materials_types-code` (`code`),
  KEY `idx-materials_types-name` (`name`),
  KEY `idx-materials_types-updated_at` (`updated_at`),
  KEY `idx-materials_types-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_types`
--

LOCK TABLES `materials_types` WRITE;
/*!40000 ALTER TABLE `materials_types` DISABLE KEYS */;
INSERT INTO `materials_types` VALUES (1,'ssd','SSD','шт','диски на замену',NULL,NULL,NULL),(2,'twistedpair','Витая пара','м','Не патчкорды, а в бухтах',NULL,NULL,NULL),(3,'tools','Инструменты','шт','Ответртки, обжимки, тестеры и т.п.',NULL,'2025-05-14 06:53:25',NULL),(4,'input','Устройства ввода','шт','Клавы, мышки, трекболы и все чем можно вводить инфу',NULL,NULL,NULL);
/*!40000 ALTER TABLE `materials_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_types_history`
--

DROP TABLE IF EXISTS `materials_types_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_types_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `code` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `units` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `scans_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `materials_types_history-master_id` (`master_id`),
  KEY `materials_types_history-updated_at` (`updated_at`),
  KEY `materials_types_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_types_history`
--

LOCK TABLES `materials_types_history` WRITE;
/*!40000 ALTER TABLE `materials_types_history` DISABLE KEYS */;
INSERT INTO `materials_types_history` VALUES (1,3,'2025-05-14 06:53:25',NULL,NULL,'code,name,units,comment','tools','Инструменты','шт','Ответртки, обжимки, тестеры и т.п.',NULL);
/*!40000 ALTER TABLE `materials_types_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_usages`
--

DROP TABLE IF EXISTS `materials_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_usages` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `materials_id` int NOT NULL COMMENT 'Материал',
  `count` int NOT NULL COMMENT 'Количество',
  `date` date NOT NULL COMMENT 'Дата расхода',
  `techs_id` int DEFAULT NULL COMMENT 'Оборудование',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-materials_usages-materials_id` (`materials_id`),
  KEY `idx-materials_usages-techs_id` (`techs_id`),
  KEY `idx-materials_usages-updated_at` (`updated_at`),
  KEY `idx-materials_usages-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_usages`
--

LOCK TABLES `materials_usages` WRITE;
/*!40000 ALTER TABLE `materials_usages` DISABLE KEYS */;
INSERT INTO `materials_usages` VALUES (1,9,1,'2022-12-29',6,'Замена вышедшей из строя клавиатуры','Пользователь пролил кофе на клавиатуру','2025-05-14 09:15:53',NULL),(2,2,10,'2025-05-25',41,'Проложил отдельную витуху 10м до места установки','','2025-05-25 16:07:23',NULL);
/*!40000 ALTER TABLE `materials_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_usages_history`
--

DROP TABLE IF EXISTS `materials_usages_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_usages_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `materials_id` int DEFAULT NULL,
  `count` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `techs_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `materials_usages_history-master_id` (`master_id`),
  KEY `materials_usages_history-updated_at` (`updated_at`),
  KEY `materials_usages_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_usages_history`
--

LOCK TABLES `materials_usages_history` WRITE;
/*!40000 ALTER TABLE `materials_usages_history` DISABLE KEYS */;
INSERT INTO `materials_usages_history` VALUES (1,1,'2025-05-14 09:15:53',NULL,NULL,'materials_id,count,date,techs_id,comment,history',9,1,'2022-12-29',6,'Замена вышедшей из строя клавиатуры','Пользователь пролил кофе на клавиатуру'),(2,2,'2025-05-25 16:07:23',NULL,NULL,'materials_id,count,date,techs_id,comment',2,10,'2025-05-25',41,'Проложил отдельную витуху 10м до места установки',NULL);
/*!40000 ALTER TABLE `materials_usages_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration`
--

DROP TABLE IF EXISTS `migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration` (
  `version` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apply_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration`
--

LOCK TABLES `migration` WRITE;
/*!40000 ALTER TABLE `migration` DISABLE KEYS */;
INSERT INTO `migration` VALUES ('app\\migrations\\m000000_000000_base',1693800995),('app\\migrations\\m140506_102106_rbac_init',1693800996),('app\\migrations\\m170907_052038_rbac_add_index_on_auth_assignment_user_id',1693800996),('app\\migrations\\m180101_010101_initial',1693801002),('app\\migrations\\m180523_151638_rbac_updates_indexes_without_prefix',1693800996),('app\\migrations\\m190101_100000_update0',1693801003),('app\\migrations\\m190101_100001_update1',1693801003),('app\\migrations\\m190101_100002_update2',1693801004),('app\\migrations\\m190101_100003_update3',1693801004),('app\\migrations\\m190101_100004_update4',1693801004),('app\\migrations\\m190101_100005_update5',1693801004),('app\\migrations\\m190101_100006_update6',1693801005),('app\\migrations\\m190101_100007_update7',1693801005),('app\\migrations\\m190101_100008_update8',1693801006),('app\\migrations\\m190101_100009_update9',1693801006),('app\\migrations\\m191101_192502_departments',1693801006),('app\\migrations\\m191103_084000_alter_updatetAt_column_to_arms_table',1693801006),('app\\migrations\\m191103_084732_add_department_column_to_arms_table',1693801006),('app\\migrations\\m191103_100000_alter_users_columns_to_arms_table',1693801007),('app\\migrations\\m191103_203015_add_procedures_for_places',1693801007),('app\\migrations\\m191106_115822_add_total_column_to_contracts_table',1693801007),('app\\migrations\\m191119_145841_add_cost_column_to_org_inet_table',1693801007),('app\\migrations\\m191119_172027_add_charge_column_to_org_inet_table',1693801007),('app\\migrations\\m191119_172409_add_charge_column_to_contracts_table',1693801007),('app\\migrations\\m191120_062411_float_prices',1693801008),('app\\migrations\\m191120_095815_add_cost_column_to_org_phones_table',1693801008),('app\\migrations\\m191204_062411_decimal_prices',1693801009),('app\\migrations\\m191208_164401_add_default_ip_values_in_comps',1693801009),('app\\migrations\\m191208_173041_fix_many_2_many',1693801009),('app\\migrations\\m191208_173041_fix_users_id',1693801009),('app\\migrations\\m191219_100000_add_users_employ_date',1693801009),('app\\migrations\\m191219_100001_fix_materials_id',1693801009),('app\\migrations\\m191219_100002_fix_contracts_in_materials_id',1693801010),('app\\migrations\\m200121_080000_add_users_auth_key',1693801010),('app\\migrations\\m200317_033238_create_user_in_services',1693801010),('app\\migrations\\m200317_040048_create_table_schedules',1693801010),('app\\migrations\\m200317_043845_alter_services_table',1693801010),('app\\migrations\\m200409_110543_rbac_update_mssql_trigger',1693800996),('app\\migrations\\m200508_064827_create_table_segments',1693801010),('app\\migrations\\m200508_160608_alter_table_services',1693801011),('app\\migrations\\m200525_200810_create_table_techs_in_services',1693801011),('app\\migrations\\m200616_205619_alter_table_techs_format_mac',1693801011),('app\\migrations\\m200712_185556_add_permissions',1693801011),('app\\migrations\\m200727_123910_alter_table_comps_add_user',1693801011),('app\\migrations\\m201023_064548_contracts_sucessor_default',1693801011),('app\\migrations\\m201024_153753_add_tech_specs',1693801011),('app\\migrations\\m201025_174509_add_tech_model_specs',1693801011),('app\\migrations\\m201202_154535_alter_lic_types_add_links',1693801011),('app\\migrations\\m210214_154227_table_net_domains',1693801011),('app\\migrations\\m210216_155422_table_net_vlans',1693801011),('app\\migrations\\m210216_165001_table_networks',1693801011),('app\\migrations\\m210220_133458_alter_table_segments',1693801011),('app\\migrations\\m210220_171805_create_table_netAddr',1693801011),('app\\migrations\\m210222_174038_alter_table_net_ips',1693801011),('app\\migrations\\m210228_121450_table_ports',1693801012),('app\\migrations\\m210301_135145_alter_table_tech_models',1693801012),('app\\migrations\\m210302_161545_alter_table_net_ips',1693801012),('app\\migrations\\m210310_174301_move_vlans_link',1693801012),('app\\migrations\\m210310_184119_alter_comment_column_in_soft_table',1693801012),('app\\migrations\\m210612_143410_alter_techs_table',1693801012),('app\\migrations\\m210614_063518_create_table_schedules',1693801012),('app\\migrations\\m210614_150516_alter_table_schedules',1693801012),('app\\migrations\\m210617_064650_alter_table_segments',1693801013),('app\\migrations\\m210621_131426_alter_table_services',1693801013),('app\\migrations\\m210716_120416_alter_table_comps',1693801013),('app\\migrations\\m210824_132508_alter_table_scans',1693801013),('app\\migrations\\m210825_125020_create_table_access',1693801014),('app\\migrations\\m210825_130339_alter_table_scans',1693801014),('app\\migrations\\m210831_093619_alter_table_users',1693801014),('app\\migrations\\m210911_113706_alter_table_services',1693801014),('app\\migrations\\m210921_035506_create_table_currency',1693801014),('app\\migrations\\m211002_062719_alter_table_services',1693801014),('app\\migrations\\m211003_141509_alter_table_partners',1693801014),('app\\migrations\\m220117_054532_add_services_recursive_segment_search',1693801014),('app\\migrations\\m220303_120730_alter_table_orgphones',1693801014),('app\\migrations\\m220303_191454_alter_table_org_inets',1693801014),('app\\migrations\\m220327_073551_alter_table_comps',1693801014),('app\\migrations\\m220329_055419_alter_table_users',1693801014),('app\\migrations\\m220402_185406_alter_table_schedules',1693801015),('app\\migrations\\m220410_134409_alter_table_services',1693801015),('app\\migrations\\m220414_105653_alter_tables_lics',1693801015),('app\\migrations\\m220416_120817_alter_tables_lics',1693801015),('app\\migrations\\m220421_075705_alter_table_org_inets',1693801015),('app\\migrations\\m220504_172124_alter_tables_lics',1693801016),('app\\migrations\\m220525_125054_alter_tables_partners',1693801016),('app\\migrations\\m220630_173032_alter_tables_prov_tel',1693801016),('app\\migrations\\m220816_104950_add_weight_column_to_services_table',1693801016),('app\\migrations\\m220818_073405_alter_table_users',1693801016),('app\\migrations\\m220819_132459_alter_table_net_domains',1693801016),('app\\migrations\\m220916_122729_add_mac_column_to_arms_table',1693801016),('app\\migrations\\m220929_173411_add_cost_column_to_materials_table',1693801016),('app\\migrations\\m221007_163802_add_archive_columns',1693801016),('app\\migrations\\m221024_153826_add_comment_column_to_places_table',1693801016),('app\\migrations\\m221111_174828_alter_table_access_types',1693801016),('app\\migrations\\m221122_151334_alter_table_ports',1693801016),('app\\migrations\\m230109_130226_alter_table_techs',1693801016),('app\\migrations\\m230206_063303_alter_table_comps',1693801016),('app\\migrations\\m230223_090652_alter_table_techs',1693801017),('app\\migrations\\m230223_102334_alter_table_tech_types',1693801017),('app\\migrations\\m230224_080124_alter_table_tech_models_add_racks',1693801017),('app\\migrations\\m230224_081112_migrate_arms2techs',1693801017),('app\\migrations\\m230302_180857_create_tables_dynagrid',1693801017),('app\\migrations\\m230321_054524_alter_table_comps',1693801017),('app\\migrations\\m230413_101124_alter_table_techs_add_pos_end',1693801017),('app\\migrations\\m230511_094545_alter_table_login_journal',1693801017),('app\\migrations\\m230512_124513_alter_table_login_journal',1693801017),('app\\migrations\\m230513_125905_create_table_attaches',1693801018),('app\\migrations\\m230520_060357_alter_table_attaches',1693801018),('app\\migrations\\m230520_060415_users_in_contracts',1693801018),('app\\migrations\\m230520_101000_alter_table_attaches',1693801018),('app\\migrations\\m230526_181446_alter_table_services',1693801018),('app\\migrations\\m230527_052818_add_external_links',1693801018),('app\\migrations\\m230531_100639_alter_table_users',1693801018),('app\\migrations\\m230620_113027_create_table_ips_in_users',1693801018),('app\\migrations\\m230622_170155_alter_table_comps',1693801018),('app\\migrations\\m230628_041251_create_table_org_inets_in_networks',1693801018),('app\\migrations\\m230708_045732_alter_table_partners',1693801018),('app\\migrations\\m230713_070612_alter_table_techs',1693801018),('app\\migrations\\m230802_162919_alter_table_networks',1693801018),('app\\migrations\\m230821_160259_init_empty_tables',1693801019),('app\\migrations\\m230828_123950_sync_prepare_2',1693801019),('app\\migrations\\m230831_174800_sync_prepare_3',1693801019),('app\\migrations\\m230903_074600_sync_prepare_4',1693801019),('app\\migrations\\m230903_114346_local_auth',1693836271),('app\\migrations\\m230905_045527_sync_prepare_5',1694186973),('app\\migrations\\m230923_092107_user_sync_prepare',1696260413),('app\\migrations\\m231006_070638_user_rest_unify',1698205001),('app\\migrations\\m231020_074646_alter_table_org_struct',1698205001),('app\\migrations\\M231109084405FixAutoincrement',1701232623),('app\\migrations\\M231209133554AlterTableNetworks',1702308017),('app\\migrations\\M231217071124AlterTableSegments',1702809741),('app\\migrations\\M231226142737CreateTableJobs',1705863174),('app\\migrations\\M240123153514UpdateTableDocs',1707115301),('app\\migrations\\M240125162320UpdateTableTechs',1707115301),('app\\migrations\\M240127160603HistoryJournals',1707115302),('app\\migrations\\M240128150114HistoryJournalsContracts',1707115303),('app\\migrations\\M240129130314HistoryJournalsTechs',1707115303),('app\\migrations\\M240201144730MaintenanceUpdate',1707115304),('app\\migrations\\M240203053203HistoryJournalsMaterials',1707115307),('app\\migrations\\M240225074103HistoryJournalsAcls',1709223405),('app\\migrations\\M240229060301HistoryJournalsAclsFix',1709223405),('app\\migrations\\M240308075641PlacesMap',1710320479),('app\\migrations\\M240328034135CompsHistory',1722769178),('app\\migrations\\M240401113410ServiceConnections',1722769178),('app\\migrations\\M240518080913CreateSandboxes',1722769179),('app\\migrations\\M240526102940MaintenanceJobsReqsHistory',1722769179),('app\\migrations\\M240612053628AclExtend',1722769180),('app\\migrations\\M240725041322CleanUnused',1722769180),('app\\migrations\\M240730162325CompsAdmins',1722769180),('app\\migrations\\M240802093936PartnersAliases',1722769181),('app\\migrations\\M241015093726ContractsHistoryAddChildren',1746711711),('app\\migrations\\M241225123824MaintenanceDescr',1746711711),('app\\migrations\\M250205141617CompsRescanQueue',1746711711),('app\\migrations\\M250224152754TechsSupportService',1746711711),('app\\migrations\\M250413161054SoftAddLinks',1746711712),('app\\migrations\\M250414164449ScansAddSoft',1746711712),('app\\migrations\\M250425033845CompsSoftMediumtext',1746711712),('app\\migrations\\M250505122356WikiCache',1746711712),('app\\migrations\\M250514090728ContractsHistorySucessorFix',1747214006),('app\\migrations\\M250526150239MaintenanceJobsHierachy',1748273958),('app\\migrations\\M250805150713LoginJournalIndexes',1755942761),('app\\migrations\\M250806065520LoginJournalCalcTime',1755942761),('app\\migrations\\M250828100718AdditionalHistory',1756450679);
/*!40000 ALTER TABLE `migration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `net_domains`
--

DROP TABLE IF EXISTS `net_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `net_domains` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `places_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `net_domains-places-idx` (`places_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `net_domains`
--

LOCK TABLES `net_domains` WRITE;
/*!40000 ALTER TABLE `net_domains` DISABLE KEYS */;
INSERT INTO `net_domains` VALUES (1,'msk_dom','Московский L2 Домен',1),(2,'chl_dom','',7);
/*!40000 ALTER TABLE `net_domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `net_ips`
--

DROP TABLE IF EXISTS `net_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `net_ips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `addr` int unsigned DEFAULT NULL,
  `mask` int DEFAULT NULL,
  `text_addr` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `networks_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-net_ips-addr` (`addr`),
  KEY `idx-net_ips-mask` (`mask`),
  KEY `idx-net_ips-text_addr` (`text_addr`),
  KEY `idx-net_ips-networks_id` (`networks_id`),
  CONSTRAINT `fk-net_ips-networks_id` FOREIGN KEY (`networks_id`) REFERENCES `networks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `net_ips`
--

LOCK TABLES `net_ips` WRITE;
/*!40000 ALTER TABLE `net_ips` DISABLE KEYS */;
INSERT INTO `net_ips` VALUES (1,169083146,NULL,'10.20.1.10','','',12),(2,169083147,NULL,'10.20.1.11','','',12),(3,169083166,NULL,'10.20.1.30',NULL,NULL,12),(4,169083167,NULL,'10.20.1.31',NULL,NULL,12),(8,169108506,NULL,'10.20.100.26',NULL,NULL,13),(9,169108510,NULL,'10.20.100.30',NULL,NULL,13),(10,171074584,NULL,'10.50.100.24',NULL,NULL,5),(11,171074588,NULL,'10.50.100.28',NULL,NULL,5),(12,171074591,NULL,'10.50.100.31',NULL,NULL,5),(13,171074600,NULL,'10.50.100.40',NULL,NULL,5),(14,171061770,NULL,'10.50.50.10',NULL,NULL,6),(15,3232235521,NULL,'192.168.0.1',NULL,NULL,NULL),(16,171051534,NULL,'10.50.10.14',NULL,NULL,NULL),(18,169102100,NULL,'10.20.75.20',NULL,NULL,1),(19,169108746,NULL,'10.20.101.10',NULL,NULL,9),(20,169108748,NULL,'10.20.101.12',NULL,NULL,9),(21,169108786,NULL,'10.20.101.50',NULL,NULL,9),(22,171049226,NULL,'10.50.1.10',NULL,NULL,4),(23,171049248,NULL,'10.50.1.32',NULL,NULL,4),(24,1112365154,NULL,'66.77.88.98',NULL,NULL,17),(25,927092045,NULL,'55.66.77.77',NULL,NULL,11),(26,927092049,NULL,'55.66.77.81',NULL,NULL,10),(27,171049247,NULL,'10.50.1.31',NULL,NULL,4),(28,169083137,NULL,'10.20.1.1',NULL,NULL,12),(29,169083138,NULL,'10.20.1.2',NULL,NULL,12),(30,167772162,NULL,'10.0.0.2',NULL,NULL,18),(31,167772161,NULL,'10.0.0.1',NULL,NULL,18),(32,171049217,NULL,'10.50.1.1',NULL,NULL,4),(33,171049218,NULL,'10.50.1.2',NULL,NULL,4),(34,171049227,NULL,'10.50.1.11',NULL,NULL,4),(35,171049266,NULL,'10.50.1.50',NULL,NULL,4),(36,169083186,NULL,'10.20.1.50',NULL,NULL,12),(38,171068164,NULL,'10.50.75.4',NULL,NULL,2),(39,169108490,NULL,'10.20.100.10',NULL,NULL,13),(40,171074570,NULL,'10.50.100.10',NULL,NULL,5),(41,169091338,NULL,'10.20.33.10',NULL,NULL,19),(42,169091340,NULL,'10.20.33.12',NULL,NULL,19),(43,169091341,NULL,'10.20.33.13',NULL,NULL,19),(44,169091343,NULL,'10.20.33.15',NULL,NULL,19),(46,171059212,NULL,'10.50.40.12',NULL,NULL,7),(47,169093130,NULL,'10.20.40.10',NULL,NULL,15),(48,169093132,NULL,'10.20.40.12',NULL,NULL,15),(49,171059210,NULL,'10.50.40.10',NULL,NULL,7),(50,927092058,NULL,'55.66.77.90',NULL,NULL,10),(51,927092050,NULL,'55.66.77.82',NULL,NULL,10),(52,169083236,NULL,'10.20.1.100',NULL,NULL,12),(53,169083237,NULL,'10.20.1.101',NULL,NULL,12),(54,169084683,NULL,'10.20.7.11',NULL,NULL,16),(55,169084684,NULL,'10.20.7.12',NULL,NULL,16),(56,169084685,NULL,'10.20.7.13',NULL,NULL,16),(57,169084686,NULL,'10.20.7.14',NULL,NULL,16),(58,169084675,NULL,'10.20.7.3',NULL,NULL,16),(59,169102090,NULL,'10.20.75.10',NULL,NULL,1),(60,169102096,NULL,'10.20.75.16',NULL,NULL,1),(61,169102091,NULL,'10.20.75.11',NULL,NULL,1),(62,171050763,NULL,'10.50.7.11',NULL,NULL,8),(63,171050764,NULL,'10.50.7.12',NULL,NULL,8),(64,171050766,NULL,'10.50.7.14',NULL,NULL,8),(65,171050768,NULL,'10.50.7.16',NULL,NULL,8),(66,171050770,NULL,'10.50.7.18',NULL,NULL,8),(67,169108505,NULL,'10.20.100.25',NULL,NULL,13),(69,169084673,NULL,'10.20.7.1',NULL,NULL,16),(70,169091329,NULL,'10.20.33.1',NULL,NULL,19),(71,169093121,NULL,'10.20.40.1',NULL,NULL,15),(72,169102081,NULL,'10.20.75.1',NULL,NULL,1),(73,169108481,NULL,'10.20.100.1',NULL,NULL,13),(74,169108737,NULL,'10.20.101.1',NULL,NULL,9),(75,171061764,NULL,'10.50.50.4','','gorodnov_ovpn',6),(76,171061765,NULL,'10.50.50.5','','barinov_ovpn',6),(77,171061766,NULL,'10.50.50.6','','levchenko_ovpn',6),(78,169108503,NULL,'10.20.100.23',NULL,NULL,13),(80,171056704,NULL,'10.50.30.64',NULL,NULL,3),(81,171050767,NULL,'10.50.7.15',NULL,NULL,8),(82,171056731,NULL,'10.50.30.91',NULL,NULL,3),(83,169083176,NULL,'10.20.1.40',NULL,NULL,12);
/*!40000 ALTER TABLE `net_ips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `net_vlans`
--

DROP TABLE IF EXISTS `net_vlans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `net_vlans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vlan` int DEFAULT NULL,
  `domain_id` int DEFAULT NULL,
  `segment_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx-net_vlans-domain_id` (`domain_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `net_vlans`
--

LOCK TABLES `net_vlans` WRITE;
/*!40000 ALTER TABLE `net_vlans` DISABLE KEYS */;
INSERT INTO `net_vlans` VALUES (1,'msk_ovpn_vlan',50,1,NULL,''),(2,'msk_open_vlan',100,1,NULL,''),(3,'msk_closed_vlan',101,1,NULL,''),(4,'msk_dmz_vlan',200,1,NULL,''),(5,'chl_ovpn_vlan',50,2,NULL,''),(6,'chl_srv_vlan',75,2,NULL,''),(7,'chl_open_lan',100,2,NULL,''),(8,'msk_srv_vlan',75,1,NULL,''),(9,'chl_it_vlan',30,2,NULL,'для сети ИТ'),(10,'msk_voip_vlan',7,1,NULL,''),(11,'chl_voip_vlan',7,2,NULL,''),(12,'chl_mgmt_clan',1,2,NULL,''),(13,'chl_prn_vlan',40,2,NULL,''),(15,'msk_mgmt_vlan',1,1,NULL,''),(16,'msk_prn_vlan',40,1,NULL,''),(17,'msk_prov1_vlan',201,1,NULL,''),(18,'chl_prov1_vlan',399,2,NULL,''),(20,'msk_surv_vlan',90,1,NULL,'');
/*!40000 ALTER TABLE `net_vlans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `networks`
--

DROP TABLE IF EXISTS `networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `networks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vlan_id` int DEFAULT NULL,
  `text_addr` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `addr` int unsigned DEFAULT NULL,
  `mask` int unsigned DEFAULT NULL,
  `router` int unsigned DEFAULT NULL,
  `dhcp` int unsigned DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `segments_id` int DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `archived` tinyint(1) DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ranges` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `text_dhcp` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-networks-vlan_id` (`vlan_id`),
  KEY `idx-networks-addr` (`addr`),
  KEY `idx-networks-text_addr` (`text_addr`),
  KEY `idx-networks-mask` (`mask`),
  KEY `idx-networks-router` (`router`),
  KEY `idx-networks-dhcp` (`dhcp`),
  KEY `idx-networks-segments_id` (`segments_id`),
  KEY `idx-networks-archived` (`archived`),
  KEY `idx-networks-updated_at` (`updated_at`),
  KEY `idx-networks-updated_by` (`updated_by`),
  CONSTRAINT `fk-networks-segments_id` FOREIGN KEY (`segments_id`) REFERENCES `segments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `networks`
--

LOCK TABLES `networks` WRITE;
/*!40000 ALTER TABLE `networks` DISABLE KEYS */;
INSERT INTO `networks` VALUES (1,'MSK_SRV_LAN',8,'10.20.75.0/24',169102080,24,169102081,169085450,'Сеть общих сервисов Мск',NULL,'',0,'','1-50 static\r\n51-100 dhcp','10.20.75.10\n10.20.75.11',NULL,NULL),(2,'CHL_SRV_LAN',6,'10.50.75.0/24',171068160,24,171068161,171068170,'Сеть общих сервисов Чел',NULL,'',0,'','1-50 static\r\n51-100 dhcp','10.50.75.10',NULL,NULL),(3,'CHL_IT_LAN',9,'10.50.30.0/24',171056640,24,171056641,171068170,'IT сеть Челябинск',NULL,'',0,'','1-50 static\r\n51-100 dhcp','10.50.75.10',NULL,NULL),(4,'CHL_MGMT_LAN',12,'10.50.1.0/24',171049216,24,171049217,171068170,'Сеть управления Чел',NULL,'',0,'','1-100 static\r\n101-200 dhcp','10.50.75.10',NULL,NULL),(5,'CHL_OPEN_LAN',7,'10.50.100.0/23',171074560,23,171074561,171068170,'Открытая сеть',NULL,'### IPAM\r\n  * Сервера - до 20го адреса включительно\r\n  * Клиенты - от 21 до 510',0,'','1-20 static servers\r\n21-100 dhcp clients','10.50.75.10',NULL,NULL),(6,'CHL_OVPN_LAN',5,'10.50.50.0/24',171061760,24,171061761,171061761,'Сеть клиентов OpenVPN Чел',NULL,'### IPAM\r\nНа OpenVPN за каждым клиентом фиксируется статический адрес через CCD\r\n\r\n### Дополнительно\r\nКаждый клиент может получить дополнительный доступ на фаерволе на уровне IP после согласования служебки через ИБ',NULL,NULL,NULL,'10.50.50.1',NULL,NULL),(7,'CHL_PRN_LAN',13,'10.50.40.0/24',171059200,24,171059201,171068170,'Сеть принтеров Чел',NULL,'',NULL,NULL,NULL,'10.50.75.10',NULL,NULL),(8,'CHL_VOIP_LAN',11,'10.50.7.0/24',171050752,24,171050753,171068170,'Сеть телефонии Чел',NULL,'',NULL,NULL,NULL,'10.50.75.10',NULL,NULL),(9,'MSK_CLOSED_LAN',3,'10.20.101.0/24',169108736,24,169108737,169102090,'Закрытая сеть Мск',NULL,'',0,'','1-50 static\r\n51-100 dhcp','10.20.75.10\n10.20.75.11',NULL,NULL),(10,'MSK_DMZ_LAN',4,'55.66.77.80/28',927092048,28,927092049,NULL,'Сеть DMZ сервисов Мск',NULL,'',NULL,NULL,NULL,'',NULL,NULL),(11,'MSK_DOMRU_LAN',17,'55.66.77.76/30',927092044,30,927092045,NULL,'Подключение Домр.РУ Мск',NULL,'',NULL,NULL,NULL,'',NULL,NULL),(12,'MSK_MGMT_LAN',15,'10.20.1.0/24',169083136,24,169083137,169102090,'Сеть управления Мск',NULL,'',0,'','1-50 static\r\n51-200 dhcp','10.20.75.10\n10.20.75.11',NULL,NULL),(13,'MSK_OPEN_LAN',2,'10.20.100.0/24',169108480,24,169108481,169102090,'Открытая сеть Мск',NULL,'',0,'','1-20 static\r\n21-100 dhcp','10.20.75.10\n10.20.75.11',NULL,NULL),(14,'MSK_OVPN_LAN',1,'10.20.50.0/24',169095680,24,169095681,169095681,'Сеть OpenVPN МСК',NULL,'### Назначение сети\r\nПодключение клиентов в изолированную сеть с доступом до ограниченного количества ресурсов\r\n\r\n### Доступ\r\n- Терминальный сервер 1С\r\n- Телефония\r\n- Мониторинг\r\n\r\nвсе остальные подключения только через служебку согласованную в ИБ\r\n',0,'','1-20 reserved\r\n21-40 admin\r\n41-100 users','10.20.50.1',NULL,NULL),(15,'MSK_PRN_LAN',16,'10.20.40.0/24',169093120,24,169093121,169102090,'Сеть принтеров Мск',NULL,'',0,'','','10.20.75.10\n10.20.75.11',NULL,NULL),(16,'MSK_VOIP_LAN',10,'10.20.7.0/24',169084672,24,169084673,169102090,'Сеть телефонии Мск',NULL,'',0,'','','10.20.75.10\n10.20.75.11',NULL,NULL),(17,'CHL_RT_LAN',18,'66.77.88.96/30',1112365152,30,1112365153,NULL,'Челябинский ввод интернет',NULL,'',NULL,NULL,NULL,'',NULL,NULL),(18,'S2S_VPN_LAN',NULL,'10.0.0.0/30',167772160,30,NULL,NULL,'Для соединения VPN серверов между собой',11,'',NULL,NULL,NULL,NULL,NULL,NULL),(19,'MSK_SURV_LAN',20,'10.20.33.0/24',169091328,24,169091329,169102090,'Сеть видеонаблюдения',12,'',0,'','1-9 static\r\n10-100 dhcp','10.20.75.10','2025-08-29 07:41:54',NULL);
/*!40000 ALTER TABLE `networks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `networks_history`
--

DROP TABLE IF EXISTS `networks_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `networks_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  `updated_comment` varchar(255) DEFAULT NULL,
  `changed_attributes` text,
  `name` varchar(255) DEFAULT NULL,
  `text_addr` varchar(255) DEFAULT NULL,
  `text_router` varchar(255) DEFAULT NULL,
  `text_dhcp` varchar(255) DEFAULT NULL,
  `comment` text,
  `notepad` text,
  `ranges` text,
  `links` text,
  `archived` tinyint(1) DEFAULT NULL,
  `vlan_id` int DEFAULT NULL,
  `segments_id` int DEFAULT NULL,
  `org_inets_ids` text,
  PRIMARY KEY (`id`),
  KEY `networks_history-master_id` (`master_id`),
  KEY `networks_history-updated_at` (`updated_at`),
  KEY `networks_history-updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `networks_history`
--

LOCK TABLES `networks_history` WRITE;
/*!40000 ALTER TABLE `networks_history` DISABLE KEYS */;
INSERT INTO `networks_history` VALUES (1,19,'2025-08-29 07:41:54',NULL,NULL,'name,text_addr,text_router,text_dhcp,comment,ranges,archived,vlan_id,segments_id','MSK_SURV_LAN','10.20.33.0/24','10.20.33.1','10.20.75.10','Сеть видеонаблюдения',NULL,'1-9 static\r\n10-100 dhcp',NULL,0,20,12,NULL);
/*!40000 ALTER TABLE `networks_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `networks_in_aces`
--

DROP TABLE IF EXISTS `networks_in_aces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `networks_in_aces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `aces_id` int DEFAULT NULL,
  `networks_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networks_in_aces-m2m` (`aces_id`,`networks_id`),
  KEY `networks_in_aces-aces_id` (`aces_id`),
  KEY `networks_in_aces-networks_id` (`networks_id`),
  CONSTRAINT `fk-networks_in_aces-aces_id` FOREIGN KEY (`aces_id`) REFERENCES `aces` (`id`),
  CONSTRAINT `fk-networks_in_aces-networks_id` FOREIGN KEY (`networks_id`) REFERENCES `networks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `networks_in_aces`
--

LOCK TABLES `networks_in_aces` WRITE;
/*!40000 ALTER TABLE `networks_in_aces` DISABLE KEYS */;
/*!40000 ALTER TABLE `networks_in_aces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `org_inet`
--

DROP TABLE IF EXISTS `org_inet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `org_inet` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Имя',
  `ip_addr` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP Адрес',
  `ip_mask` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Маска подсети',
  `ip_gw` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Шлюз по умолчанию',
  `ip_dns1` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '1й DNS сервер',
  `ip_dns2` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '2й DNS сервер',
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Тип подключения',
  `static` tinyint(1) DEFAULT NULL COMMENT 'Статический?',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Дополнительно',
  `places_id` int DEFAULT NULL COMMENT 'Помещение',
  `account` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Аккаунт, л/с',
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'История',
  `cost` decimal(15,2) DEFAULT NULL,
  `charge` decimal(15,2) DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `networks_id` int DEFAULT NULL,
  `archived` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `places_id` (`places_id`),
  KEY `services_id` (`services_id`),
  KEY `networks_id` (`networks_id`),
  KEY `org_inet_archived_index` (`archived`),
  CONSTRAINT `org_inet_ibfk_1` FOREIGN KEY (`places_id`) REFERENCES `places` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `org_inet`
--

LOCK TABLES `org_inet` WRITE;
/*!40000 ALTER TABLE `org_inet` DISABLE KEYS */;
INSERT INTO `org_inet` VALUES (1,'Москва - Домру',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'100 мбит/с, оптика, DMZ\r\nпредоплата',1,'120000234/vip','',10100.00,2020.00,1,NULL,0),(2,'Челябинск - РТ',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'100 Мбит/с, оптика, \r\nПостоплата 20го числа',7,'19000003555','Порт 19 циски',17500.00,3500.00,2,NULL,0);
/*!40000 ALTER TABLE `org_inet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `org_inets_in_networks`
--

DROP TABLE IF EXISTS `org_inets_in_networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `org_inets_in_networks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `org_inets_id` int DEFAULT NULL,
  `networks_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-org_inets_in_networks-networks` (`networks_id`),
  KEY `idx-org_inets_in_networks-inets` (`org_inets_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `org_inets_in_networks`
--

LOCK TABLES `org_inets_in_networks` WRITE;
/*!40000 ALTER TABLE `org_inets_in_networks` DISABLE KEYS */;
INSERT INTO `org_inets_in_networks` VALUES (5,2,17),(6,1,11),(7,1,10);
/*!40000 ALTER TABLE `org_inets_in_networks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `org_phones`
--

DROP TABLE IF EXISTS `org_phones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `org_phones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `country_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код страны',
  `city_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код города',
  `local_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Местный номер',
  `places_id` int DEFAULT NULL COMMENT 'Помещение',
  `account` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Комментарий',
  `cost` decimal(15,2) DEFAULT NULL,
  `charge` decimal(15,2) DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `archived` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `country_code` (`country_code`),
  KEY `city_code` (`city_code`),
  KEY `local_code` (`local_code`),
  KEY `places_id` (`places_id`),
  KEY `services_id` (`services_id`),
  KEY `org_phones_archived_index` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Городские телефонные номера в организации';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `org_phones`
--

LOCK TABLES `org_phones` WRITE;
/*!40000 ALTER TABLE `org_phones` DISABLE KEYS */;
INSERT INTO `org_phones` VALUES (1,'7','495','1234567',1,'00017501','',1000.00,166.67,2,0),(2,'7','499','1234567',1,'00017503','',750.00,125.00,2,0),(3,'7','495','7777777',1,'00017502','Отказались от этого номера',5000.00,833.33,2,1),(4,'7','351','1234567',7,'00017505','',500.00,83.33,2,0),(5,'7','351','555555',7,'00017504','Отказались от него',800.00,133.33,2,1);
/*!40000 ALTER TABLE `org_phones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `org_struct`
--

DROP TABLE IF EXISTS `org_struct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `org_struct` (
  `hr_id` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `org_id` int NOT NULL DEFAULT '1' COMMENT 'Организация',
  `parent_hr_id` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `pup` (`parent_hr_id`),
  KEY `orgStruct-org-index` (`org_id`),
  KEY `idx-org_struct-parent_id` (`parent_id`),
  KEY `idx-org_struct-id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Структурные подразделения';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `org_struct`
--

LOCK TABLES `org_struct` WRITE;
/*!40000 ALTER TABLE `org_struct` DISABLE KEYS */;
INSERT INTO `org_struct` VALUES ('1',1,NULL,'Бухгалтерия',NULL,1),('2',1,'','Отдел продаж',NULL,2),('3',1,'','Отдел ИТ',NULL,3),('4',1,'','Генеральная дирекция',NULL,4),('5',1,'','Хозяйственный отдел',NULL,5);
/*!40000 ALTER TABLE `org_struct` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orgs`
--

DROP TABLE IF EXISTS `orgs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orgs` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Наименование',
  `short` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Короткое имя',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Комментарий',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orgs`
--

LOCK TABLES `orgs` WRITE;
/*!40000 ALTER TABLE `orgs` DISABLE KEYS */;
INSERT INTO `orgs` VALUES (1,'Организация 1','Орг1','Переименуй меня');
/*!40000 ALTER TABLE `orgs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partners`
--

DROP TABLE IF EXISTS `partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `inn` bigint DEFAULT NULL,
  `kpp` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cabinet_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `support_tel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prefix` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inn` (`inn`),
  KEY `kpp` (`kpp`),
  KEY `uname` (`uname`(191)),
  KEY `bname` (`bname`(191)),
  KEY `idx-partners-alias` (`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Контрагенты';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners`
--

LOCK TABLES `partners` WRITE;
/*!40000 ALTER TABLE `partners` DISABLE KEYS */;
INSERT INTO `partners` VALUES (1,10000000,'00000000','ООО Табуретка инкорпорэейтед','Табуретка','Демо организация 1','','','','2023-09-13 14:41:02','admin',NULL),(2,11111111,'11111111','ЗАО ХЗ Коллектив','X3 Team','Вторая демо организация','','','','2023-09-15 18:31:38','admin',NULL),(3,7701583410,'771401001','АО \"Азимут\"','Азимут',NULL,'','',NULL,'2023-09-05 05:42:18',NULL,NULL),(4,7707049388,'784001001','ПАО \"Ростелеком\" филиал Калуга','Ростелеком',NULL,'','',NULL,'2023-09-05 05:42:18',NULL,NULL),(5,7713076301,'997750001','ПАО «ВымпелКом»','Билайн',NULL,'','',NULL,'2023-09-05 05:42:18',NULL,NULL),(6,7709219099,'667843001','АО \"Компания ТрансТелеКом\" Макрорегион Урал','ТТК',NULL,'','',NULL,'2023-09-05 05:42:18',NULL,NULL),(7,5902202276,'744843001','АО \"ЭР-Телеком Холдинг\"','ДомРУ','Корп менеджер: \r\nЕрошенко Анфиса Константиновна /+7 (969) 433-54-35/\r\nТел: +7 495 4455555 доб. 33587\r\nЭл. почта: AnfisaEroshenko486@domru.ru','https://lkb2b.dom.ru/','8 800 333 9000 /3 /2','','2023-09-09 16:39:31','admin',NULL),(8,7811152127,'781101001','ООО «Теорема Телеком»','Теорема Телеком',NULL,'','',NULL,'2023-09-05 05:42:18',NULL,NULL),(9,7728429849,'771501001','ООО \"Международная компания связи\"','МКС-Москва',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(10,5902202276,'525743001','Филиал ЗАО \"ЭР-Телеком Холдинг\" в г. Нижнем Новгороде','Дом Ру',NULL,'','8-831-215-7808',NULL,'2023-09-05 05:42:19',NULL,NULL),(11,7740000076,'997750001','ПАО \"Мобильные ТелеСистемы\"','МТС',NULL,'','8-800-250-0990',NULL,'2023-09-05 05:42:19',NULL,NULL),(12,7714278935,'772401001','АО \"Связь\"','Neirika',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(13,7453012173,'745101001','АО НИИИТ-РК','АО НИИИТ-РК',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(14,7801249881,'781401001','ООО \"Юпитер\"','Юпитер',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(15,7704810710,'770401001','ООО \"РТ-ИНФОРМ\"','RT-Inform',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(16,7453176929,'745301001','ООО \"ИТ Дистрибуция\"','Примари',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(17,7715839407,'502401001','ООО \"Си-Эн-Эс\"','Си-Эн-Эс',NULL,'','+7 (495) 955 90 96',NULL,'2023-09-05 05:42:19',NULL,NULL),(18,5024096727,'502401001','ООО \"ДЕПО Электроникс\"','ДЕПО Электроникс',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(19,7453332487,'745301001','ООО \"ТЕХНОТРЕНД\"','НИКС',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(20,7705017253,'997750001','ОАО \"МТТ\"','МТТ',NULL,'https://business.mtt.ru/user/login','8-800-333-31-40 /2 /1',NULL,'2023-09-05 05:42:19',NULL,NULL),(21,7453299127,'745301001','ООО \"Центр информационных технологий и информационной безопасности\"','ЦИТИБ',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(22,7721793895,'997350001','ООО \"КОМУС\"','ООО \"КОМУС\"',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(23,7842399212,'783801001','ООО \"ЛВКОМ Проект\"','LWCOM',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(24,4027036266,'402701001','ООО \"Апгрейд\"','UpGrade',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(25,7743098542,'774850001','ООО \"Хьюлетт Паккорд Энтерпрайз\"','HP',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(26,7709980095,'771501001','ООО \"АРБУЗ-АЙТИ\"','ООО \"АРБУЗ-АЙТИ\"',NULL,NULL,NULL,NULL,'2023-09-05 05:42:19',NULL,NULL),(27,7724531030,'771601001','ООО \"Никотех\"','Nicotech',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(28,541026687,'057201001','ООО \"СПУТНИК+\"','СПУТНИК+',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(29,7707438994,'770701001','ООО \"ИННОВЕЙВ АП\"','ABBYY',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(30,7705058323,'997750001','ООО \"САП СНГ\"','SAP',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(31,7703215813,'770501001','ООО \"Сименс Индастри Софтвер\"','Siemens',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(32,7810182337,'781601001','ООО «НИП Информатика»','Altium',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(33,5260218015,'526001001','ООО \"Гранд-Нижний Новгород\"','Грандсмета',NULL,'','+7 (831) 430-06-14',NULL,'2023-09-05 05:42:19',NULL,NULL),(34,5407203074,'540601001','ООО НПП \"АВС-Н\"','АВС',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(35,7718226920,'772801001','ООО \"ЦЗИ\"','нет',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(36,1840016110,'184001001','ООО \"БиПиЭм Консалтинг\"','БиПиЭм Консалтинг',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(37,5262271737,'526201001','ООО \"Паскаль\"','Паскаль',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(38,5262327651,'526201001','ООО \"Технологии Бизнеса\"','Технологии Бизнеса',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(39,7449127245,'744901001','Общество с ограниченной ответственностью \"Балвер Про\"','Балвер Про',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(40,9715200607,'773101001','ООО \"ГК ИМПУЛЬС ТЕЛЕКОМ\"','импульс телеком',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(41,3702073284,'771601001','ООО \"Хай Эм Джи\"','Компиком',NULL,'','+74959840815',NULL,'2023-09-05 05:42:19',NULL,NULL),(42,7714964934,'771401001','ООО СТЦ \"ВИП\"','ООО СТЦ \"ВИП\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(43,7733568767,'771401001','ООО «Регистратор доменных имен РЕГ.РУ»','REG.RU',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(44,7701962739,'770101001','ООО \"АйТи Таск\"','RuSIEM',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(45,7733573894,'773401001','АО \"Региональный Сетевой Информационный Центр\"','NIC.RU',NULL,'https://nic.ru','',NULL,'2023-09-05 05:42:19',NULL,NULL),(46,7714907809,'502401001','ООО \"ТЕГРУС\"','ТЕГРУС',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(47,5040013947,'','ИП Кузнецова Нина Петровна','ИП Кузнецова Нина Петровна',NULL,'','+74950061342',NULL,'2023-09-05 05:42:19',NULL,NULL),(48,7714569853,'773401001','ООО \"СанТел\"','СанТел',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(49,7709219099,'366643002','АО \"Компания ТрансТелеКом\" Макрорегион Центр','ТТК',NULL,'','8-800-775-00-15',NULL,'2023-09-05 05:42:19',NULL,NULL),(50,7704345974,'770401001','ООО \"БТП\"','БТП',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(51,503008528,'050301001','АО \"Электросвязь\"','ellcom',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(52,7453333402,'745301001','ООО \"Айзет-Телеком Челябинск\"','izet',NULL,'https://lk.izet.ru/welcome/login','+7 351 200-41-11 - техподдержка',NULL,'2023-09-05 05:42:19',NULL,NULL),(53,7707049388,'230843001','ПАО \"Ростелеком\" филиал Махачкала','Ростелеком',NULL,'','8 800 200 67 86 Говорить сразу чтобы на \"Юг\" переводили звонок',NULL,'2023-09-05 05:42:19',NULL,NULL),(54,7707049388,'526043002','ПАО \"Ростелеком\"  Нижегородский  филиал','Ростелеком',NULL,'','8-800-2003000',NULL,'2023-09-05 05:42:19',NULL,NULL),(55,9725032006,'770501001','ООО \"АСАП Разработка\"','АСАП Разработка',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(56,7722350688,'770301001','ООО «Хайтэк-Интеграция»','«Хайтэк-Интеграция»',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(57,7707049388,'668543001','ПАО \"Ростелеком\" филиал Челябинск','Ростелеком','Персональный менеджер: Алена Юрьевна Шарыпова\r\nВедущий менеджер по работе с федеральными холдингами (Отдел продаж корпоративным заказчикам)\r\nТел.: + 7 (351) 239-96-88, IP 33XXX, Мобильный +7 904 XXX XX XX\r\nEmail : sharypova-au@ural.rt.ru\r\nг. Челябинск, ул. Цвиллинга, 10','','8 800 2006786 Говорить сразу чтобы переводили звонок на Урал','','2023-09-09 15:58:20','admin',NULL),(58,7811489410,'780201001','ООО \"Квартет\"','ООО \"Квартет\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(59,7728182207,'771801001','ООО \"ТОРОС\"','Торос',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(60,772427171157,'','ИП РАГИМОВ МУСТАФА САЛМАН оглы','-',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(61,7811549517,'81101001','ООО \"ТК \"Элко\"','Элко',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(62,7814466467,'781301001','ООО «ЛайфТелеком»','ТелФин',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(63,5259019343,'526101001','АО \"Р7\"','R7',NULL,'https://support.r7-office.ru/https@support.r7-office.ru/hc/ru/default.htm','',NULL,'2023-09-05 05:42:19',NULL,NULL),(64,2540167061,'667143001','Филиал Уральский ООО \"ДНС Ритейл\"','ДНС',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(65,7705626696,'770401001','ООО \"Гриднайн Системс\"','Гриднайн',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(66,5008040124,'500801001','ООО \"НИКС Компьютерный Супермаркет\"','НИКС',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(68,541026800,'057201001','ООО \"ИВТ\"','ИВТ',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(69,7714475796,'771401001','ООО \"Аэронавиком инжиниринг\"','Аэронавиком',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(70,7733573894,'773401001','Акционерное общество «Региональный Сетевой Информационный Центр»','АО «РСИЦ»',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(71,7705630371,'770501001','ООО \"АСАП Консалтинг\"','АСАП Консалтинг',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(72,7722753969,'997750001','ООО \"Все инструменты\"','Все инструменты',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(73,7805651030,'780501001','ООО \"БИГХАРД\"','ООО \"БИГХАРД\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(74,7842393933,'781001001','ООО «Селектел»','Селектел',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(75,7708025358,'772201001','ФКУ НПО «СТиС» МВД России','МВД России',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(76,7449116973,'745301001','ООО ТД \"ПРОМЭКС\"','Промэкс',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(77,7722795327,'','ООО \"Рубеж\"','Рубеж',NULL,'','772201001',NULL,'2023-09-05 05:42:19',NULL,NULL),(78,0,'','H3C','H3C',NULL,'https://h3c.com','',NULL,'2023-09-05 05:42:19',NULL,NULL),(79,9729292661,'771401001','ООО \"Аметист НТ\"','Аметист',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(80,9723033526,'773601001','ООО \"КОМПЬЮЛАН\"','KNS',NULL,'','(495) 626-20-20',NULL,'2023-09-05 05:42:19',NULL,NULL),(81,7114020500,'711401001','ООО \"Северо-задонский конденсаторный завод\"','ООО \"СКЗ\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(82,7415077532,'741501001','ООО \"СОНДА ПРО\"','ООО \"СОНДА ПРО\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(83,7840421014,'780401001','АО \"Альянс-АйТи\"','АО \"Альянс-Айти\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(84,7736337944,'77040001','ООО \"СКАЛАВ\"','СКАЛАВ',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(85,5263107289,'526301001','ООО \"А-Деталь-НН\"','А-Деталь-НН',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(86,670418330,'667801001','ООО \"100 Гигабит\"','100G',NULL,'','+7 (343) 288-22-90;  +7 (922) 226-33-08',NULL,'2023-09-05 05:42:19',NULL,NULL),(87,7811773854,'781101001','ООО \"ФОРТ-СЕРВИС\"','ООО \"ФОРТ-СЕРВИС\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(88,7701583410,'105774626','ИП Лозовицкий И.Б.','ИП Лозовицкий И.Б.',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(89,9710079001,'771001001','ООО \"2С\"','ООО \"2С\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(90,7719795020,'772901001','ООО \"СМ\"','ООО \"СМ\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(91,7717107991,'771901001','ООО \"КриптоПРО\"','ООО \"КриптоПРО\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(92,9721113744,'771501001','ООО \"КРОНН\"','kronn',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(93,7714352040,'771401001','АО \"Инфоком-Авиа\"','Инфоком Авиа',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(94,7729108750,'770201001','ЗАО \"ЧИП и ДИП\"','chipdip',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(95,7710950049,'783901001','ООО \"АДВАНСТ МОБИЛИТИ СОЛЮШИНЗ\"','ООО \"АМС\" (МИГ)',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(96,7731481045,'773401001','ООО \"ОСТЕК-СМТ\"','ОСТЕК-СМТ',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(97,9705045818,'770501001','ООО \"ВАЛЬКИРИЯ\"','Валькирия',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(98,1841019386,'184101001','ООО \"1С-ИжТиСи\"','1С-ИжТиСи',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(99,7744000302,'784143001','ООО «Лаборатория систем автоматизации процессов»','ООО \"ЛАБ СП\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(100,4027145240,'402845001','ООО \"АСТРАЛ-СОФТ\"','АСТРАЛ-СОФТ',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(101,9721113744,'771501001','ООО \"КРОНН\"_дубль','kronn',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(102,7725297016,'772501001','ООО \"Висмарт\"','ООО \"Висмарт\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(103,7714176877,'772201001','ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ \"АУДИТ. ОЦЕНКА. КОНСАЛТИНГ\"','ООО \"АУДИТ. ОЦЕНКА. КОНСАЛТИНГ\"',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(104,4025045699,'402743001','Филиал № 1 ООО \"Макснет системы\"','Макснет',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(105,6673240328,'665801001','ООО «Сертум-Про»','Сертум-Про',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(106,772770707357,'','Индивидуальный предприниматель Сесемов С.А.','ИП Сесемов С.А.',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(107,4027058975,'402701001','ООО \"КонсультантПлюс-Калуга\"','КонсультантПлюс-Калуга',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(108,4027124787,'402701001','ООО \"ЯРНЕТ\"','Ярнет',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(109,7726482572,'772601001','АО \"ИБ Реформ\"','ИБ Реформ',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(110,7733300287,'771401001','ООО НТЦ Азимут','НТЦ Азимут',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(111,4027123984,'402701001','ООО \"Маст\"','Маст',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL),(112,402800098416,'-','ИП Асмолов В.В.','Апгрейд',NULL,'','',NULL,'2023-09-05 05:42:19',NULL,NULL);
/*!40000 ALTER TABLE `partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partners_in_contracts`
--

DROP TABLE IF EXISTS `partners_in_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners_in_contracts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partners_id` int NOT NULL,
  `contracts_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `partners_id` (`partners_id`),
  KEY `contracts_id` (`contracts_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners_in_contracts`
--

LOCK TABLES `partners_in_contracts` WRITE;
/*!40000 ALTER TABLE `partners_in_contracts` DISABLE KEYS */;
INSERT INTO `partners_in_contracts` VALUES (2,7,1),(3,57,2),(7,19,3),(9,19,4),(11,14,5),(12,14,6),(14,14,8),(17,14,7);
/*!40000 ALTER TABLE `partners_in_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `places`
--

DROP TABLE IF EXISTS `places`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `places` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `parent_id` int DEFAULT NULL COMMENT 'Предок',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название',
  `addr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Адрес',
  `prefix` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Префикс',
  `short` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Короткое имя',
  `scans_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `map` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `map_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Помещения';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `places`
--

LOCK TABLES `places` WRITE;
/*!40000 ALTER TABLE `places` DISABLE KEYS */;
INSERT INTO `places` VALUES (1,NULL,'Московская площадка','143441, Московская область, Красногорский район, 69 километр МКАД, корпус 33, 17й этаж','МСК','Москва',303,'','{\"places\":{\"3\":{\"x\":7,\"y\":242,\"width\":200,\"height\":147},\"4\":{\"x\":211,\"y\":198,\"width\":282,\"height\":145},\"2\":{\"x\":716,\"y\":237,\"width\":112,\"height\":195},\"5\":{\"x\":536,\"y\":78,\"width\":177,\"height\":184},\"6\":{\"x\":295,\"y\":496,\"width\":184,\"height\":96}}}',303),(2,1,'Рецепшен','','','Рец',305,'','{\"techs\":{\"39\":{\"x\":715,\"y\":10,\"width\":24,\"height\":25},\"36\":{\"x\":718,\"y\":249,\"width\":25,\"height\":25}}}',305),(3,1,'Бухгалтерия','оф. 1711','','Бух',304,'','{\"techs\":{\"5\":{\"x\":9,\"y\":248,\"width\":66,\"height\":66},\"6\":{\"x\":9,\"y\":317,\"width\":66,\"height\":66},\"41\":{\"x\":142,\"y\":246,\"width\":50,\"height\":50},\"37\":{\"x\":200,\"y\":363,\"width\":25,\"height\":25}}}',304),(4,1,'Менеджеры','оф. 1712','','Мен',306,'','{\"techs\":{\"7\":{\"x\":220,\"y\":271,\"width\":66,\"height\":66},\"17\":{\"x\":253,\"y\":204,\"width\":66,\"height\":66},\"42\":{\"x\":404,\"y\":352,\"width\":50,\"height\":50},\"38\":{\"x\":505,\"y\":154,\"width\":25,\"height\":25}}}',306),(5,1,'Генеральный','','','Ген',308,'','',308),(6,1,'Серверная','оф. 1715','','Серв.',307,'','{\"techs\":{\"18\":{\"x\":331,\"y\":528,\"width\":30,\"height\":45},\"40\":{\"x\":298,\"y\":498,\"width\":25,\"height\":25}}}',307),(7,NULL,'Челябинская площадка','454080, г. Челябинск, ул. Энгельса, 77А оф707','ЧЕЛ','Чел',NULL,'',NULL,NULL),(8,7,'Серверная','каб 5.','','Серв.',NULL,'',NULL,NULL),(9,7,'Бухгалтерия','каб. 3','','Бух.',NULL,'',NULL,NULL),(10,7,'Менеджеры','каб 2.','','Мен.',NULL,'',NULL,NULL),(11,7,'Кабинет ИТ','каб. 4','','ИТ',NULL,'',NULL,NULL),(13,5,'Приемная','','','Приемная',NULL,'',NULL,NULL),(14,5,'Комната отдыха','','','К.О.',NULL,'',NULL,NULL);
/*!40000 ALTER TABLE `places` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ports`
--

DROP TABLE IF EXISTS `ports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `techs_id` int DEFAULT NULL,
  `arms_id` int DEFAULT NULL,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link_ports_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-ports-name` (`name`),
  KEY `idx-ports-techs_id` (`techs_id`),
  KEY `idx-ports-link_ports_id` (`link_ports_id`),
  KEY `ports_arms_id` (`arms_id`),
  CONSTRAINT `fk-ports_link_port` FOREIGN KEY (`link_ports_id`) REFERENCES `ports` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ports`
--

LOCK TABLES `ports` WRITE;
/*!40000 ALTER TABLE `ports` DISABLE KEYS */;
INSERT INTO `ports` VALUES (1,12,NULL,'Ge0/1','Access Vlan1',2),(2,1,NULL,'iLO','красный патчкорд',1),(3,12,NULL,'Ge0/3','Trunk',4),(4,1,NULL,'eth1','синий патчкорд',3),(5,12,NULL,'Ge0/5','Trunk',6),(6,1,NULL,'eth2','синий патчкорд',5),(7,12,NULL,'Ge0/2','Access Vlan1',8),(8,2,NULL,'iLO','красный патчкорд',7),(9,12,NULL,'Ge0/4','Trunk',10),(10,2,NULL,'eth1','синий патчкорд',9),(11,12,NULL,'Ge0/6','Trunk',12),(12,2,NULL,'eth2','синий патчкорд',11),(13,45,NULL,'Head1','Желтый патчкорд',14),(14,12,NULL,'Ge0/7','Access Vlan1',13),(15,45,NULL,'Head2','Желтый патчкорд',16),(16,12,NULL,'Ge0/8','Access Vlan1',15),(17,22,NULL,'Gi1/0/48','Зеленый патчкорд',19),(18,12,NULL,'Ge0/23','Зеленый патчкорд',20),(19,12,NULL,'Ge0/24','Зеленый патчкорд',17),(20,22,NULL,'Gi1/0/47','Зеленый патчкорд',18);
/*!40000 ALTER TABLE `ports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sandboxes`
--

DROP TABLE IF EXISTS `sandboxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sandboxes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `suffix` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `network_accessible` tinyint(1) DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `archived` tinyint(1) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sandboxes`
--

LOCK TABLES `sandboxes` WRITE;
/*!40000 ALTER TABLE `sandboxes` DISABLE KEYS */;
INSERT INTO `sandboxes` VALUES (1,'Тестовая песочница 1С','1C_TEST',0,NULL,NULL,0,'2025-05-09 14:48:25',NULL),(2,'Тестовая песочница ИТ','TEST',0,NULL,NULL,0,'2025-05-25 16:22:38',NULL);
/*!40000 ALTER TABLE `sandboxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sandboxes_history`
--

DROP TABLE IF EXISTS `sandboxes_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sandboxes_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `archived` tinyint(1) DEFAULT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `suffix` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `network_accessible` tinyint(1) DEFAULT NULL,
  `notepad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comps_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `sandboxes_history-master_id` (`master_id`),
  KEY `sandboxes_history-updated_at` (`updated_at`),
  KEY `sandboxes_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sandboxes_history`
--

LOCK TABLES `sandboxes_history` WRITE;
/*!40000 ALTER TABLE `sandboxes_history` DISABLE KEYS */;
INSERT INTO `sandboxes_history` VALUES (1,1,'2025-05-09 14:48:25',NULL,NULL,'archived,name,suffix,network_accessible',0,'Тестовая песочница 1С','1C_TEST',0,NULL,NULL,NULL),(2,2,'2025-05-25 16:22:20',NULL,NULL,'archived,name,network_accessible',0,'Тесто',NULL,0,NULL,NULL,NULL),(3,2,'2025-05-25 16:22:38',NULL,NULL,'name,suffix',0,'Тестовая песочница ИТ','TEST',0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `sandboxes_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scans`
--

DROP TABLE IF EXISTS `scans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scans` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `contracts_id` int DEFAULT NULL,
  `format` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `places_id` int DEFAULT NULL,
  `tech_models_id` int DEFAULT NULL,
  `material_models_id` int DEFAULT NULL,
  `lic_types_id` int DEFAULT NULL,
  `lic_items_id` int DEFAULT NULL,
  `techs_id` int DEFAULT NULL,
  `arms_id` int DEFAULT NULL,
  `soft_id` int DEFAULT NULL COMMENT 'Soft ID associated with the scan',
  PRIMARY KEY (`id`),
  KEY `contracts_id` (`contracts_id`),
  KEY `idx-scans_places_id` (`places_id`),
  KEY `idx-scans_tech_models_id` (`tech_models_id`),
  KEY `idx-scans_material_models_id` (`material_models_id`),
  KEY `idx-scans_lic_types_id` (`lic_types_id`),
  KEY `idx-scans_lic_items_id` (`lic_items_id`),
  KEY `idx-scans_techs_id` (`techs_id`),
  KEY `idx-scans_arms_id` (`arms_id`),
  KEY `idx-scans-soft_id` (`soft_id`),
  CONSTRAINT `scans_ibfk_1` FOREIGN KEY (`contracts_id`) REFERENCES `contracts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=309 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scans`
--

LOCK TABLES `scans` WRITE;
/*!40000 ALTER TABLE `scans` DISABLE KEYS */;
INSERT INTO `scans` VALUES (199,NULL,'png','1-SPA504G2',NULL,2,NULL,NULL,NULL,NULL,NULL,NULL),(200,NULL,'png','200-0cbd4013e35a41f6af9d9a22de4a8c0',NULL,10,NULL,NULL,NULL,NULL,NULL,NULL),(201,NULL,'png','201-grandstream_dp750_451022_4',NULL,14,NULL,NULL,NULL,NULL,NULL,NULL),(202,NULL,'png','202-pngegg',NULL,17,NULL,NULL,NULL,NULL,NULL,NULL),(203,NULL,'png','203-pngegg _1_',NULL,17,NULL,NULL,NULL,NULL,NULL,NULL),(204,NULL,'png','204-UBIQUITI-AP-AC-LR-2199032254',NULL,31,NULL,NULL,NULL,NULL,NULL,NULL),(205,NULL,'png','205-BACK-UPS ES400',NULL,55,NULL,NULL,NULL,NULL,NULL,NULL),(206,NULL,'png','206-16966',NULL,57,NULL,NULL,NULL,NULL,NULL,NULL),(207,NULL,'png','207-232d',NULL,58,NULL,NULL,NULL,NULL,NULL,NULL),(208,NULL,'png','208-APC-BK650EI-301692254',NULL,59,NULL,NULL,NULL,NULL,NULL,NULL),(209,NULL,'png','209-APC-BK650EI-301692241',NULL,59,NULL,NULL,NULL,NULL,NULL,NULL),(210,NULL,'png','210-scale-dec-558x600 _1_',NULL,61,NULL,NULL,NULL,NULL,NULL,NULL),(211,NULL,'png','211-scale-dec-558x600',NULL,61,NULL,NULL,NULL,NULL,NULL,NULL),(212,NULL,'png','212-APC-800VA,-230V,-AVR,-Schuko-Outlets,-CIS-BX800CI-RS-1434382254 _1_',NULL,65,NULL,NULL,NULL,NULL,NULL,NULL),(213,NULL,'png','213-APC-800VA,-230V,-AVR,-Schuko-Outlets,-CIS-BX800CI-RS-1434382241',NULL,65,NULL,NULL,NULL,NULL,NULL,NULL),(214,NULL,'png','214-APC-BX700UI-2080232254',NULL,69,NULL,NULL,NULL,NULL,NULL,NULL),(215,NULL,'png','215-APC-BX700UI-2080232241',NULL,69,NULL,NULL,NULL,NULL,NULL,NULL),(216,NULL,'png','216-APC-BX950UI-2080242254',NULL,73,NULL,NULL,NULL,NULL,NULL,NULL),(217,NULL,'png','217-APC-BX950UI-2080242241',NULL,73,NULL,NULL,NULL,NULL,NULL,NULL),(218,NULL,'png','218-CyberPower-BR850ELCD-779982254',NULL,74,NULL,NULL,NULL,NULL,NULL,NULL),(219,NULL,'png','219-APC-650VA,-AVR,-230V,-Schuko-Sockets,-CIS-BX650CI-RS-1258122254',NULL,75,NULL,NULL,NULL,NULL,NULL,NULL),(220,NULL,'png','220-APC-650VA,-AVR,-230V,-Schuko-Sockets,-CIS-BX650CI-RS-1258122241',NULL,75,NULL,NULL,NULL,NULL,NULL,NULL),(221,NULL,'png','221-m1120',NULL,87,NULL,NULL,NULL,NULL,NULL,NULL),(222,NULL,'png','222-4012i',NULL,88,NULL,NULL,NULL,NULL,NULL,NULL),(223,NULL,'png','223-avaya-1608-i',NULL,99,NULL,NULL,NULL,NULL,NULL,NULL),(224,NULL,'png','224-1161576',NULL,100,NULL,NULL,NULL,NULL,NULL,NULL),(225,NULL,'png','225-1181965',NULL,104,NULL,NULL,NULL,NULL,NULL,NULL),(226,NULL,'png','226-SIP-t31g',NULL,138,NULL,NULL,NULL,NULL,NULL,NULL),(227,NULL,'png','227-SIP-T46U',NULL,139,NULL,NULL,NULL,NULL,NULL,NULL),(228,NULL,'png','228-ws-c3560x-24t-s_2-1200x800',NULL,151,NULL,NULL,NULL,NULL,NULL,NULL),(229,NULL,'png','229-ws-c3560x-24t-s_3-1200x800',NULL,151,NULL,NULL,NULL,NULL,NULL,NULL),(230,NULL,'png','230-10103655_1',NULL,152,NULL,NULL,NULL,NULL,NULL,NULL),(231,NULL,'png','231-10103655__3_3',NULL,152,NULL,NULL,NULL,NULL,NULL,NULL),(232,NULL,'jpg','232-149-G430',NULL,171,NULL,NULL,NULL,NULL,NULL,NULL),(233,NULL,'png','233-ADS2800W_main',NULL,183,NULL,NULL,NULL,NULL,NULL,NULL),(234,NULL,'png','234-ADS2800W_left',NULL,183,NULL,NULL,NULL,NULL,NULL,NULL),(235,NULL,'png','235-oceanstor-2200-v3',NULL,184,NULL,NULL,NULL,NULL,NULL,NULL),(236,NULL,'png','236-DEPO_Neos_DF226_670',NULL,187,NULL,NULL,NULL,NULL,NULL,NULL),(237,NULL,'png','237-DELL-U2412M-1239912254',NULL,190,NULL,NULL,NULL,NULL,NULL,NULL),(238,NULL,'png','238-cca40c3a86831c9adc7d1afb0c8255a9',NULL,190,NULL,NULL,NULL,NULL,NULL,NULL),(239,NULL,'jpg','239-DELL-U2412M-1239912312',NULL,190,NULL,NULL,NULL,NULL,NULL,NULL),(240,NULL,'png','240-aac5fcd72707e7f423defe936dd85619992ab332',NULL,193,NULL,NULL,NULL,NULL,NULL,NULL),(241,NULL,'png','241-364cdaa073c21d05d094dea688ffa765f3ec9456',NULL,193,NULL,NULL,NULL,NULL,NULL,NULL),(242,NULL,'png','242-dfca307953924a054675f439d578f5a61ce5ef67',NULL,193,NULL,NULL,NULL,NULL,NULL,NULL),(243,NULL,'png','243-b4a190e9faa3dd8fa06c819d95f20d1a10cf19cc',NULL,193,NULL,NULL,NULL,NULL,NULL,NULL),(244,NULL,'png','244-696528',NULL,195,NULL,NULL,NULL,NULL,NULL,NULL),(245,NULL,'png','245-orig _2__proc',NULL,199,NULL,NULL,NULL,NULL,NULL,NULL),(246,NULL,'png','246-orig_proc',NULL,199,NULL,NULL,NULL,NULL,NULL,NULL),(247,NULL,'png','247-orig _1__proc',NULL,199,NULL,NULL,NULL,NULL,NULL,NULL),(248,NULL,'png','248-APC-Power-Saving-900,-230V-BR900GI-1088272254',NULL,214,NULL,NULL,NULL,NULL,NULL,NULL),(249,NULL,'png','249-br900gi-10122970vNcHamL8P7bO5',NULL,214,NULL,NULL,NULL,NULL,NULL,NULL),(250,NULL,'png','250-20220525064527878ec88633f42e095b0379dfa777105',NULL,235,NULL,NULL,NULL,NULL,NULL,NULL),(251,NULL,'png','251-apc-smart-ups-3000va-usb-serial-rm-2u-230v-sua3000rmi2u',NULL,238,NULL,NULL,NULL,NULL,NULL,NULL),(252,NULL,'jpg','252-apc-3000va-usb-serial-rm-2u-230v-sua3000rmi2u-189172241',NULL,238,NULL,NULL,NULL,NULL,NULL,NULL),(253,NULL,'png','253-apc-1000va-usb-serial-rm-2u-230v-sua1000rmi2u-163832254',NULL,239,NULL,NULL,NULL,NULL,NULL,NULL),(254,NULL,'jpg','254-apc-1000va-usb-serial-rm-2u-230v-sua1000rmi2u-163832241',NULL,239,NULL,NULL,NULL,NULL,NULL,NULL),(255,NULL,'png','255-07_Yoga_C940_Iron_Grey_15Inch_Hero_Tent_Voice_Assistant_Cortana',NULL,241,NULL,NULL,NULL,NULL,NULL,NULL),(256,NULL,'png','256-fa4416c04ffbbf2bd1be51c62b827137',NULL,241,NULL,NULL,NULL,NULL,NULL,NULL),(257,NULL,'png','257-5746f95f1939f208c12b645b94a798c4',NULL,241,NULL,NULL,NULL,NULL,NULL,NULL),(258,NULL,'png','258-1000x1000_1',NULL,242,NULL,NULL,NULL,NULL,NULL,NULL),(259,NULL,'jpg','259-295842_v01_b',NULL,256,NULL,NULL,NULL,NULL,NULL,NULL),(260,NULL,'png','260-pngegg _3_',NULL,271,NULL,NULL,NULL,NULL,NULL,NULL),(261,NULL,'png','261-HP_t620_Thin_Client_back_bennoshop_600x600@2x',NULL,271,NULL,NULL,NULL,NULL,NULL,NULL),(262,NULL,'png','262-559-SYS-6025B-3RV',NULL,274,NULL,NULL,NULL,NULL,NULL,NULL),(263,NULL,'jpg','263-gibridnij-massiv-hraneniya-dannih-netapp-fas2554',NULL,275,NULL,NULL,NULL,NULL,NULL,NULL),(264,NULL,'jpg','264-561-gibridnij-massiv-hraneniya-dannih-netapp-fas2554 _1_.jpg_thumb_160x160',NULL,275,NULL,NULL,NULL,NULL,NULL,NULL),(265,NULL,'png','265-7_y4jv-h4',NULL,278,NULL,NULL,NULL,NULL,NULL,NULL),(266,NULL,'png','266-apc-br650mi-pro-front',NULL,279,NULL,NULL,NULL,NULL,NULL,NULL),(267,NULL,'png','267-apc-br650mi-pro-back',NULL,279,NULL,NULL,NULL,NULL,NULL,NULL),(268,NULL,'png','268-WA6330',NULL,281,NULL,NULL,NULL,NULL,NULL,NULL),(269,NULL,'png','269-product_pic',NULL,284,NULL,NULL,NULL,NULL,NULL,NULL),(270,NULL,'png','270-47',NULL,309,NULL,NULL,NULL,NULL,NULL,NULL),(271,NULL,'jpg','271-5468452',NULL,309,NULL,NULL,NULL,NULL,NULL,NULL),(272,NULL,'png','272-1005639_v01_b',NULL,310,NULL,NULL,NULL,NULL,NULL,NULL),(273,NULL,'jpg','273-1005639_v10_b',NULL,310,NULL,NULL,NULL,NULL,NULL,NULL),(274,NULL,'jpg','274-1005639_v05_b',NULL,310,NULL,NULL,NULL,NULL,NULL,NULL),(275,NULL,'jpg','275-1005639_v02_b',NULL,310,NULL,NULL,NULL,NULL,NULL,NULL),(276,NULL,'png','276-Mini_PK_RDW_Computers_1',NULL,311,NULL,NULL,NULL,NULL,NULL,NULL),(277,NULL,'png','277-1676587032348',NULL,330,NULL,NULL,NULL,NULL,NULL,NULL),(278,NULL,'png','278-1676584183821',NULL,330,NULL,NULL,NULL,NULL,NULL,NULL),(279,NULL,'png','279-slm2008pt-eu-10108158-014c6s8NRxg6cD8N',NULL,381,NULL,NULL,NULL,NULL,NULL,NULL),(280,NULL,'png','280-5ebb4058f57e9f61879a666c59b59c7b',NULL,387,NULL,NULL,NULL,NULL,NULL,NULL),(281,NULL,'png','281-1bc255ac4c76a074e8e99147f5267344',NULL,388,NULL,NULL,NULL,NULL,NULL,NULL),(282,NULL,'png','282-HPE_ProLiant_DL380_Gen9',NULL,4,NULL,NULL,NULL,NULL,NULL,NULL),(283,3,'pdf','283-Счет №456 от 17.11.2020г',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(284,NULL,'png','284-intel-nuc',NULL,327,NULL,NULL,NULL,NULL,NULL,NULL),(285,4,'pdf','285-2021-12-21 Счет№ 709 Оборудование в офисы',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(286,NULL,'png','286-Hb9a2b5f9b3e44a9fa19a5b3afdcc45a7T',NULL,194,NULL,NULL,NULL,NULL,NULL,NULL),(287,NULL,'png','287-KX-TG1611_Spec2',NULL,262,NULL,NULL,NULL,NULL,NULL,NULL),(288,NULL,'png','288-DP720_side_cradle_web',NULL,15,NULL,NULL,NULL,NULL,NULL,NULL),(289,NULL,'png','289-0bbc86c5c6692524f51156798c383ed3',NULL,252,NULL,NULL,NULL,NULL,NULL,NULL),(290,NULL,'png','290-gac2500_main_picture_2',NULL,54,NULL,NULL,NULL,NULL,NULL,NULL),(291,NULL,'png','291-gac2500_top',NULL,54,NULL,NULL,NULL,NULL,NULL,NULL),(292,NULL,'png','292-1451',NULL,389,NULL,NULL,NULL,NULL,NULL,NULL),(293,NULL,'png','293-f3101f1cf5e7bd8d59c3fed39a166c86',NULL,38,NULL,NULL,NULL,NULL,NULL,NULL),(294,NULL,'png','294-msa2052b',NULL,390,NULL,NULL,NULL,NULL,NULL,NULL),(295,NULL,'png','295-msa2052',NULL,390,NULL,NULL,NULL,NULL,NULL,NULL),(296,5,'pdf','296-________',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(300,7,'pdf','299-testFax',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(301,NULL,'jpg','301-188362-6afa9-42460626-m750x740-ub5b3a',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(302,NULL,'jpg','302-561ee21418326133449e5c443bb49228',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(303,NULL,'jpg','303-3',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(304,NULL,'jpg','304-1',3,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(305,NULL,'jpg','305-4',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(306,NULL,'jpg','306-2',4,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(307,NULL,'jpg','307-5',6,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(308,NULL,'jpg','308-6',5,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `scans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `start_date` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `end_date` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `override_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-schedules_parent_id` (`parent_id`),
  KEY `idx-schedules-updated_at` (`updated_at`),
  KEY `idx-schedules-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,'Круглосуточно 24/7','Для сервисов предоставляемых непрерывно',NULL,'','','',NULL,NULL,NULL),(2,'Рабочее время МСК','Рабочее время московского офиса',NULL,'','','',NULL,NULL,NULL),(3,'Рабочее время ЧЕЛ','Рабочее время челябинского офиса',NULL,'','','',NULL,NULL,NULL),(4,'Расписание техподдержки','График дежурства на первой линии',NULL,'','','',NULL,NULL,NULL),(5,'Override for #4','Безруков в отпуске',4,'','2023-09-19','',4,NULL,NULL),(6,'Удаленный доступ коллектива ХЗ к терминалам 1С',NULL,NULL,'',NULL,NULL,NULL,NULL,NULL),(8,'Удаленный доступ ИТ отдела к рабочим местам',NULL,NULL,'',NULL,NULL,NULL,'2024-03-18 15:22:27','admin'),(9,'Расписание veeam Backup GFS 7-3','',NULL,'','','',NULL,'2025-05-09 14:44:47',NULL);
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules_entries`
--

DROP TABLE IF EXISTS `schedules_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` int DEFAULT NULL,
  `date` varchar(64) DEFAULT NULL,
  `schedule` varchar(255) DEFAULT NULL,
  `date_end` varchar(64) DEFAULT NULL,
  `is_period` tinyint(1) DEFAULT NULL,
  `is_work` tinyint(1) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `history` text,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-schedules_days_org_id` (`schedule_id`),
  KEY `idx-schedules_days_date` (`date`),
  KEY `idx-schedules_days_end_date` (`date_end`),
  KEY `idx-schedules_days_is_period` (`is_period`),
  KEY `idx-schedules_days_is_work` (`is_work`),
  KEY `idx-schedules_entries-updated_at` (`updated_at`),
  KEY `idx-schedules_entries-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules_entries`
--

LOCK TABLES `schedules_entries` WRITE;
/*!40000 ALTER TABLE `schedules_entries` DISABLE KEYS */;
INSERT INTO `schedules_entries` VALUES (1,1,'def','00:00-23:59',NULL,0,1,'','',NULL,NULL),(2,2,'def','08:00-17:00',NULL,0,1,'','',NULL,NULL),(3,2,'6','-',NULL,0,1,'','',NULL,NULL),(4,2,'7','-',NULL,0,1,'','',NULL,NULL),(5,3,'def','06:00-15:00',NULL,0,1,'','',NULL,NULL),(6,3,'7','-',NULL,0,1,'','',NULL,NULL),(7,3,'6','-',NULL,0,1,'','',NULL,NULL),(8,3,'5','6:00-14:00',NULL,0,1,'Сокр день','',NULL,NULL),(9,4,'def','6:00-08:00{\"user\":\"DaniilZimin\"},8:00-12:30{\"user\":\"admin\"},12:30-17:00{\"user\":\"BorisBarinov\"}',NULL,0,1,'','',NULL,NULL),(10,4,'7','-',NULL,0,1,'','',NULL,NULL),(11,4,'6','-',NULL,0,1,'','',NULL,NULL),(12,4,'5','6:00-08:00{\"user\":\"DaniilZimin\"},8:00-12:00{\"user\":\"admin\"},12:00-16:00{\"user\":\"BorisBarinov\"}',NULL,0,1,'Сокр день','',NULL,NULL),(13,5,'def','6:00-08:00{\"user\":\"DaniilZimin\"},8:00-12:30{\"user\":\"VeniaminLevchenko\"},12:30-17:00{\"user\":\"BorisBarinov\"}',NULL,0,1,'','',NULL,NULL),(14,5,'7','-',NULL,0,1,'','',NULL,NULL),(15,5,'6','-',NULL,0,1,'','',NULL,NULL),(16,5,'5','6:00-08:00{\"user\":\"DaniilZimin\"},8:00-12:00{\"user\":\"VeniaminLevchenko\"},12:00-16:00{\"user\":\"BorisBarinov\"}',NULL,0,1,'','',NULL,NULL),(17,6,'2022-02-08 00:00:00',NULL,'2022-12-31 23:55:59',1,1,'Согласно СЗ№ 31 от 01.02.2022 об аутсорсе 1С','',NULL,NULL),(18,6,'2023-01-01 00:00:00',NULL,NULL,1,1,'Согласно СЗ№ 7 от 21.12.2022 о пролонгации аутсорса 1С','','2024-03-18 15:52:20','admin'),(20,8,'2024-03-01 00:00:41',NULL,'2027-12-31 23:55:41',1,1,'СЗ № 32 от 01.03.2024 об удаленной работе ИТ отдела','','2024-03-18 15:53:38','admin');
/*!40000 ALTER TABLE `schedules_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules_entries_history`
--

DROP TABLE IF EXISTS `schedules_entries_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules_entries_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `schedule_id` int DEFAULT NULL,
  `date` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_end` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `schedule` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_period` tinyint(1) DEFAULT NULL,
  `is_work` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedules_entries_history-master_id` (`master_id`),
  KEY `schedules_entries_history-updated_at` (`updated_at`),
  KEY `schedules_entries_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules_entries_history`
--

LOCK TABLES `schedules_entries_history` WRITE;
/*!40000 ALTER TABLE `schedules_entries_history` DISABLE KEYS */;
INSERT INTO `schedules_entries_history` VALUES (1,18,'2024-03-18 15:52:20','admin',NULL,'comment,schedule_id,date,is_period,is_work','Согласно СЗ№ 7 от 21.12.2022 о пролонгации аутсорса 1С',NULL,6,'2023-01-01 00:00:00',NULL,NULL,1,1),(2,20,'2024-03-18 15:53:38','admin',NULL,'comment,schedule_id,date,date_end,is_period,is_work','СЗ № 32 от 01.03.2024 об удаленной работе ИТ отдела',NULL,8,'2024-03-01 00:00:41','2027-12-31 23:55:41',NULL,1,1);
/*!40000 ALTER TABLE `schedules_entries_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules_history`
--

DROP TABLE IF EXISTS `schedules_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `parent_id` int DEFAULT NULL,
  `override_id` int DEFAULT NULL,
  `start_date` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `end_date` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `entries_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `providing_services_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `support_services_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `acls_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `maintenance_jobs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `overrides_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `schedules_history-master_id` (`master_id`),
  KEY `schedules_history-updated_at` (`updated_at`),
  KEY `schedules_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules_history`
--

LOCK TABLES `schedules_history` WRITE;
/*!40000 ALTER TABLE `schedules_history` DISABLE KEYS */;
INSERT INTO `schedules_history` VALUES (1,8,'2024-03-18 15:22:27','admin',NULL,'name','Удаленный доступ ИТ отдела к рабочим местам',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,8,'2024-03-18 15:22:27','admin',NULL,'acls_ids','Удаленный доступ ИТ отдела к рабочим местам',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3',NULL,NULL),(3,8,'2024-03-18 15:50:12','admin',NULL,'acls_ids','Удаленный доступ ИТ отдела к рабочим местам',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3,4',NULL,NULL),(4,6,'2024-03-18 15:52:20','admin',NULL,'name,entries_ids,acls_ids','Удаленный доступ коллектива ХЗ к терминалам 1С',NULL,NULL,NULL,NULL,NULL,NULL,'17,18',NULL,NULL,'1',NULL,NULL),(5,8,'2024-03-18 15:53:38','admin',NULL,'entries_ids','Удаленный доступ ИТ отдела к рабочим местам',NULL,NULL,NULL,NULL,NULL,NULL,'20',NULL,NULL,'3,4',NULL,NULL),(6,8,'2024-03-18 17:06:55','admin',NULL,'acls_ids','Удаленный доступ ИТ отдела к рабочим местам',NULL,NULL,NULL,NULL,NULL,NULL,'20',NULL,NULL,'3,4,5',NULL,NULL),(7,8,'2024-03-19 03:42:25','admin',NULL,'acls_ids','Удаленный доступ ИТ отдела к рабочим местам',NULL,NULL,NULL,NULL,NULL,NULL,'20',NULL,NULL,'3,4',NULL,NULL),(8,1,'2025-05-09 14:38:05',NULL,NULL,'name,description,entries_ids,providing_services_ids','Круглосуточно 24/7','Для сервисов предоставляемых непрерывно',NULL,NULL,NULL,NULL,NULL,'1','1,2,3,5,11,12,13,19,20,21,22,24,26',NULL,NULL,NULL,NULL),(9,2,'2025-05-09 14:38:05',NULL,NULL,'name,description,entries_ids,support_services_ids','Рабочее время МСК','Рабочее время московского офиса',NULL,NULL,NULL,NULL,NULL,'2,3,4',NULL,'1,2,5,12,21,22,26',NULL,NULL,NULL),(10,9,'2025-05-09 14:44:47',NULL,NULL,'name','Расписание veeam Backup GFS 7-3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `schedules_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `segments`
--

DROP TABLE IF EXISTS `segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `archived` tinyint(1) DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-segments-archived` (`archived`),
  KEY `idx-segments-updated_at` (`updated_at`),
  KEY `idx-segments-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `segments`
--

LOCK TABLES `segments` WRITE;
/*!40000 ALTER TABLE `segments` DISABLE KEYS */;
INSERT INTO `segments` VALUES (1,'Открытый','Для компьютеров с доступом в интернет','segment_open','### Назначение сегмента\r\nВ этом сегменте находятся компьютеры с доступом в интернет, но без доступа к ресурсам закрытого сегмента',NULL,NULL,NULL,NULL),(2,'Сеть принтеров','Для принтеров','segment_prn','### Назначение сегмента\r\nСегмент для принтеров необходим для возможности печати на принтеры только с серверов печати и блокировки доступа пользователей к печатному оборудованию напрямую\r\n\r\n### Доступ\r\n- Из сети серверов открыт\r\n- Из остальных закрыт сетей закрыт\r\n- Печать из остальных сетей через сервера печати в сети серверов \r\n- Исходящие SMB соединения до файловых серверов и в интернет по порту SMTP для возможности сетевого сканирования  ',NULL,NULL,NULL,NULL),(3,'Клиентский VPN','Для сетей VPN клиентов','segment_guest_dmz','### Назначение сегмента\r\nПоскольку с другого конца клиентского VPN может быть BYOD устройство (а на самом деле в основном они и есть), то все их IP адреса должны быть помещены в некий гостевой DMZ с фильтрацией доступа в остальные сегменты.\r\n\r\n### Доступ\r\n  * до ресурсов доступных из интернет\r\n  * до корпоративного портала и чата\r\n  * терминальных сервисов без буфера обмена и монтирования устройств',NULL,NULL,NULL,NULL),(4,'Закрытый','Закрытые ресурсы','segment_closed','### Назначение сегмента\r\nЗакрытые ресурсы, представляющие собой коммерческую тайну и клиентские устройства с доступом к ней и без доступа в интернет и в открытый сегмент.\r\n\r\n### Доступ\r\n  * До сети серверов для доступа к инфраструктурным сервисам  \r\n  * До прокси сервера где через белый список доступ до серверов обновлений\r\n  * До сервера печати где доступ только к принтерам через сервис контроля печати',NULL,NULL,NULL,NULL),(5,'Внешний DMZ','Для сервисов доступных из интернет','segment_ext_dmz','### Назначение сегмента\r\nОграничение доступа серверов доступных из интернет к остальной сети, для ограничения масштабов взлома при прорыве внешнего контура\r\n\r\n### Доступ\r\n  * До инфраструктурных сервисов\r\n  * Для каждого сервиса также индивидуальный доступ',NULL,NULL,NULL,NULL),(6,'Сеть серверов','Ресурсы доступные для всех','segment_common','### Назначение сегмента\r\nВ этом сегменте расположены сервисы, которые должны быть доступны и из закрытого и из открытого сегментов\r\n\r\n### Доступ\r\nОтовсюду но только по адресам/портам размещенных сервисов',NULL,NULL,NULL,NULL),(7,'VoIP','Для устройств VoIP','segment_voip','### Назначение сегмента\r\nВ сетях этого сегмента должен быть настроен DHCP autoprovision (опция 66) для автоматического подключения телефонов к АТС\r\n\r\n### Доступ\r\nСервер телефонии может бросать исходящие соединения наружу по SIP (+RTP), также нужен проброс входящих SIP (+RTP) с белого списка (провайдеров телефонии) на сервер телефонии для входящих вызовов.  \r\nОстальной доступ ограничен, т.к. телефония очень финансово чувствительна ко взлому  \r\n(сразу начнутся звонки в Бразилию, Китай и т.п. где в течение дня можно получить счет на сотни тыс.р.)',NULL,NULL,NULL,NULL),(8,'IT сегмент','Сеть для рабочих мест админов','segment_it_lan','### Назначение сегмента\r\nОрганизация доступа сисадминов до всей инфраструктуры\r\n\r\n### Доступ\r\nИз этого сегмента полный доступ во все остальные  \r\nВ этот сегмент только RDP доступ с VPN адресов админов до своих машин для удаленной работы  \r\n(взлом узлов этой сети чрезвычайно опасен, т.к. даст далее доступ до всей сети)',NULL,NULL,NULL,NULL),(9,'Сегмент управления','Для управления устройствами','segment_mgmt','### Назначение сегмента\r\nРазмещение адресов интерфейсов управления оборудованием\r\n\r\n### Доступ\r\nТолько ИТ из сети\r\nИсходящие соединения только до инфраструктурных сервисов',NULL,NULL,NULL,NULL),(10,'Внешний','Для внешних подключений','segment_ext','### Назначение сегмента\r\nТут находятся очевидно все сервисы доступные напрямую из интернет\r\n\r\n### Доступ\r\nПоскольку тут сервисы имеют внешний адрес с доступом из интернет, и это не DMZ, то фаервол нужно ставить на каждый узел отдельно',NULL,NULL,NULL,NULL),(11,'Межсайтовый VPN','Для связи между площадками','segment_intersite_vpn','',NULL,NULL,NULL,NULL),(12,'Сегмент видеонаблюдения','Видеокамеры, регистраторы','segment_skud','',NULL,'','2025-08-29 07:29:54',NULL);
/*!40000 ALTER TABLE `segments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `segments_history`
--

DROP TABLE IF EXISTS `segments_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segments_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  `updated_comment` varchar(255) DEFAULT NULL,
  `changed_attributes` text,
  `name` varchar(32) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `history` text,
  `links` text,
  PRIMARY KEY (`id`),
  KEY `segments_history-master_id` (`master_id`),
  KEY `segments_history-updated_at` (`updated_at`),
  KEY `segments_history-updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `segments_history`
--

LOCK TABLES `segments_history` WRITE;
/*!40000 ALTER TABLE `segments_history` DISABLE KEYS */;
INSERT INTO `segments_history` VALUES (1,12,'2025-08-29 07:29:54',NULL,NULL,'name,code,description','Сегмент видеонаблюдения','segment_skud','Видеокамеры, регистраторы',NULL,NULL);
/*!40000 ALTER TABLE `segments_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_end_user` tinyint(1) NOT NULL,
  `notebook` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `responsible_id` int DEFAULT NULL,
  `providing_schedule_id` int DEFAULT NULL,
  `support_schedule_id` int DEFAULT NULL,
  `segment_id` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `cost` float DEFAULT NULL,
  `charge` float DEFAULT NULL,
  `partners_id` int DEFAULT NULL,
  `places_id` int DEFAULT NULL,
  `archived` int NOT NULL DEFAULT '0',
  `is_service` tinyint(1) DEFAULT '1',
  `currency_id` int NOT NULL DEFAULT '1',
  `search_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` int NOT NULL DEFAULT '100',
  `infrastructure_user_id` int DEFAULT NULL,
  `external_links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `vm_cores` int DEFAULT NULL,
  `vm_ram` int DEFAULT NULL,
  `vm_hdd` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-services-is_end_user` (`is_end_user`),
  KEY `idx-services_responsible` (`responsible_id`),
  KEY `idx-services_providing_schedule` (`providing_schedule_id`),
  KEY `idx-services_support_schedule` (`support_schedule_id`),
  KEY `idx-services_segment` (`segment_id`),
  KEY `idx-services_parent_id` (`parent_id`),
  KEY `idx-services_partners_id` (`partners_id`),
  KEY `idx-services_places_id` (`places_id`),
  KEY `idx-services_archived` (`archived`),
  KEY `idx-services-currency_id` (`currency_id`),
  CONSTRAINT `fk-services-providing_schedule` FOREIGN KEY (`providing_schedule_id`) REFERENCES `schedules` (`id`),
  CONSTRAINT `fk-services-responsible` FOREIGN KEY (`responsible_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk-services-segment` FOREIGN KEY (`segment_id`) REFERENCES `segments` (`id`),
  CONSTRAINT `fk-services-support_schedule` FOREIGN KEY (`support_schedule_id`) REFERENCES `schedules` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Услуги связи - Дом.Ру','Связь в МСК','',1,'',10,1,2,10,NULL,NULL,NULL,7,NULL,0,0,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'Услуги связи - РТ','Услуги связи РТ в Челябинске','',1,'',10,1,2,10,NULL,NULL,NULL,57,7,0,0,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,'Управление сетью','Все что связано с сетевыми вопросами','',0,'',1,1,3,9,NULL,NULL,NULL,NULL,NULL,0,0,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,'Локальная сеть (LAN)','Коммутация устройств','',0,'',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'Управление виртуализацией','Все услуги и сервисы по виртуализации','',0,'',9,1,2,9,NULL,NULL,NULL,NULL,NULL,0,0,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,'Маршрутизация','Управление маршрутизацией','',0,'',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,0,1,1,'routing',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,'Сетевой экран','Настройка сетевых ограничений','',0,'',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,'Виртуализация: аппаратный уровень','Обслуживание и настройка серверов','',0,'',NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,0,0,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,'Виртуализация: гипервизоры','Слой виртуализации','',0,'',NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,'Виртуализация: кластеризация','vCenter','',0,'',NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,'Телефония','Управление SIP телефонией','',1,'',9,1,3,7,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,'Домен Taburetka.local','Управление доменом','',0,'',6,1,2,6,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,'Сервер печати','Перенаправление и контроль задач печати от пользователей к устройствам печати','',1,'',10,1,4,6,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,'DNS зона taburetka.local','Встроенная в AD','',0,'',NULL,NULL,NULL,NULL,12,NULL,NULL,NULL,NULL,0,1,1,'',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,'DHCP сервер','Раздача адресов сетевым устройствам','',0,'',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,'Межсайтовый VPN','Связность сайтов','',0,'',NULL,NULL,NULL,11,3,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,'Пользовательский VPN','Доступ пользователей к внутренним ресурсам','Описание https://wiki.reviakin.net/сервисы:пользовательский_vpn',1,'',6,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(18,'Кластер 1С','Трехзвенка БД - Сервер - Клиент','',1,'',9,4,4,4,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(19,'Контроль доступа в интернет','Выход в интернет через SQUID','',1,'',1,1,3,1,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,6,NULL,NULL,NULL,NULL,NULL,NULL),(20,'Мониторинг инфраструктуры','Zabbix','',0,'',1,1,3,6,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,'Видеонаблюдение','Регистратор + камеры','',0,'',6,1,2,12,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,'Сайт taburetka','LAMP сервер на базе дебиан','',1,'',1,1,2,5,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,'DNS зона taburetka.fidonet','BIND9','',0,'',1,NULL,NULL,5,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,'Инвентаризация','Учет в ИТ','',1,'',1,1,4,6,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(26,'Резервное копирование','Резервное копирование VM при помощи Veeam','',0,'',1,1,2,9,NULL,NULL,NULL,NULL,NULL,0,1,1,'',100,NULL,'[]',NULL,NULL,NULL,'2025-05-09 14:42:12',NULL);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_depends`
--

DROP TABLE IF EXISTS `services_depends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services_depends` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `service_id` int NOT NULL,
  `depends_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-services_depends-service_id` (`service_id`),
  KEY `idx-services_depends-depends_id` (`depends_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_depends`
--

LOCK TABLES `services_depends` WRITE;
/*!40000 ALTER TABLE `services_depends` DISABLE KEYS */;
INSERT INTO `services_depends` VALUES (7,17,14),(8,17,6);
/*!40000 ALTER TABLE `services_depends` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_history`
--

DROP TABLE IF EXISTS `services_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `search_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `external_links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_end_user` tinyint(1) DEFAULT NULL,
  `is_service` tinyint(1) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `cost` float DEFAULT NULL,
  `charge` float DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `notebook` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `weight` int DEFAULT NULL,
  `vm_cores` int DEFAULT NULL,
  `vm_ram` int DEFAULT NULL,
  `vm_hdd` int DEFAULT NULL,
  `responsible_id` int DEFAULT NULL,
  `infrastructure_user_id` int DEFAULT NULL,
  `providing_schedule_id` int DEFAULT NULL,
  `support_schedule_id` int DEFAULT NULL,
  `segment_id` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `partners_id` int DEFAULT NULL,
  `places_id` int DEFAULT NULL,
  `currency_id` int DEFAULT NULL,
  `comps_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `techs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `depends_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `support_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `contracts_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `infrastructure_support_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `maintenance_reqs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `acls_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `maintenance_jobs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `aces_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lic_items_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lic_groups_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `services_history-master_id` (`master_id`),
  KEY `services_history-updated_at` (`updated_at`),
  KEY `services_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_history`
--

LOCK TABLES `services_history` WRITE;
/*!40000 ALTER TABLE `services_history` DISABLE KEYS */;
INSERT INTO `services_history` VALUES (1,20,'2024-03-18 17:06:55','admin',NULL,'name,description,is_service,weight,responsible_id,providing_schedule_id,support_schedule_id,segment_id,currency_id,comps_ids,support_ids,acls_ids','Мониторинг инфраструктуры','Zabbix',NULL,NULL,0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,1,NULL,1,3,6,NULL,NULL,NULL,1,'33',NULL,NULL,'6,9',NULL,NULL,NULL,'5',NULL,NULL,NULL,NULL),(2,20,'2024-03-19 03:42:25','admin',NULL,'acls_ids','Мониторинг инфраструктуры','Zabbix',NULL,NULL,0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,1,NULL,1,3,6,NULL,NULL,NULL,1,'33',NULL,NULL,'6,9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,26,'2025-05-09 14:38:05',NULL,NULL,'name,description,is_end_user,is_service,archived,weight,responsible_id,providing_schedule_id,support_schedule_id,segment_id,currency_id,support_ids','Резервное копирование','Резервное копирование VM при помощи Veeam',NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,1,NULL,1,2,9,NULL,NULL,NULL,1,NULL,NULL,NULL,'6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,26,'2025-05-09 14:38:42',NULL,NULL,'external_links,is_service','Резервное копирование','Резервное копирование VM при помощи Veeam',NULL,'[]',0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,1,NULL,1,2,9,NULL,NULL,NULL,1,NULL,NULL,NULL,'6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,26,'2025-05-09 14:42:12',NULL,NULL,'comps_ids','Резервное копирование','Резервное копирование VM при помощи Veeam',NULL,'[]',0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,1,NULL,1,2,9,NULL,NULL,NULL,1,'41',NULL,NULL,'6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,4,'2025-05-23 11:27:10',NULL,NULL,'name,description,is_service,weight,parent_id,currency_id,techs_ids','Локальная сеть (LAN)','Коммутация устройств',NULL,NULL,0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,1,NULL,'12,14,22,23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,6,'2025-05-23 11:27:10',NULL,NULL,'name,description,search_text,is_service,weight,parent_id,currency_id,comps_ids,techs_ids','Маршрутизация','Управление маршрутизацией','routing',NULL,0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,1,'21,24','12,14',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,7,'2025-05-23 11:27:10',NULL,NULL,'name,description,is_service,weight,parent_id,currency_id,comps_ids,techs_ids','Сетевой экран','Настройка сетевых ограничений',NULL,NULL,0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,1,'21,24','12,14',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,15,'2025-05-23 11:27:10',NULL,NULL,'name,description,is_service,weight,parent_id,currency_id,comps_ids,techs_ids','DHCP сервер','Раздача адресов сетевым устройствам',NULL,NULL,0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,1,'3,32','14',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,21,'2025-05-23 11:27:11',NULL,NULL,'name,description,is_service,weight,responsible_id,providing_schedule_id,support_schedule_id,segment_id,currency_id,techs_ids,support_ids','Видеонаблюдение','Регистратор + камеры',NULL,NULL,0,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,6,NULL,1,2,12,NULL,NULL,NULL,1,NULL,'36,37,38,39,40',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,18,'2025-08-29 07:24:02','admin',NULL,'name,description,is_end_user,is_service,weight,responsible_id,providing_schedule_id,support_schedule_id,segment_id,currency_id,comps_ids,support_ids','Кластер 1С','Трехзвенка БД - Сервер - Клиент',NULL,NULL,1,1,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,9,NULL,4,4,4,NULL,NULL,NULL,1,'17,18,19',NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,5,'2025-08-29 07:24:39',NULL,NULL,'name,description,weight,responsible_id,providing_schedule_id,support_schedule_id,segment_id,currency_id,support_ids','Управление виртуализацией','Все услуги и сервисы по виртуализации',NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,100,NULL,NULL,NULL,9,NULL,1,2,9,NULL,NULL,NULL,1,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `services_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_in_aces`
--

DROP TABLE IF EXISTS `services_in_aces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services_in_aces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `aces_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_in_aces-m2m` (`aces_id`,`services_id`),
  KEY `services_in_aces-aces_id` (`aces_id`),
  KEY `services_in_aces-services_id` (`services_id`),
  CONSTRAINT `fk-services_in_aces-aces_id` FOREIGN KEY (`aces_id`) REFERENCES `aces` (`id`),
  CONSTRAINT `fk-services_in_aces-services_id` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_in_aces`
--

LOCK TABLES `services_in_aces` WRITE;
/*!40000 ALTER TABLE `services_in_aces` DISABLE KEYS */;
/*!40000 ALTER TABLE `services_in_aces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soft`
--

DROP TABLE IF EXISTS `soft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soft` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `manufacturers_id` int DEFAULT NULL,
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `items` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `additional` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Links associated with the software',
  `scans_id` int DEFAULT NULL COMMENT 'ID of the software preview image',
  PRIMARY KEY (`id`),
  KEY `manufacturer_id` (`manufacturers_id`),
  KEY `idx-soft-archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=1082 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Программное обеспечение';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soft`
--

LOCK TABLES `soft` WRITE;
/*!40000 ALTER TABLE `soft` DISABLE KEYS */;
INSERT INTO `soft` VALUES (1,1,'Unifying','программа подключения устройств Logitech к приемнику Unifying','Программа Logitech Unifying','','2018-02-04 14:46:39',NULL,NULL,NULL,NULL),(2,2,'Catalyst Control Center','Управление драйвером видеокарт от AMD','Catalyst Control Center Next Localization ..\n(AMD|ATI) (Catalyst )?Install Manager\nApplication Profiles','AMD Wireless Display v3.0\nATI Stream SDK v2 Developer','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(3,3,'Visual C++ 2008 Redistributable','Распространяемые библиотеки Visual C++ 2008','Microsoft Visual C\\+\\+ 2008 Redistributable - .*\nMicrosoft Visual C\\+\\+ Compilers 2008 Standard Edition - .*','Microsoft Visual C\\+\\+ 2008 ATL Update .*','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(4,3,'Visual C++ 2012 Redistributable','Распространяемые библиотеки Visual C++ 2012','Microsoft Visual C\\+\\+ 2012 x(64|86) \\w+ Runtime - 11.*\nMicrosoft Visual C\\+\\+ 2012 Redistributable \\(x..\\) - 11.*','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(5,4,'Java 7','','Java(\\(TM\\))? 7 Update.*','','2018-02-04 18:29:41',NULL,NULL,NULL,NULL),(6,4,'Java 8','','Java 8 Update.*\nJava Auto Updater','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(7,3,'Visual C++ 2015 Redistributable','Библиотеки Visual C++ 2015','Microsoft Visual C\\+\\+ 2015 x(64|86) \\w+ Runtime - 14.*\nMicrosoft Visual C\\+\\+ 2015 Redistributable \\(x..\\) - 14.*','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(8,3,'Visual J# 2.0 Redistributable','Библиотеки Visual J# 2.0','Microsoft Visual J# 2.0 Redistributable Package.*','','2018-02-05 03:02:51',NULL,NULL,NULL,NULL),(9,3,'Visual C++ 2010 Redistributable','Библиотеки Visual C++ 2010','Microsoft Visual C\\+\\+ 2010 ( x.. )?(Redistributable|Runtime) - (x.. )?10.*','Visual Studio 2010 Prerequisites\nMicrosoft Visual Studio 2010 Shell \\(Isolated\\)','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(10,3,'Windows SDK for Windows 7','','Microsoft Windows SDK for Windows 7 .*\nMicrosoft Windows SDK Intellisense and Reference Assemblies.*\nDebugging Tools for Windows .*\nApplication Verifier .*','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(11,2,'Radeon Settings','Настройки видеокарт от AMD','AMD Settings','','2018-02-05 03:12:05',NULL,NULL,NULL,NULL),(12,3,'Visual C++ 2013 Redistributable','','Microsoft Visual C\\+\\+ 2013 x.. \\w+ Runtime - 12.*\nMicrosoft Visual C\\+\\+ 2013 Redistributable \\(x..\\) - 12.*','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(13,5,'GIMP','Свободный редактор растровых изображений GNU IMAGE MANIPULATION PROGRAM','GIMP \\d\\.\\d\\.\\d+\nGIMP [0-9.]*','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(14,6,'Far Manager 3','Свободный файловый менеджер','Far Manager 3.*','','2018-02-05 03:23:29',NULL,NULL,NULL,NULL),(15,3,'Office 2016 Standard','','Microsoft Office стандартный 2016\nMicrosoft Office Standard 2016','Microsoft Visual Studio 2010 Tools for Office Runtime \\(x\\d{2}\\)\nЯзыковой пакет Microsoft Visual Studio 2010 Tools для среды выполнения Office \\(x\\d{2}\\) - RUS\nMicrosoft Office 2003 Web Components\nMicrosoft Office File Validation Add-In\nАрхивация личных папок Microsoft Outlook\nНадстройка Microsoft для сохранения в формате PDF или XPS для программ выпуска 2007 системы Microsoft Office','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(16,3,'Visio 2010 Premium','Визио отдельно от офиса','Microsoft Office Visio 2010\nMicrosoft Visio премиум 2010','Microsoft Visual Studio 2010 Tools for Office Runtime \\(x..\\)\nЯзыковой пакет Microsoft Visual Studio 2010 Tools для среды выполнения Office \\(x..\\) - .*','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(17,7,'Chrome','Хром','Google Chrome','Google Talk Plugin','2018-02-05 04:20:19',NULL,NULL,NULL,NULL),(18,8,'Firefox','Браузер на движке GECKO','Mozilla Firefox \\d+\\.\\d+(\\.\\d+)? (ESR )?\\(x\\d{2} .*\\)\nFirefox Developer Edition .*\nMozilla Firefox \\(\\d+\\.\\d+(\\.\\d+)?\\)\nFrontMotion Firefox Community Edition \\(ru\\)\nNightly [0-9.]*a1 \\(x\\d{2} ru\\)\nMozilla Firefox \\(x\\d{2} ru\\)','Mozilla Maintenance Service','2023-08-31 05:45:07','reviakin.a',NULL,NULL,NULL),(19,3,'Remote Desktop Connection Manager','RDCMan - программа удаленного доступа по протоколу RDP','Remote Desktop Connection Manager','','2018-02-05 04:26:35',NULL,NULL,NULL,NULL),(20,9,'Workstation','Гипервизор от VMware','VMware Workstation','','2018-02-05 04:27:12',NULL,NULL,NULL,NULL),(21,9,'vSphere Client 5.5','','VMware vSphere Client 5\\.5','','2018-02-05 05:15:25',NULL,NULL,NULL,NULL),(22,9,'vSphere Client 6.0','','VMware vSphere Client 6.0','','2018-02-05 05:19:45',NULL,NULL,NULL,NULL),(23,3,'Visual C++ 2005 Redistributable','','Microsoft Visual C\\+\\+ 2005 Redistributable\nMicrosoft Visual C\\+\\+ 2005 ATL Update.*','Microsoft Visual Studio 2005 Remote Debugger Light \\(x64\\) - ENU\nMicrosoft Visual Studio 2005 Tools for Applications - ENU','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(24,10,'PDFCreator','Может бесплатно использоваться отдельными лицами и компаниями','PDFCreator','','2018-02-05 05:38:04',NULL,NULL,NULL,NULL),(25,11,'Flash Player','','Adobe Flash Player \\d+ (PPAPI|NPAPI|ActiveX)\nAdobe Flash Player \\d+ Plugin','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(26,12,'Winamp','License Proprietary freeware','Winamp','','2018-02-05 05:40:12',NULL,NULL,NULL,NULL),(27,3,'Windows Resource Kit Tools','','Windows Resource Kit Tools - .*','','2018-02-05 05:41:25',NULL,NULL,NULL,NULL),(28,3,'Silverlight','','Microsoft Silverlight','','2018-02-05 05:41:48',NULL,NULL,NULL,NULL),(29,3,'Orca','Программа редактирования пакетов установки MSI','Orca','','2018-02-05 05:42:31',NULL,NULL,NULL,NULL),(31,11,'Reader 9','','Adobe Reader 9\\.\\d+ - \\w+\nAdobe Reader 9 - Russian','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(32,11,'AIR','Платформа для запуска флэш приложений без браузера. Бесплатно','Adobe AIR','','2018-02-05 05:52:21',NULL,NULL,NULL,NULL),(33,3,'Office 2007 Professional Plus','','Microsoft Office Professional Plus 2007\nMicrosoft Office Профессиональный плюс 2007','Microsoft Office 2007 Service Pack 3 \\(SP3\\)\nMicrosoft Office Proofing Tools 2007 Service Pack 3 \\(SP3\\)\nUpdate for Microsoft Office 2007 suites \\(KB\\d+\\) 32-Bit Edition\nMicrosoft Office Proof \\(\\w+\\) 2007\nSecurity Update for Microsoft Office \\w+ 2007 \\(KB\\d+\\) 32-Bit Edition.*\nMicrosoft Office \\w+ MUI \\(\\w+\\) 2007\nMicrosoft Office 2007 Primary Interop Assemblies\nCompatibility Pack for the 2007 Office system','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(34,13,'Audacity','Свободная программа редактирования аудио-файлов','Audacity \\d+\\..*','','2023-08-30 05:47:13','reviakin.a',NULL,NULL,NULL),(35,14,'7-Zip','','7-Zip \\d+\\.\\d+','','2018-02-05 07:18:33',NULL,NULL,NULL,NULL),(36,9,'vSphere Client 5.1','','VMware vSphere Client 5.1','','2018-02-05 07:18:52',NULL,NULL,NULL,NULL),(37,15,'Notepad++','Свободный редактор тестовых файлов и исходных кодов','Notepad++','','2018-02-05 07:21:54',NULL,NULL,NULL,NULL),(38,16,'High Definition Audio Driver','Драйвер звуковой карты','Realtek High Definition Audio Driver','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(39,11,'Reader XI','','Adobe Reader XI( \\(\\d+\\.\\d+\\.\\d+\\))? - \\w+\nAdobe Reader XI$','','2023-08-30 13:51:40',NULL,NULL,NULL,NULL),(40,9,'vCenter Converter Standalone','','VMware vCenter Converter Standalone','','2018-02-05 07:24:04',NULL,NULL,NULL,NULL),(41,3,'Skype for Business Web App Plug-in','','Skype for Business Web App Plug-in','','2018-02-05 07:48:44',NULL,NULL,NULL,NULL),(42,17,'Phone','Бесплатный SIP клиент','3CXPhone','','2023-08-30 13:51:41',NULL,NULL,NULL,NULL),(43,3,'Office 2010 Standard','','Microsoft Office стандартный 2010','Microsoft Visual Studio 2010 Tools for Office Runtime \\(x\\d{2}\\)\nЯзыковой пакет Microsoft Visual Studio 2010 Tools для среды выполнения Office \\(x\\d{2}\\) - RUS\nMicrosoft Office 2003 Web Components\nMicrosoft Office File Validation Add-In\nАрхивация личных папок Microsoft Outlook\nНадстройка Microsoft для сохранения в формате PDF или XPS для программ выпуска 2007 системы Microsoft Office','2023-08-30 13:51:41',NULL,NULL,NULL,NULL),(44,9,'Player','VMware Workstation Player is free for personal non-commercial use (business and non profit use is considered commercial use). ','VMware Player$','','2018-02-05 13:37:27',NULL,NULL,NULL,NULL),(45,18,'NX 9','','Siemens NX 9.\\d+','','2018-02-05 13:40:34',NULL,NULL,NULL,NULL),(46,3,'Skype','IM клиент с сервисом аудио и видео звонков и конференций','Skype™ \\d+\\.\\d+\nSkype, версия \\d+\\.\\d+','Skype Click to Call','2023-08-30 13:51:41',NULL,NULL,NULL,NULL),(47,19,'OpenVPN','','OpenVPN \\d+\\.\\d+\\..*','TAP\\-Windows \\d+\\.\\d+\\..*','2018-02-05 13:48:23',NULL,NULL,NULL,NULL),(49,21,'C++ Redistributables','','Intel\\(R\\) C\\+\\+ Redistributables (for Windows\\*)? on Intel\\(R\\) 64','','2018-02-05 14:01:03',NULL,NULL,NULL,NULL),(50,22,'XenCenter','Консоль управления виртуализацией от Citrix','Citrix XenCenter','','2018-02-05 14:02:00',NULL,NULL,NULL,NULL),(51,23,'qBittorrent','Бесплатный клиент bitTorrent','qBittorrent \\d+\\.\\d+\\.\\d+','','2018-02-05 14:05:01',NULL,NULL,NULL,NULL),(52,24,'TightVNC','GNU GPL','TightVNC','','2018-02-05 14:05:22',NULL,NULL,NULL,NULL),(53,25,'SoapUI','Бесплатное ПО. SoapUI is an open-source web service testing application for service-oriented architectures (SOA) and representational state transfers (REST).','SoapUI .*','','2018-02-05 14:05:51',NULL,NULL,NULL,NULL),(54,26,'Input Director','Software KVM: Система управления несколькими рабочими станциями с одной консоли. Бесплатна только для личного некоммерческого использования','Input Director v\\d\\.\\d+','','2018-02-05 14:11:49',NULL,NULL,NULL,NULL),(55,27,'Инвентаризация Компьютеров Pro','','10-Страйк: Инвентаризация Компьютеров Pro','','2018-02-05 14:12:59',NULL,NULL,NULL,NULL),(56,28,'','','TeamViewer','','2018-02-05 14:13:46',NULL,NULL,NULL,NULL),(57,29,'Тарифер клиент','','Тарифер клиент \\d+\\.\\d+\\.\\d+','','2018-02-05 14:14:22',NULL,NULL,NULL,NULL),(58,30,'VLC media player','Проигрыватель мультимедиа с GPL лицензией','VLC media player','','2018-02-05 14:16:15',NULL,NULL,NULL,NULL),(59,3,'Office 365 Business','','Microsoft Office 365 Business\nMicrosoft 365 для бизнеса\nMicrosoft Офис 365 для бизнеса\nMicrosoft Office 365 для бизнеса','Office 16 Click-to-Run Localization Component\nOffice 16 Click-to-Run Extensibility Component\nOffice 16 Click-to-Run Licensing Component\nMicrosoft Visual Studio 2010 Tools for Office Runtime \\(x64\\)\nЯзыковой пакет Microsoft Visual Studio 2010 Tools для среды выполнения Office \\(x64\\) - RUS\nMicrosoft 365 - ru-ru\nMicrosoft Office 2003 Web Components','2023-08-30 13:51:41',NULL,NULL,NULL,NULL),(60,31,'Endpoint Protection','Антивирус от Symantec','Symantec Endpoint Protection','','2018-02-05 14:20:37',NULL,NULL,NULL,NULL),(61,1,'SetPoint','По идущее в комплекте с манипуляторами от логитек','Logitech SetPoint \\d+\\.\\d+','User\'s Guides','2018-02-05 14:21:28',NULL,NULL,NULL,NULL),(62,32,'Графический драйвер','','NVIDIA Графический драйвер \\d+\\.\\d+\nNVIDIA Драйвер( контроллера)? 3D Vision \\d+\\.\\d+\nNVIDIA Drivers\nNVIDIA Stereoscopic 3D Driver\nNVIDIA PhysX','Панель управления NVIDIA \\d+\\.\\d+\nNVIDIA SceniX.*\nSHIELD Wireless Controller Driver\nNVIDIA Display .*\nNVIDIA nView .*\nNVIDIA WMI .*\nNVIDIA Аудиодрайвер HD .*\nNVIDIA Install Application\nNVIDIA Системное программное обеспечение PhysX .*\nNVIDIA Виртуальное аудиоустройство Miracast .*\nNVIDIA Virtual Audio .*\nNVIDIA .*(Nv)?Container\nОбновления NVIDIA .*\nNVIDIA GeForce Experience.*\nNVIDIA Ansel\nNVIDIA Update Core\nNVIDIA ShadowPlay .*\nNVIDIA System Monitor\nNVIDIA System Update\nNVIDIA Performance','2023-08-30 13:51:41',NULL,NULL,NULL,NULL),(63,33,'Vulkan Run Time Libraries','','Vulkan Run Time Libraries .*','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(64,34,'WinDjView','Бесплатный','WinDjView \\d+\\.\\d+','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(65,35,'Print driver','','HP CIO Components Installer\nHPLaserJetHelp_LearnCenter\nHP Update\nHP Deskjet .* All-In-One Driver Software\nHP (Color )?(LaserJet|LJ) (Enterprise |Pro |Professional )?(\\d+ )?(color )?(MFP C?M\\d+|C?M\\d+ MFP|P\\d+)\nHP Color LaserJet CM2320 MFP\nHPC?LJ(\\d+)?(color)?(Enterprise|Pro)?MFPM\\d+','HP Unified IO\nDot4\nHP LaserJet Toolbox\nScan To\nHPSSupply','2023-09-05 05:39:18','reviakin.a',NULL,NULL,NULL),(66,21,'Management Engine','Компоненты централизованного управления компьютерами','Intel\\(R\\) Management Engine Components\nIntel\\(R\\) ME UninstallLegacy\nIntel® Security Assist\nIntel\\(R\\) Control Center\nIntel\\(R\\) Manageability Engine Firmware Recovery Agent\nIntel\\(R\\) Smart Connect Technology( \\d+\\.\\d+ x\\d{2})?\nIntel® Hardware Accelerated Execution Manager\nIntel\\(R\\) Serial IO\nIntel\\(R\\) Dynamic Platform and Thermal Framework\nПрограммное обеспечение Intel\\® PROSet\\/Wireless','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(67,21,'Chipset Driver','','Intel(\\(R\\)|®) Chipset Device Software\nIntel(\\(R\\)|®) Rapid Storage Technology\nIntel(\\(R\\)|®) Trusted Connect Service Client\nIntel(\\(R\\)|®) Graphics Media Accelerator Driver\nIntel(\\(R\\)|®) USB.* Host Controller.* Driver\nIntel(\\(R\\)|®) TV Wizard\nIntel(\\(R\\)|®) PROSet\\/Wireless Wi\nIntel(\\(R\\)|®) WiDi\nIntel(\\(R\\)|®) Wireless Display','','2023-09-05 05:39:18','reviakin.a',NULL,NULL,NULL),(68,3,'.NET Framework 4','','Microsoft \\.NET Framework 4\\..*\nЯзыковой пакет клиентского профиля Microsoft.NET Framework 4 - RUS\nЯзыковой пакет расширенной версии Microsoft.NET Framework 4 - RUS\nMicrosoft \\.NET Framework 4 Client Profile\nMicrosoft \\.NET Framework 4 Extended\nMicrosoft \\.NET Framework 4 Multi-Targeting Pack','Microsoft ASP.NET MVC 2','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(69,11,'Reader DC','','Adobe Acrobat Reader DC - .*\nAdobe Acrobat Reader DC MUI','Extended Asian Language font pack for Adobe Acrobat Reader DC','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(70,4,'VirtualBox','GNU General Public License - предоставляет пользователю права копировать, модифицировать и распространять (в том числе на коммерческой основе) программы','Oracle VM VirtualBox \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(71,10,'PDF Architect','Бесплатна версия \"PDF Architect FREE\"(доступны модули \"просмотр\" и \"создание\"). Платные: PDF Architect Standard, PDF Architect Pro, PDF Architect Pro+OCR.','PDF Architect \\d+','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(72,16,'Ethernet Controller Driver','','Realtek Ethernet Controller Driver\nRealtek Ethernet Controller All-In-One Windows Driver\nRealtek 8136 8168 8169 Ethernet Driver\nREALTEK GbE & FE Ethernet PCI-E NIC Driver','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(73,3,'MSXML 4','','MSXML 4\\.\\d+( SP\\d).*','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(74,18,'Teamcenter 10','','Teamcenter Visualization 10\\.\\d+( 64-bit)?','Teamcenter Plugin for XMetaL','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(75,22,'Receiver','Клиент. Программное обеспечение RECEIVER (Workspace) используется на основании лицензии Citrix, которая распространяется на специальный выпуск серверного программного обеспечения Citrix, используемого вместе с данным компонентом.','Citrix Receiver \\d+\\.\\d+\nCitrix Receiver','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(76,3,'Windows 10 Pro','','Майкрософт Windows 10 Pro','DHTML Editing Component\nЦентр устройств Windows Mobile\nПомощник по обновлению до Windows 10\nUpdate for Windows 10 for .*\nПроверка работоспособности ПК Windows','2023-10-27 04:56:10','admin',NULL,NULL,NULL),(77,9,'Tools','','VMware Tools','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(78,9,'vSphere Client 5.0','','VMware vSphere Client 5\\.0','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(79,3,'Windows 7 Professional','','Microsoft Windows 7 Профессиональная\nMicrosoft Windows 7 Professional','Предыдущая версия клиента\nDHTML Editing Component\nЦентр устройств Windows Mobile\nWindows Software Development Kit\nОсновные компоненты Windows Live','2023-08-30 14:50:38','reviakin.a',NULL,NULL,NULL),(80,3,'Windows XP Professional','','Microsoft Windows XP Professional','Windows XP - Обновление программного обеспечения\nИсправление для Windows XP \\(KB\nОбновление безопасности для проигрывателя Windows Media - \\(KB\nSecurity Update for Windows Media Player \\(KB\nобновление для Windows XP \\(KB\nWindows XP - Software Updates\n(Update|Hotfix) for Windows XP \\(KB\n%UpdWXP%\nMicrosoft Compression Client Pack 1\\.\\d for Windows XP\nWindows Management Framework Core\nWindows Genuine Advantage Validation Tool.*\nHigh Definition Audio Driver Package\nWindows Imaging Componen\nPrevious Versions Client\nПредыдущая версия клиента','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(81,3,'Internet Explorer','','Windows Internet Explorer','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(82,3,'.NET Framework 3.5','','Microsoft \\.NET Framework 3\\.5( SP\\d)?','Microsoft ASP.NET MVC 2','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(83,3,'.NET Framework 3.0','','Microsoft \\.NET Framework 3\\.0( Service Pack \\d)?','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(84,3,'.NET Framework 2.0','','Microsoft \\.NET Framework 2\\.0( Service Pack \\d)?','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(85,36,'','GUI для SyncThing - средства мультимастер-синхронизации файлов по технологии p2p через bitTorrent протокол.','SyncTrayzor \\(x\\d{2}\\) version .*','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(86,3,'Visio Viewer 2010','Бесплатно','Microsoft Visio Viewer 2010','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(87,3,'Visio Viewer 2016','','Microsoft Visio Viewer 2016','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(88,37,'Snagit 12','','Snagit 12','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(89,38,'389 Management Console','Бесплатное ПО. These works are licensed under a Creative Commons Attribution-ShareAlike 4.0 International License.','389 Management Console','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(90,39,'DameWare Remote Support','','DameWare Remote Support\nDameWare Mini Remote Control Service','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(91,40,'PDF24 Creator','Бесплатное прикладное программное обеспечение','PDF24 Creator','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(92,41,'K-Lite Codec Pack','','K-Lite (Mega )?Codec Pack.*','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(93,42,'Ldap Browser','Бесплатное','LDAPSoft Ldap Browser','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(94,43,'Dia','ПО для рисования схем и диаграмм, входит в GNOME Office','Dia \\(remove only\\)','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(95,3,'Проигрыватель Windows Media 11','','Проигрыватель Windows Media 11\nWindows Media Format 11 runtime','Обновление безопасности для проигрывателя Windows Media.*\nHotfix for Windows Media Player 11 .*','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(96,44,'SumatraPDF','License: GNU General Public License v3. Очень быстрая смотрелка PDF. Написана на вызовах WINAPI + оптимизации рендера PDF. ','SumatraPDF','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(97,35,'P6000 Command View','Консоль управления HP EVA P6000','HP P6000 Command View Software Suite','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(98,45,'V2V Image Converter V8','Конвертер образов виртуальных машин','StarWind V2V Image Converter V8\\.\\d+.*','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(99,46,'Flash','Программа прошивки телефонов Xiaomi','XiaoMiFlash','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(100,18,'Teamcenter EDA','Интеграция приложения ECAD в Teamcenter','Teamcenter EDA 1 .*','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(102,48,'Free Cam 8','iSpring Free Cam помогает быстро создавать видеозаписи, редактировать их и размещать на YouTube в один клик','iSpring Free Cam 8','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(103,49,'TeighaX','Библиотека доступа к проприетарным CAD форматам','TeighaX \\d\\.\\d+','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(104,3,'Windows 8.1 Professional','','Майкрософт Windows 8\\.1 Профессиональная\nMicrosoft Windows 8\\.1 Pro','','2023-08-30 13:51:42',NULL,NULL,NULL,NULL),(105,3,'Office 2010 Home and Business','','Microsoft Office, для дома и бизнеса 2010','Microsoft Visual Studio 2010 Tools for Office Runtime \\(x64\\)\nЯзыковой пакет Microsoft Visual Studio 2010 Tools для среды выполнения Office \\(x64\\) - RUS\nMicrosoft Office$\nMicrosoft Office 2003 Web Components','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(106,3,'Office 2010 Professional Plus','','Microsoft Office профессиональный плюс 2010','Microsoft Office 2003 Web Components\nMicrosoft Office File Validation Add-In','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(107,50,'Agent','OCS inventory NG - бесплатное программное обеспечение. OCS-NG собирает информацию об аппаратном и программном обеспечении сетевых машин, работающих с клиентской программой OCS («Агент инвентаризации OCS»). GNU GPL.','OCS Inventory (NG )?Agent \\d+\\.\\d+\\.\\d+\\.\\d+(-utf8)?','','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(108,21,'Processor Graphics','Драйвер видео','Intel\\(R\\) Processor Graphics','Intel\\(R\\) SDK for OpenCL - CPU Only Runtime Package','2023-09-05 05:39:18',NULL,NULL,NULL,NULL),(109,51,'Reader','Бесплатное прикладное программное обеспечение для просмотра электронных документов в стандарте PDF','Foxit Reader','Foxit Cloud','2023-09-05 05:39:19','reviakin.a',NULL,NULL,NULL),(111,52,'3DMark','','3DMark\nFuturemark SystemInfo','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(112,53,'PL-2303 USB-to-Serial','Драйвер для железки конвертера USB-RS232','PL-2303 USB-to-Serial','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(113,54,'Mini Remote Control Service','Платное ПО','DameWare Mini Remote Control Service','','2023-09-05 05:39:19','reviakin.a',NULL,NULL,NULL),(114,55,'MB Drivers','','ASUS Product Register Program\nASUSUpdate\nPC Probe II\nEPU-6 Engine','AI Suite','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(115,56,'LibreOffice','Свободно распространяемый офисный пакет с открытым исходным кодом','LibreOffice \\d\\.\\d\\.\\d\\.\\d','LibreOffice [0-9.]* Help Pack .*','2023-10-27 04:55:23','admin',NULL,NULL,NULL),(117,58,'','Бесплатная программа с закрытым кодом для записи CD, DVD, HD DVD и Blu-Ray дисков.','CDBurnerXP','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(118,59,'AIMP3','Бесплатный аудиопроигрыватель','AIMP3','','2023-09-05 05:39:19','reviakin.a',NULL,NULL,NULL),(119,60,'PC Connectivity Solution','','PC Connectivity Solution\nПакет драйверов Windows - Nokia pccsmcfd LegacyDriver .*\nNokia Connectivity Cable Driver\nNokia Suite','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(120,61,'Putty','','PuTTY (release|version) \\d+\\.\\d+( \\(64-bit\\))?\nPuTTY development snapshot \\d+\\-\\d+\\-\\d+\\.\\w+','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(121,62,'BDE','Borland Database Engine. Распространяется в месте с др. ПО','BDE\nBorland Database Engine','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(122,63,'Bitrix24','Приложение-клиент - бесплатно','Bitrix24 for Windows\nBitrix24','','2023-10-27 04:56:54','admin',NULL,NULL,NULL),(123,64,'','Бесплатное','TortoiseSVN \\d+\\.\\d+\\.\\d+\\.\\d+ \\(\\d+ bit\\)','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(124,65,'Python','','Python \\d\\.\\d+\\.\\d+','PyQt GPL v\\d+\\.\\d+\\.\\d+ for Python v\\d+\\.\\d+ \\(x\\d+\\)','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(125,66,'Xming','Порт (Linux) Х-сервера под Windows. free','Xming \\d+\\.\\d+\\.\\d+\\.\\d+\nXming-fonts \\d+\\.\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(126,67,'PCB Toolkit','Donateware. Free','Saturn PCB Design, Inc\\. - PCB Toolkit\nSaturn PCB Toolkit .*','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(128,68,'FreeCAD','Бесплатное ПО. Лицензия GNU GPL','FreeCAD \\d+\\.\\d+ - A free open source CAD system','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(129,57,'КОМПАС-3D Viewer V16','Бесплатное ПО','КОМПАС-3D Viewer V16','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(130,69,'Octave','Лицензия GNU GPLv3. GNU Octave - это программное обеспечение с высокоуровневым языком программирования , в основном предназначенным для численных вычислений .','Octave \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(131,3,'Visio 2013 Professional','','Microsoft Visio профессиональный 2013','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(132,3,'Office 2013 Professional Plus','','Microsoft Office профессиональный плюс 2013','Microsoft Office 2003 Web Components','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(133,70,'Голосовой помощник Алиса','','Голосовой помощник Алиса','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(134,57,'КОМПАС-3D Viewer V17','Бесплатное ПО','КОМПАС-3D Viewer v17','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(136,72,'NPort Administration Suite','ПО для управления железками от MOXA - бесплатное','NPort Administration Suite Ver\\d+\\.\\d+','NPort Windows Driver Manager','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(137,73,'Inkscape','Свободный векторный редактор','Inkscape \\d+\\.\\d+','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(138,7,'Earth Pro','Бесплатное','Google Earth Pro\nGoogle Планета Земля','','2023-09-05 05:39:19','reviakin.a',NULL,NULL,NULL),(139,74,'Arduino IDE','IDE для Ардуино (бесплатное)','Arduino','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(140,75,'LibreCAD','Кроссплатформенная, открытая и свободная САПР для 2-мерного черчения и проектирования, создана на основе QCad. Лицензия GNU GPL v2','LibreCAD','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(141,76,'Rutoken Drivers','','Rutoken Drivers\nRutoken support modules for CryptoPro CSP\nДрайверы Рутокен','','2023-09-05 05:39:19','reviakin.a',NULL,NULL,NULL),(142,77,'NC Corrector v4.0','NC Corrector v4.0, это бесплатный редактор визуализатор программ, для фрезерных станков с ЧПУ (G-код).','NC Corrector v4.0','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(143,78,'NRP-Toolkit','Драйвер для сенсоров от R&S','NrpUsb v\\d+\\.\\d+\\.\\d+\\.\\d+\nRohde & Schwarz NRP-Toolkit V\\d+\\.\\d+\\.\\d+(\\.\\d+)?\nRohde & Schwarz NRP USB Driver v\\d+\\.\\d+\\.\\d+\nRsNrpz IVI Driver \\d+\\.\\d+\\.\\d+\nR&S NRP IVI-COM Driver \\d+\\.\\d+\\.\\d+\nRohde&Schwarz, RSNRPZ LabVIEW \\d+\\.\\d+ \\d{2}bit Instrument Driver\nR&S NRPV Virtual Power Meter V\\d+\\.\\d+\\.\\d+(\\.\\d+)?','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(144,79,'Viber','','Viber','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(145,80,'Unity Web Player','','Unity Web Player','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(146,81,'Agent','','Mail\\.Ru Agent \\(версия .*\\)','Служба автоматического обновления программ','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(147,82,'Eywa','','Eywa, версия \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(148,83,'ObzorTR1300','Идет в комплекте с железкой Обзор','Obzor( )?TR1300 v\\d+\\.\\d+\\.\\d+','S2VNA v\\d+\\.\\d+\\.\\d+\nПакет драйверов Windows - Planar.*','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(149,84,'Windows Driver Package','','Windows Driver Package - Silicon Laboratories \\(silabenm\\) Ports.*\nПакет драйверов Windows - Silicon Laboratories \\(silabenm\\) Ports','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(150,85,'Allegro Free Physical Viewers','Бесплатное','Cadence Allegro Free Physical Viewers \\d+\\.\\d+','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(151,86,'Onis 2.5 Free Edition','ONIS 2.5 software - fast, powerful DICOM viewer, Free Edition','Onis 2\\.5 Free Edition','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(152,87,'SAU510-USB device driver','','Sauris GmbH SAU510-USB device driver','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(153,88,'','VisualGPS (Freeware) incorporates many advanced features found in professional programs. Its sole purpose is to display graphically specific NMEA 0183 sentences and show the effects of selective availability (SA).','VisualGPS','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(154,89,'','ofzPCB: FREE 3D Gerber Viewer Самый быстрый, простой и интуитивный способ проверить дизайн Вашей печатной платы перед отправкой на производство','ZofzPCB','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(155,90,'ViewMate','ViewMate Gerber Viewer (free версия)','ViewMate \\d+\\.\\d+','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(156,91,'AkelPad','Свободный текстовый редактор с открытым исходным кодом для операционных систем Microsoft Windows, но может свободно быть запущен под Wine и работать под управлением Unix-подобных операционных систем, таких как Linux. Распространяется под лицензией BSD. ','AkelPad [0-9.]*$\nAkelPad [0-9.]* \\(64-bit\\)\nAkelPad \\d+\\.\\d+\\.\\d+-x64\nAkelPad \\(64-bit\\)','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(158,4,'MySQL Server','GNU GPL 2','MySQL Server \\d+.\\d+\nMySQL Installer - Community','MySQL Utilities\nMySQL For Excel .*\nMySQL Connector Net .*\nMySQL for Visual Studio .*\nMySQL Workbench .*\nMySQL Connector C\\+\\+ .*\nMySQL Connector J\nMySQL Connector\\/ODBC .*\nMySQL Notifier .*\nMySQL Connector\\/C .*\nMySQL Examples and Samples .*\nMySQL Documents .*','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(159,4,'Java SE Development Kit','Бесплатно распространяемый компанией Oracle Corporation комплект разработчика приложений на языке Java','Java(\\(TM\\))? SE Development Kit \\d+\\.\\d+\\.\\d+ \\(64-bit\\)\nJava SE Development Kit \\d+ Update \\d+( \\(64-bit\\))?\nJava\\(TM\\) SE Development Kit \\d+ Update \\d+ \\(64-bit\\)','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(160,3,'SQL Server 2012 Express (LocalDB)','Express бесплатный','Microsoft SQL Server 2012 Express LocalDB','Microsoft System CLR Types for SQL Server 2012.*\nMicrosoft SQL Server 2012 Native Client\nMicrosoft SQL Server 2012 T-SQL Language Service\nMicrosoft SQL Server 2012 Data-Tier App Framework.*\nMicrosoft SQL Server 2012 Management Objects.*\nMicrosoft SQL Server 2012 Transact-SQL ScriptDom.*\nMicrosoft SQL Server Data Tools (Build Utilities )?- \\w+ \\(12.*\nMicrosoft SQL Server 2012 Command Line Utilities\nSQL Server System CLR Types','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(161,3,'SQL Server Compact','Компакт бесплатно','Microsoft SQL Server Compact \\d+\\.\\d+( SP.)? x64 \\w+\nMicrosoft SQL Server Compact','Microsoft SQL Server System CLR Types','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(162,21,'SSD Toolbox','','Intel® SSD Toolbox','','2023-09-05 05:39:19',NULL,NULL,NULL,NULL),(163,3,'SQL Server 2012','','Microsoft SQL Server 2012 \\(64-разрядная версия\\)\nMicrosoft SQL Server 2012 \\(64-bit\\)','Microsoft Report Viewer 2012 Runtime\nMicrosoft SQL Server 2012\nMicrosoft .* (for|для) SQL Server 2012\nSQL Server Browser for SQL Server 2012\nОбозреватель SQL Server для SQL Server 2012\nMicrosoft SQL Server Data Tools (Build Utilities )?- \\w+ \\(12.*\nСистемные типы Microsoft SQL Server System CLR Types\nСлужбы синхронизации контроля версий для SQL Server 2012','2023-08-30 13:51:42',NULL,NULL,NULL,NULL),(164,92,'OrCAD PCB Designer Lite 17','Бесплатное полнофункциональное ПО, ограниченное размерами и сложностью платы','Cadence OrCAD PCB Designer Lite 17\\.\\d+','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(165,93,'LiteManager Free - Server','ПО удаленного управления. Серверная часть - бесплатная','LiteManagerFree - Server','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(166,94,'PowerGPS','отладчик работы GPS модулей от Mediatek','PowerGPS version \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(167,95,'Компоненты «Контур-Экстерн»','','Компоненты «Контур-Экстерн» \\(Администратор\\) .*\nKontur-Extern components \\(Administrator\\) .*\nЛокальное хранилище данных «Контур-Экстерн» \\(Администратор\\) .*\nrtCOMLite 1.0.3.1','ComTools\nCAPICOM.*\nKontur.Diag \\(Administrator\\).*\nrtCOMLite.*','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(168,96,'','БД с открытым исх кодом','Firebird \\d+\\.\\d+\\.\\d+\\.\\d+ \\((Win32|x64)\\)','MSI to redistribute MS VS2005 CRT libraries','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(169,97,'КриптоПро CSP','','КриптоПро CSP\nКриптоПро CADESCOM\nКриптоПро ЭЦП Browser plug-in','','2023-08-30 13:51:42',NULL,NULL,NULL,NULL),(170,3,'Visual FoxPro 9.0 Redistributable','','Microsoft Visual FoxPro OLE DB Provider','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(171,3,'CAPICOM','Снятый с поддержки элемент управления ActiveX, созданный Microsoft с целью помочь разработчикам приложений в получении доступа к услугам, которые позволяют обеспечить безопасность для приложений на основе криптографических функций, реализованных в CryptoA','CAPICOM','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(172,4,'Java 6','','Java\\(TM\\) 6 Update \\d+','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(173,98,'Chipset driver','','VIA Диспетчер устройств платформы','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(174,3,'Office 2013 Standard','','Microsoft Office стандартный 2013','','2018-04-18 06:21:13',NULL,NULL,NULL,NULL),(175,99,'TWAIN Driver','','Kyocera TWAIN Driver','Kyocera Product Library','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(176,100,'Возмещение НДС Налогоплательщик','','ПК \"Возмещение НДС Налогоплательщик\" .*\nПечать НД с PDF\\d+ \\d+\\.\\d+\\.\\d+ \\(пакет\\)\nДекларация 201\\d','RosreestrXML','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(177,21,'OpenCL CPU Runtime','','Intel\\(R\\) OpenCL CPU Runtime','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(178,3,'Office 365','','Microsoft Office 365 - ru-ru\nMicrosoft 365 - ru-ru','Office 16 Click-to-Run Localization Component\nOffice 16 Click-to-Run Extensibility Component\nOffice 16 Click-to-Run Licensing Component\nMicrosoft Visual Studio 2010 Tools for Office Runtime \\(x64\\)\nЯзыковой пакет Microsoft Visual Studio 2010 Tools для среды выполнения Office \\(x64\\) - RUS','2023-09-05 05:39:20','reviakin.a',NULL,NULL,NULL),(181,102,'','Платное ПО Search?. Разраб. intermech.ru.','Intermech','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(183,104,'SQLite ODBC Driver for Win64','This is an open source ODBC driver for the wonderful SQLite 2.8.* and SQLite 3.* Database Engine/Library.','SQLite ODBC Driver for Win64','','2023-09-05 05:39:20','reviakin.a',NULL,NULL,NULL),(184,105,'MWI 2016','Бесплатное ПО для рассчета топологии печатных плат. The MWI-2017 software is free','MWI 2016','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(185,106,'3DxWare 10','ПО к железу от 3Dconnexion','3Dconnexion 3DxWare 10 \\(64-bit\\)','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(186,107,'doPDF printer','Бесплатный ','doPDF \\d+\\.\\d+ printer','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(187,108,'PDF Split And Merge Basic','A free, open source, platform independent software designed to split, merge, mix, extract pages and rotate PDF files','PDF Split And Merge Basic$','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(188,109,'KMPlayer','Какая-то сборка Васян-эдишен','The KMPlayer \\d+\\.\\d+\\.\\d+\\.\\d+ with LAV Filters','','2023-09-05 05:39:20','reviakin.a',NULL,NULL,NULL),(189,110,'Плагин пользователя систем электронного правительства','','Плагин пользователя систем электронного правительства \\(версия \\d+\\.\\d+\\.\\d+\\)( x64)?','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(190,3,'Skype Meetings','Компания Microsoft представила новое бесплатное приложение под названием Skype Meetings, которое предназначено в первую очередь для малого бизнеса. Skype Meetings предоставляет возможность вести диалог сразу между 10 пользователями в течение первых 60 дне','Skype Meetings App','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(191,22,'Online Launcher','','Citrix Online Launcher','','2023-09-05 05:39:20','reviakin.a',NULL,NULL,NULL),(192,111,'USB 3.0 Host Controller Driver','','Renesas Electronics USB 3\\.0 Host Controller Driver','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(193,3,'Office 2007 Standard','','Microsoft Office Стандартный 2007\n2007 Microsoft Office system','Microsoft Office 2007 Primary Interop Assemblies\nCompatibility Pack for the 2007 Office system','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(194,57,'КОМПАС-3D Viewer V14','Бесплатное ПО','КОМПАС-3D Viewer V14','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(195,112,'MF Toolbox','','Canon MF Toolbox .*','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(196,113,'STDU Viewer','Бесплатный для некоммерческого использования. До версии 1.6 был бесплатен полностью','STDU Viewer version .*','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(197,6,'Far Manager 2','Бесплатное ПО. BSD licenses','Far Manager 2','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(198,57,'КОМПАС-3D Viewer V12','КОМПАС-3D Viewer позволяет, не приобретая коммерческой лицензии, просмотреть 3D-модель или чертеж, созданные в системе КОМПАС-3D или КОМПАС-График. Это абсолютно бесплатная система, которую вы можете использовать на рабочем месте или домашнем компьютере.','КОМПАС-3D Viewer V12','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(199,4,'JavaFX','','JavaFX \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(200,114,'Subversion','Apache Subversion - бесплатный. www.apache.org/licenses/LICENSE-2.0','Subversion','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(201,115,'XnView','ПО платное для коммерческой организации. If you intend to use XnView in a company, you must purchase a license. XnView is provided as FREEWARE (NO Adware, NO Spyware) for private or educational use (including non-profit organizations).','XnView \\d+\\.\\d+(\\.\\d+)?','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(202,116,'Monitor Drivers','','Samsung_MonSetup','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(203,4,'Primavera P6 Optional Client R8.1','','Primavera P6 Optional Client R8\\.1','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(204,117,'scilab','Свободная альтернатива Матлаб','scilab-\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(206,118,'Драйверы Guardant','','Драйверы Guardant x\\d{2}','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(207,119,'ABC-4','Автоматизация Выпуска Смет','ABC-4.*','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(208,120,'Sentinel Runtime','','Sentinel Runtime\nSentinel System Driver Installer.*','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(209,7,'Android Studio','Freeware IDE','Android Studio','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(210,3,'IIS 8.0 Express','','IIS 8.0 Express','IIS Express Application Compatibility Database for x\\d{2}','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(211,83,'Obzor103','Идет в комплекте с железкой Обзор','Obzor103 v\\d+\\.\\d+(\\.\\d+)?\nПакет драйверов Windows - Planar, http://planarchel.ru (ezusb) USB  (01/14/2020 3.4.1.41)','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(212,121,'WinPcap','Бесплатная. Драйвер захвата сетевых пакетов BSD lic','WinPcap \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(213,122,'Wireshark','GNU GPL 2. Анализатор сетевого трафика','Wireshark \\d+\\.\\d+\\.\\d+ \\d{2}-bit','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(214,3,'Visual C++ Redistributable','','Microsoft Visual C\\+\\+ Redist - ENU','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(215,123,'CDM Driver Package','','Пакет драйверов Windows - FTDI CDM Driver Package .*\nWindows Driver Package - FTDI CDM Driver Package .*\nFTDI USB Serial Converter Drivers\nFTDI FTD2XX USB Drivers','','2023-09-05 05:39:20',NULL,NULL,NULL,NULL),(216,3,'Help Viewer','','Microsoft Help Viewer \\d+\\.\\d+\nЯзыковой пакет для средства просмотра справки \\(Microsoft\\).*','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(217,124,'Paint.NET','Бесплатный графический редактор','Paint\\.NET v\\d+\\.\\d+\\.\\d+\npaint.net','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(218,3,'XML Notepad','','XML Notepad 2007','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(219,3,'Office 2007 Enterprise','','Microsoft Office Enterprise 2007','Надстройка Microsoft для сохранения в формате PDF или XPS для программ выпуска 2007 системы Microsoft Office\nMicrosoft Visual Studio 2010 Tools for Office Runtime \\(x64\\)\nMicrosoft Office 2007 Primary Interop Assemblies\nCompatibility Pack for the 2007 Office system','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(220,125,'CPU-Z','бесплатное по просмотра инфо о процессоре и материнке','CPUID CPU-Z \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(221,126,'XSL Formatter','Прикладное ПО для EFI (Компонент ТС для рисования схем сборок)','XSL Formatter V\\d+\\.\\d+\\(x64\\)','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(222,127,'XMetaL','Прикладное ПО для EFI (Компонент ТС для рисования схем сборок)','XMetaL Author Enterprise\nXMetaL XMAX','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(223,128,'GUI for Windows','','SAP GUI for Windows \\d+\\.\\d+( \\(Patch \\d+\\))?','Adobe LiveCycle Designer \\d+\\.\\d+\nSAP JVM 6 \\(61_REL i486 opt\\)\nSNC Client Encryption\nEngineering Client Viewer \\d+\\.\\d+\nSAP RFC Library for 32-bit Windows\nSAP Webdynpro IDE Form Designer Control\nSAPSetup Automatic Workstation Update Service\nSAP Graphics Chart Control\nMicrosoft redistributable runtime DLLs\nSAP .Net Connector','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(224,3,'Project 2016 Standard','','Microsoft Project стандартный 2016','','2018-04-28 06:55:29',NULL,NULL,NULL,NULL),(225,1,'Webcam Software','','Logitech Webcam Software','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(226,57,'КОМПАС-3D V12','Бесплатное ПО','КОМПАС-3D V12','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(227,101,'2012','','SolidWorks 2012 x64 Edition SP\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(228,129,'eDrawings 2014','','eDrawings 2014','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(229,130,'Cortona2D Viewer','Компонент ТС. Лицензиат может использовать ПО только для личных и некоммерческих целей. Для любого другого вида использования необходимо получить соответствующую лицензию у Лицензиара.','Cortona2D Viewer','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(230,130,'Cortona3D Viewer','Компонент ТС. Лицензиат может использовать ПО только для личных и некоммерческих целей. Для любого другого вида использования необходимо получить соответствующую лицензию у Лицензиара.','Cortona3D Viewer','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(231,130,'RapidAuthor S','Компонент ТС. Платное ПО','RapidAuthor S','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(232,131,'2ГИС','Бесплатное ПО','2ГИС \\d+\\.\\d+\\.\\d+\\.\\d+\nДанные 2ГИС г\\..*','Модуль \"Фотографии на карте города\" для 2ГИС','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(233,132,'Meetings','','Cisco WebEx Meetings\nМенеджер event-совещаний WebEx для Internet Explorer','','2023-09-05 05:39:21','reviakin.a',NULL,NULL,NULL),(234,133,'foobar2000','Only unmodified installers can be redistributed; redistribution of foobar2000 binaries in any other form is not permitted. Проприетарная лицензия проигрывателя разрешает свободное распространение только немодифицированных копий установщика программы.','foobar2000 v\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(235,134,'CutePDF Writer','Бесплатно только для некоммерческого использования','CutePDF Writer \\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(236,135,'QIP Shot','QIP Shot — это бесплатное приложение для получения снимков с экрана, захвата видео и проведения онлайн-трансляций. QIP Shot позволяет редактировать снимки, сохранять их в интернете и получать на них прямые ссылки.','QIP Shot \\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(237,136,'Genius','Каталог инструмента Vargus','VargusGen','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(238,137,'Free Video Call Recorder for Skype','\"Программа абсолютно бесплатна\"','Free Video Call Recorder for Skype version \\d+\\.\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(239,138,'ToolExpert','Каталог инструмента Fraisa','ToolExpert Web-Setup','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(242,57,'КОМПАС-3D Viewer V11','КОМПАС-3D Viewer позволяет, не приобретая коммерческой лицензии, просмотреть 3D-модель или чертеж, созданные в системе КОМПАС-3D или КОМПАС-График. Это абсолютно бесплатная система, которую вы можете использовать на рабочем месте или домашнем компьютере.','КОМПАС-3D Viewer V11','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(243,101,'DWGeditor','DWGeditor is free with the purchase of SolidWorks software.','DWGeditor','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(244,139,'КриптоАРМ','\"КриптоАРМ Старт\" не требует регистрации и не ограничена временем ее использования. \"КриптоАРМ Стандарт\" и \"КриптоАРМ СтандартPRO\" требуют ввода лицензионного ключа.','КриптоАРМ','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(245,140,'Scanner Maintenance','ПО для сканера','Scanner Maintenance\nWIDEsystem\nNextimage','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(246,82,'ECE Radar','','ECE Radar, версия,*','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(247,141,'doxygen','Генератор документации программного кода. Лиц GNU','doxygen \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(248,142,'Dr.Explain','You may use the free unregistered copy of the Dr.Explain software as long as you wish. The free unregistered copy is almost fully functional though all output images are watermarked. To use all the benefits of the program please order the Dr.Explain licen','Dr.Explain','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(249,123,'FT_Prog','FT_PROG is a free EEPROM programming utility for use with FTDI devices.  It is used for modifying EEPROM contents that store the FTDI device descriptors to customize designs.  FT_PROG also includes the capability of programming the Vinculum firmware.','FT_Prog','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(250,143,'WinSCP','Свободный графический клиент протоколов SFTP и SCP, предназначенный для Windows. Распространяется по лицензии GNU GPL. Обеспечивает защищённое копирование файлов между компьютером и серверами, поддерживающими эти протоколы','WinSCP \\d+\\.\\d+\\.\\d+\nWinSCP plugin for FAR.*','','2023-09-05 05:39:21','reviakin.a',NULL,NULL,NULL),(251,144,'Bouml','Редактор UML схем. Since the release 7.0 BOUML is again a free software.','Bouml \\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(252,145,'Inno Setup','Система создания инсталляторов для Windows программ с открытым исходным кодом. Впервые выпущенный в 1997 году, Inno Setup сегодня конкурирует и даже превосходит многие коммерческие установщики по функциональности и стабильности','Inno Setup, версия .*','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(253,146,'Qt ','OpenSource - Usage under (L)GPL v3 license. Есть версия Commercial.','Qt OpenSource \\d+\\.\\d+\\.\\d+\nQt [0-9.]*$','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(254,147,'StarUML','Проект с открытым кодом для разработки быстрых, гибких, расширяемых, функциональных и распространяемых бесплатно платформ UML/MDA для 32-разрядных систем Windows. ','StarUML \\d+\\.\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(255,3,'Windows 7 Ultimate','','Microsoft Windows 7 Максимальная','Предыдущая версия клиента','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(256,148,'MPC-HC','Media Player Classic (MPC) (проект guliverkli) — свободный проигрыватель','MPC-HC \\d+\\.\\d+\\.\\d+( \\(\\d{2}-bit\\))?','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(257,96,'Клиент СУБД Firebird','','Клиент СУБД Firebird \\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(258,120,'Aladdin Monitor','','Aladdin Monitor \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(259,120,'HASP License Manager','','HASP License Manager','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(260,3,'Visio 2007 Professional','','Microsoft Office Visio Профессиональный 2007','Compatibility Pack for the 2007 Office system','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(261,41,'QuickTime Alternative','QuickTime Alternative (codec)','QT Lite \\d+\\.\\d+\\.\\d+\nQuickTime Alternative \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(264,101,'viewer','eDrawings is free software that lets you view and print eDrawings(eDRW, ePRT, eASM), native SolidWorks documents (sldprt, sldasm, slddrw) , DXF, and DWG format files.','SolidWorks viewer','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(265,149,'Rainlendar','Версия Lite бесплатна, версия The Pro version of Rainlendar requires a license file to operate fully.','Rainlendar2 \\(remove only\\)','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(266,83,'Obzor304','Идет в комплекте с железкой Обзор','Obzor304 v.*','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(267,83,'S2VNA','Очередное ПО для приборов от Планар. ПО предоставляется по запросу.','S2VNA v\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(268,150,'ModelSim-Altera 6 Starter Edition','Free. No license required','ModelSim-Altera 6\\.\\d+. \\(Quartus II \\d+\\.\\d+\\) Starter Edition','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(270,72,'NPort Windows Driver Manager','','NPort Windows Driver Manager','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(272,153,'COM Port Toolkit','Shareware ПО для отладки работы COM порта. COM Port Toolkit is available for FREE downloading and using for 30-day trial period. When this period expires you must uninstall it from your system. ','COM Port Toolkit \\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(273,154,'iTunes','','iTunes','Apple Mobile Device Support\nПоддержка программ Apple \\(\\d{2} бит\\)','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(274,154,'Bonjour','','Bonjour','Apple Software Update','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(275,101,'2010','','SolidWorks 2010 SP\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(276,3,'Windows Media Player Firefox Plugin','','Windows Media Player Firefox Plugin','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(277,7,'SketchUp','Версия не Про - бесплатная, В апреле 2012 Google продал SketchUp компании Trimble Navigation','Google SketchUp 8','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(278,155,'Shared Components','Permission is hereby granted, free of charge and subject to the terms set forth below, to any person obtaining a copy of this Intellectual Property and any associated documentation','IVI Shared Components \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:21',NULL,NULL,NULL,NULL),(279,156,'Плагин пользователя портала гос.услуг','','Плагин пользователя портала гос.услуг \\d+\\.\\d+\\.\\d+(\\.\\d+)?','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(280,157,'PCI Multi-IO Controller Driver','','MosChip PCI Multi-IO Controller','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(281,158,'AppCAD','The following RF design software is provided free of charge as a service to the RF and microwave design community.','AppCAD','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(282,159,'WinConsole','','WinConsole','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(283,3,'Windows 7 Enterprise','','Microsoft Windows 7 Корпоративная','Предыдущая версия клиента\nDHTML Editing Component\nЦентр устройств Windows Mobile\nОбновление драйверов Центра устройств Windows Mobile','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(284,7,'Earth Plug-in','','Google Earth Plug-in','','2023-09-05 05:39:22','reviakin.a',NULL,NULL,NULL),(285,35,'Scan driver','','HP Scanjet\nHP Imaging Device\nI\\.R\\.I\\.S.\nСканирование документов','','2023-09-05 05:39:22','reviakin.a',NULL,NULL,NULL),(286,160,'AutoCAD 2011','Платное ПО','AutoCAD 2011 - Русский','Autodesk Material Library 2011.*','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(287,161,'LS','ПО лазерного сканера, ставится вместе с Автокадом','FARO LS \\d+\\.\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(288,162,'VRazvedke','Какаято хрень от васяна для просмотра закрытых страниц вконтакта, бесплатная','VRazvedke','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(289,163,'WebMoney Keeper Classic','Бесплатное ПО. Keeper WinPro – программа для управления кошельками и работы с системой WebMoney.','WebMoney Keeper Classic \\d+\\.\\d+\\.\\d+\\.\\d+\nWebMoney Keeper WinPro','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(290,164,'Viewpoint Media Player','Какое то adware типа плагина для IE','Viewpoint Media Player','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(292,166,'WebMoney Agent','','WebMoney Agent','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(293,167,'','The GNU General Public License is a free, copyleft license for software and other kinds of works. ','TortoiseGit \\d+\\.\\d+\\.\\d+\\.\\d+ \\(\\d{2} bit\\)','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(294,168,'Html5 geolocation provider','','Html5 geolocation provider','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(295,3,'Visio Viewer 2013','Бесплатная','Microsoft Visio Viewer 2013','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(296,169,'PC Companion','','Sony PC Companion \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(297,170,'FreeCommander XE','','FreeCommander XE','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(298,171,'Recuva','Бесплатна только версия \"Recuva FREE\"','Recuva','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(299,172,'WinMerge','Свободное ПО с открытым исходным кодом для сравнения и синхронизации файлов и каталогов','WinMerge \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(300,173,'Mp3tag','','Mp3tag v.*','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(301,174,'QUIK','система интернет трейдинга от сбера','QUIK, версия .*','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(302,175,'ISO to USB','Бесплатная утилитка для записи ISO на USB','ISO to USB','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(303,176,'TortoiseHg','TortoiseHg is free, donations are voluntary.','TortoiseHg \\d+\\.\\d+\\.\\d+ \\(x\\d{2}\\)','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(304,160,'DWG TrueView','Вы можете использовать DWG True View в коммерческих целях','DWG TrueView [0-9]*$','','2023-09-05 05:39:22','reviakin.a',NULL,NULL,NULL),(305,6,'Far Manager','','Far Manager v1\\.\\d+','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(306,3,'.NET Framework 1.1','','Microsoft .NET Framework 1.1','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(307,57,'КОМПАС-3D Viewer V10','','KOMPAS-3D Viewer V10\nКОМПАС-3D Viewer V10','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(308,177,'FileZilla Client','','FileZilla Client \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(309,100,'Декларация 2009','','Декларация 2009','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(310,21,'Network Connections','','Intel\\(R\\) Network Connections','Intel\\(R\\) Network Connections Drivers','2023-09-05 05:39:22','reviakin.a',NULL,NULL,NULL),(311,178,'µTorrent','Бесплатно - Adware','µTorrent','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(312,8,'Thunderbird','Бесплатная кроссплатформенная свободно распространяемая программа для работы с электронной почтой','Mozilla Thunderbird .*','Mozilla Maintenance Service','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(313,153,'TCP Port Toolkit','','TCP Port Toolkit \\d+\\.\\d+','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(314,179,'Embedded Workbench for ARM','a 30-day time-limited but fully functional license','IAR Embedded Workbench for ARM','','2023-09-05 05:39:22','reviakin.a',NULL,NULL,NULL),(315,180,'Packet Tracer 6.2 Student','Бесплатно для учащихся Сетевой Академии Cisco','Cisco Packet Tracer 6.2 Student','','2023-09-05 05:39:22','reviakin.a',NULL,NULL,NULL),(316,181,'Advanced IP Scanner 2.5','','Advanced IP Scanner 2.5','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(317,182,'-S-20','','PERCo-S-20','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(318,183,'Burning Studio FREE','','Ashampoo Burning Studio FREE','','2023-09-05 05:39:22','reviakin.a',NULL,NULL,NULL),(319,184,'CalDavSynchronizer','','CalDavSynchronizer','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(320,185,'Download Master','','Download Master','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(321,186,'Camera Station 5.07','','AXIS Camera Station 5.07','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(322,182,'-SN01 Базовое ПО','','PERCo-SN01 Базовое ПО','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(323,187,'AutoGreen B12.0206.1','','AutoGreen B12.0206.1','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(324,70,'Менеджер браузеров','','Менеджер браузеров','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(325,187,'@BIOS','','@BIOS','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(326,188,'Free DWG Viewer','Бесплатное','Free DWG Viewer','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(327,70,'Элементы Яндекса 8.7 для Internet Explorer','','Элементы Яндекса 8.7 для Internet Explorer','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(328,189,'Декларация 2017','','Декларация 2017','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(329,35,'Customer Participation Program','','HP Customer Participation Program','','2023-09-05 05:39:22','reviakin.a',NULL,NULL,NULL),(330,3,'Office 2013 Home and Business','','Microsoft Office для дома и бизнеса 2013','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(331,27,'ABC-4 (5.1 RU)','','ABC-4 (5.1 RU)','','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(332,190,'ГРАНД-Смета, версия 8','','ГРАНД-Смета, версия 8.*','Утилита удаленной технической поддержки, версия.*\nГранд Калькулятор, версия 1.*\nМенеджер обновлений','2023-09-05 05:39:22',NULL,NULL,NULL,NULL),(333,190,'ГРАНД-Смета 2018','','ГРАНД-Смета 2018','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(334,190,'ГРАНД-СтройИнфо, версия 5','','ГРАНД-СтройИнфо, версия 5.*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(335,190,'Гранд Калькулятор, версия 1','','Гранд Калькулятор, версия 1.*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(336,112,'Color Network ScanGear','','Color Network ScanGear','','2023-09-05 05:39:23','reviakin.a',NULL,NULL,NULL),(337,191,'USB Mouse Quality Testing Program V6.0','','A4tech USB Mouse Quality Testing Program V6.0','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(338,99,'Product Library','','Kyocera Product Library','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(339,3,'Visual Studio 2010 Tools for Office Runtime','Если обнаруживается само по себе - значит Офис удалили удалили, а утилита осталась','Microsoft Visual Studio 2010 Tools for Office Runtime \\(x\\d{2}\\)','Языковой пакет Microsoft Visual Studio 2010 Tools для среды выполнения Office \\(x\\d{2}\\) - \\w+','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(340,99,'Status Monitor 5','','KYOCERA Status Monitor 5','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(341,7,'Toolbar for Internet Explorer','','Google Toolbar for Internet Explorer','','2023-09-05 05:39:23','reviakin.a',NULL,NULL,NULL),(342,16,'Ethernet Diagnostic Utility','','Realtek Ethernet Diagnostic Utility','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(343,192,'nanoCAD x64 Plus 8','','nanoCAD x64 Plus 8','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(344,192,'nanoCAD СПДС 7','','nanoCAD СПДС 7.0 x64','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(345,192,'nanoCAD x64 Plus 10','','nanoCAD x64 Plus 10','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(346,192,'NormaCS 3.0 Lite Клиент','NormaCS версия Lite бесплатна','Nanosoft NormaCS 3.0 Lite Клиент','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(347,193,'ThinkVantage Password Manager','','ThinkVantage Password Manager','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(348,193,'System Interface Driver','','Lenovo System Interface Driver','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(349,193,'Auto Scroll Utility','','Lenovo Auto Scroll Utility','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(350,193,'ThinkPad UltraNav Driver','','ThinkPad UltraNav Driver','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(351,193,'ThinkVantage Communications Utility','','ThinkVantage Communications Utility','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(352,193,'Solution Center','','Lenovo Solution Center','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(353,193,'System Update','','Lenovo System Update','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(354,193,'Patch Utility','','Lenovo Patch Utility','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(355,188,'Free DWG Viewer 16.0','','Free DWG Viewer 16.0','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(356,3,'Sync Framework 2.0 Provider Services','','Microsoft Sync Framework 2.0 Provider Services (x64) ENU','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(357,101,'eDrawings 2018 x64','','eDrawings 2018 x64','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(358,194,'FreeFileSync 10','','FreeFileSync 10','','2023-09-05 05:39:23','reviakin.a',NULL,NULL,NULL),(359,151,'FineReader 11 Corporate Edition','','ABBYY FineReader 11 Corporate Edition','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(360,195,'Solid PDF Tools v8','','Solid PDF Tools v8','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(361,195,'SolidWordAddIn','','SolidWordAddIn','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(362,195,'SolidPDFCreator','','SolidPDFCreator','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(363,195,'Solid Converter v8','','Solid Converter v8','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(364,193,'Broadcom 802.11 Network Adapter','','Broadcom 802.11 Network Adapter','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(365,193,'HD Audio','','Conexant HD Audio','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(366,196,'XMind 8','','XMind 8','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(367,3,'Skype Web Plugin','','Skype Web Plugin','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(368,197,'Oxygen XML Editor','','Oxygen XML Editor','','2018-08-15 14:24:25',NULL,NULL,NULL,NULL),(369,37,'Snagit 13','','Snagit 13','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(370,3,'Visio 2016 Standard','','Microsoft Visio стандартный 2016','','2018-08-16 05:52:20',NULL,NULL,NULL,NULL),(371,3,'Visual C++ 2017 Redistributable','','Microsoft Visual C\\+\\+ 2017 Redistributable \\(x\\d{2}\\) - .*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(372,8,'Maintenance Service','Если обнаруживается само по себе - значит фаерфокс удалили, а служба осталась (можно сносить)','Mozilla Maintenance Service','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(373,4,'J2SE Runtime Environment 5','','J2SE Runtime Environment 5\\.0 Update.*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(374,70,'Диск','','Яндекс\\.Диск','','2023-09-05 05:39:23','reviakin.a',NULL,NULL,NULL),(375,32,'Display Control Panel','','NVIDIA Display Control Panel','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(376,3,'Visio 2016 Professional','','Microsoft Visio профессиональный 2016.*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(377,101,'eDrawings 2016','Free','eDrawings 2016$\neDrawings 2016 x64$','SOLIDWORKS 2017 Document Manager API\nSOLIDWORKS 2016 Document Manager API.*','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(379,151,'Lingvo x6','','ABBYY Lingvo x6','Lernout & Hauspie TruVoice American English TTS Engine\nL&H TTS3000.*','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(380,199,'TeXstudio','Licensed under the GPL v2. Being open source, you are free to use and to modify it as you like.','TeXstudio.*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(381,200,'SMath Studio','Free. Tiny, powerful, free mathematical program with WYSIWYG editor and complete units of measurements support.','SMath Studio','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(382,201,'The KMPlayer','Бесплатный','The KMPlayer.*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(383,3,'Набор средств Visual Studio для работы с системой Office','Visual Studio Tools for Office','Набор средств Visual Studio для работы с системой Office.*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(384,3,'Помощник по обновлению до Windows 10','','Помощник по обновлению до Windows 10','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(385,101,'eDrawings 2010','Бесплатный просмотрщик солида','eDrawings 2010','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(386,3,'Visio 2007 Standard','','Microsoft Office Visio Стандартный 2007','Compatibility Pack for the 2007 Office system','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(387,112,'Printer Driver','','imagePROGRAF Printer Driver Extra Kit\nПринтеры Canon CAPT','','2023-09-05 05:39:23','reviakin.a',NULL,NULL,NULL),(388,192,'NormaCS 3.0 Client','Клиент NormaCS','Nanosoft NormaCS 3.0 Client','Nanosoft NormaCS 3.0 Demo Client\nNanosoft NormaCS 3.0 Lite Клиент','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(389,190,'ГРАНД-Смета, версия 6','','ГРАНД-Смета, версия 6.*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(390,190,'Менеджер обновлений','','Менеджер обновлений .*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(391,32,'Системное программное обеспечение PhysX','','NVIDIA Системное программное обеспечение PhysX .*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(392,3,'SQL Server 2005 Compact Edition','','Microsoft SQL Server 2005 Compact Edition .*','','2023-09-05 05:39:23',NULL,NULL,NULL,NULL),(393,21,'SDK for OpenCL - CPU Only Runtime Package','','Intel\\(R\\) SDK for OpenCL - CPU Only Runtime Package','','2023-09-05 05:39:23','reviakin.a',NULL,NULL,NULL),(394,192,'nanoCAD Геоника 5','','nanoCAD Геоника 5.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(395,192,'nanoCAD Геоника 6','','nanoCAD Геоника 6.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(396,202,'Модуль обмена данными через последовательный порт','','','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(397,202,'ТРАНСКОР','','ТРАНСКОР','Модуль импорта данных в формате GSI\nМодуль импорта данных в формате SDR20-33\nМодуль импорта данных в формате IDEX\nМодуль обмена данными через последовательный порт\nЦентр управления ПО CREDO\nСистема защиты Echelon-II','2023-08-30 13:51:44',NULL,NULL,NULL,NULL),(398,202,'Трансформ','','Трансформ','','2018-08-20 13:15:05',NULL,NULL,NULL,NULL),(399,202,'CREDO_DAT 4 LITE','','CREDO_DAT 4 LITE','Модуль импорта данных SAT','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(400,160,'AutoCAD 2012','','AutoCAD 2012','Autodesk Material Library Base Resolution Image Library 2012\nAutodesk Inventor Fusion plug-in for AutoCAD 2012\nAutodesk Inventor Fusion 2012\nAutodesk Material Library 2012\nAutodesk Content Service\nAutodesk Inventor 2012.*\nAutodesk Inventor Content Center Libraries 2012 \\(Desktop Content\\)\nAutodesk Inventor Fusion for Inventor 2012 Add-in\nAutodesk Material Library Low Resolution Image Library 2012\nSPDS Extension v2.0 \\(64-bit\\)\nSPDS Extension v1.*','2023-09-05 05:39:24','reviakin.a',NULL,NULL,NULL),(401,70,'Punto Switcher','Программа Punto Switcher является бесплатной и может быть установлена на любой компьютер без ограничений.Коммерческим использованием считается только перепродажа программы третьим лицам.','Punto Switcher.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(402,120,'Sentinel Protection Installer','','Sentinel Protection Installer .*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(403,203,'Electronics USB 3.0 Host Controller Driver','','NEC Electronics USB 3.0 Host Controller Driver','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(404,192,'nanoCAD Plus 8','','nanoCAD Plus 8.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(405,204,'AR-M160/M205/5220 Series PCL/PS T3 Printer Driver','','SHARP AR-M160/M205/5220 Series PCL/PS T3 Printer Driver','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(406,187,'ON_OFF Charge','','ON_OFF Charge .*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(407,187,'DMIView','','DMIView .*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(408,205,'DIALux evo ','Free software','DIALux evo .*','DIAL Data Dispatcher\nDIAL Communication Framework','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(409,32,'System Update','','NVIDIA System Update','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(410,32,'Performance','','NVIDIA Performance','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(411,32,'System Monitor','','NVIDIA System Monitor','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(412,187,'Easy Tune ','','Easy Tune .*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(413,206,'Oce WPD','для плоттера дрова','Oce WPD','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(414,3,'WSE 3.0 Runtime','Web Services Enhancements 3.0 for Microsoft .NET (WSE) enables developers to create interoperable Web services with advanced Web services features','Microsoft WSE 3.0 Runtime','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(415,207,'ПК АЭМО 4.0','','ПК АЭМО 4.0','','2018-08-20 13:36:57',NULL,NULL,NULL,NULL),(416,192,'nanoCAD 5.1','Бесплатная версия отечественной универсальной САПР-платформы','nanoCAD \\d+\\.\\d+\nnanoCAD \\d+','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(417,208,'PDF-XChange 3','Существует free версия это PDF-XChange Editor(ранее Viewer), но которая включает платную программу- Includes PDF-XChange Lite printer (FREE for Non-Commerical use only), возможно платный функционал просто не работает. ','PDF-XChange \\d+','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(418,209,'WinRAR 5.20 (64-разрядная)','Распространяется по shareware-лицензии. После 40 дней пробной эксплуатации пользователю предлагается приобрести лицензию.','WinRAR \\d+\\.\\d{2} \\(\\d{2}-разрядная\\)','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(419,187,'Raid Configurer','','Gigabyte Raid Configurer','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(420,210,'','Открытая замена коммерческому Microsoft Project','ProjectLibre','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(421,160,'AutoCAD 2014','','Autodesk AutoCAD 2014 — Русский.*','Autodesk Content Service\nAutodesk Material Library 2014\nAutodesk Material Library Base Resolution Image Library 2014\nSketchUp Import for AutoCAD 2014','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(422,205,'DIALux 4','Free software','DIALux 4.*','DIAL Communication Framework\nDIAL Data Dispatcher','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(423,211,'Catalogue','Бесплатные каталоги для DIALux','Vatra Catalogue','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(424,212,'Catalogue','Бесплатные каталоги для DIALux','GALAD Catalogue','','2023-09-05 05:39:24','reviakin.a',NULL,NULL,NULL),(425,213,'Lamp PlugIn','Бесплатные каталоги для DIALux','OSRAM Lamp PlugIn [0-9.]*$','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(426,214,'Catalogue','Бесплатные каталоги для DIALux','Lighting Technologies Catalogue','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(427,215,'PDF reDirect','Бесплатный','PDF reDirect .*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(428,192,'nanoCAD СПДС 5','','nanoCAD СПДС 5.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(429,192,'nanoCAD СПДС 6','','nanoCAD СПДС 6.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(430,192,'nanoCAD СПДС 4','','nanoCAD СПДС 4.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(431,216,'ELMA Агент','','ELMA Агент','','2023-09-05 05:39:24','reviakin.a',NULL,NULL,NULL),(432,122,'Wireshark 2','GNU GPL 2','Wireshark 2[0-9.]*$\nWireshark 2[0-9.]* \\(64-bit\\)$','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(433,217,'Гидравлический расчет','Бесплатно','НПО ПАС Гидравлический расчет','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(434,217,'Расчет массы','Бесплатно','НПО ПАС Расчет массы','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(435,18,'Interactive Catalog CA 01','','Interactive Catalog CA 01','Российский пакет данных для Interactive Catalog  CA 01','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(436,18,'Российский пакет данных для Interactive Catalog  CA 01','','Российский пакет данных для Interactive Catalog  CA 01','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(437,218,'MasterSCADA','Среда разработки MasterSCADA бесплатна. Среда исполнения в Demo версии содержит все модули и опции, и не имеет ограничений по количеству тегов, но имеет ограничение на время работы — 1 час, после чего требуется перезапуск.','MasterSCADA','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(438,192,'nanoCAD СПДС Стройплощадка 5','','nanoCAD СПДС Стройплощадка 5.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(439,192,'nanoCAD СПДС Стройплощадка 4','','nanoCAD СПДС Стройплощадка 4.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(440,160,'AutoCAD 2013','','AutoCAD 2013 – Русский.*','Autodesk Content Service\nAutodesk Material Library 2013\nAutodesk Material Library Base Resolution Image Library 2013\nAutodesk App Manager\nAutodesk Featured Apps\nAutodesk Sync','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(441,219,'Sync','','HTC Sync','HTC Driver Installer\nHTC BMP USB Driver\nIPTInstaller','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(442,220,'Foundation','','Foundation','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(443,192,'nanoCAD Конструкции 4','','nanoCAD Конструкции 4.*','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(444,221,'DAEMON Tools Lite','Lite-версия бесплатна для «домашнего» некоммерческого использования','DAEMON Tools Lite','','2023-09-05 05:39:24','reviakin.a',NULL,NULL,NULL),(446,57,'КОМПАС-Менеджер 5','','КОМПАС-Менеджер 5','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(447,223,'Free WAV to MP3 Converter','Free','Free WAV to MP3 Converter \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(448,27,'Advanced Port Scanner v1.3','Бесплатный сканер портов','Advanced Port Scanner v\\d+.\\d+','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(449,7,'Earth','','Google Earth','','2023-09-05 05:39:24','reviakin.a',NULL,NULL,NULL),(450,3,'SQL Server 2008 R2','Бесплатный только MS SQL Server Express 2008 R2','Microsoft SQL Server 2008 R2','Файлы поддержки установки Microsoft SQL Server .*\nMicrosoft SQL Server Native Client\nMicrosoft SQL Server VSS Writer\nУстановка Microsoft SQL Server 2008 R2.*\nФайлы поддержки программы установки Microsoft SQL Server 2008\nPrevious Versions Client\nСобственный клиент Microsoft SQL Server 2008 R2\nСлужбы синхр\\. контроля версий Microsoft SQL Server VSS Writer\nПолитики Microsoft SQL Server 2008 R2\nMicrosoft SQL Server Compact 3.5 SP2 .*\nMicrosoft SQL Server Browser\nMicrosoft Report Viewer Redistributable 2008 SP1.*\nФайлы поддержки программы установки Microsoft SQL Server 20xx\nMicrosoft SQL Server 2008 Setup Support Files','2023-08-30 13:51:45',NULL,NULL,NULL,NULL),(451,224,'CloneCD','Условно бесплатное ПО (shareware) - программы которые можно бесплатно скачать и использовать определенный промежуток времени','CloneCD\nCloneCD \\d+\\.\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(453,131,'Модуль GPS для ДубльГИС','','Модуль GPS для ДубльГИС','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(454,226,'Opera','','Opera \\d+\\.\\d+\nOpera Stable','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(455,227,'HTML Help Workshop','','HTML Help Workshop','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(456,228,'The Bat! v7.0.0 (32-bit)','Платная программа','The Bat! v\\d+\\.\\d+\\.\\d+ \\(\\d{2}-bit\\)','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(457,131,'Модуль \"Фотографии на карте города\" для 2ГИС','','Модуль \"Фотографии на карте города\" для 2ГИС','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(458,227,'Helpman3','','Helpman3','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(459,3,'Visual FoxPro 7.0 Professional','','Microsoft Visual FoxPro 7.0 Professional - English','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(460,2,'AVIVO Codecs','','ATI AVIVO Codecs','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(461,229,'Lazarus','','Lazarus .\\..\\..','','2023-09-05 05:39:24',NULL,NULL,NULL,NULL),(462,3,'SOAP Toolkit 3.0','','Microsoft SOAP Toolkit 3.0','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(463,230,'KMPlayer (remove only)','Бесплатное программное обеспечение. freeware','KMPlayer \\(remove only\\)','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(464,3,'Report Viewer Redistributable','','Microsoft Report Viewer Redistributable 20\\d+\\d+','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(465,3,'SQL Server Native Client','','Microsoft SQL Server Native Client','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(466,3,'Visual Studio Tools for Applications Runtime','','Microsoft Visual Studio Tools for Applications','Microsoft Visual Studio Tools for Applications \\d+\\.\\d+ Language Pack - RUS','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(467,3,'Базовый пакет поставщика службы криптографии смарт-карт (Microsoft)','','Базовый пакет поставщика службы криптографии смарт-карт \\(Microsoft\\)','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(468,3,'Windows 7 USB/DVD Download Tool','','Windows 7 USB.DVD Download Tool','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(469,231,'Partition Master 10','Есть бесплатная версия Partition Master Free(только для частного, некоммерческого, домашнего использования использование  в любой организации или в коммерческих целях строго запрещено), а так же платные Pro, Server. ','EaseUS Partition Master 10','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(470,231,'Partition Master 9 Home Edition','Есть бесплатная версия Partition Master Free(только для частного, некоммерческого, домашнего использования использование  в любой организации или в коммерческих целях строго запрещено), а так же платные Pro, Server. ','EASEUS Partition Master 9.* Home Edition','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(471,232,'Лидер-ЭнергоПроект','','Лидер-ЭнергоПроект','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(472,233,'SCAD Office 11','Платный','SCAD Office 11.*\nSCAD 11.*','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(473,227,'','','PDF Creator','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(474,234,'Bizagi Modeler','','Bizagi Modeler','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(475,235,'PC Health Monitor','','TOSHIBA PC Health Monitor','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(476,236,'CNC Syntax Editor Free Edition','CNC Syntax Editor Free Edition is free of charge for personal use. FREE EDITION Version. The free edition version of this software may be used for your purposes at the user\'s own risk for a unlimited period.','CNC Syntax Editor Free Edition','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(477,235,'Wireless Display Monitor','','TOSHIBA Wireless Display Monitor','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(478,235,'Bluetooth Stack for Windows by Toshiba','','Bluetooth Stack for Windows by Toshiba','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(479,235,'Web Camera Application','','TOSHIBA Web Camera Application','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(480,226,'Opera Stable','Бесплатно','Opera Stable \\d+\\.\\d+\\.\\d+\\d+\\.\\d+','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(481,235,'Service Station','','TOSHIBA Service Station','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(482,235,'Media Controller Plug-in','','TOSHIBA Media Controller Plug-in','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(483,235,'Media Controller','','TOSHIBA Media Controller','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(484,235,'Recovery Media Creator','','TOSHIBA Recovery Media Creator','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(485,235,'Sleep Utility','','TOSHIBA Sleep Utility','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(486,235,'Resolution+ Plug-in for Windows Media Player','','TOSHIBA Resolution+ Plug-in for Windows Media Player','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(487,70,'Элементы Яндекса 8.0 для Internet Explorer','','Элементы Яндекса 8.0 для Internet Explorer','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(488,3,'Bing Bar','','Bing Bar','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(489,237,'ICQ','','ICQ \\(версия \\d+\\.\\d+\\..*\\)$\nICQ\\d+','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(490,70,'Кнопка \"Яндекс\" на панели задач','','Кнопка \"Яндекс\" на панели задач','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(491,238,'CutePDF Writer','FREE software for commercial and non-commercial use!  No watermarks!  No Popup Web Ads! CutePDF Writer is the free version of commercial PDF converter software.','CutePDF Writer \\d+\\.\\d+','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(492,81,'Служба автоматического обновления программ','','Служба автоматического обновления программ','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(493,239,'ImgBurn','First and foremost, ImgBurn is a freeware tool. You cannot bundle it with your own commercial application and you cannot sell it in any way, shape or form. As an individual, you\'re allowed to use it anywhere you like - be it at home or at work.','ImgBurn','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(494,81,'Амиго','что-то вроде вируса:)','Амиго','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(495,101,'2014 Document Manager API','The SolidWorks Document Manager API requires a license key that is only available via the SolidWorks customer portal to SolidWorks customers who are currently under subscription.','SolidWorks 2014 Document Manager API','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(497,108,'pdfsam','ПО \"PDFsam Basic\" - free, open source. Но идет с платным модулем(снять галку) PDFsam Enhanced. ПО \"PDFsam Visual\" - платная.','pdfsam','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(498,101,'eDrawings 2014','Программа просмотра eDrawings Viewer, доступная в бесплатной и коммерческой версии (eDrawings Professional).','eDrawings 2014','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(499,11,'Acrobat DC','','Adobe Acrobat DC','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(500,3,'Skype для бизнеса базовый 2016','','Skype для бизнеса базовый 2016','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(501,3,'Visio 2010 Standard','','Microsoft Visio стандартный 2010','','2018-09-18 15:17:03',NULL,NULL,NULL,NULL),(502,241,'pdfforge Toolbar','потенциально нежелательная программа, бесплатная','pdfforge Toolbar v\\d+\\.\\d+(\\.\\d+)?','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(503,82,'Window Moccof','','Window Moccof, версия \\d+\\.\\d+','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(504,82,'Power supply radar','','Power supply radar, версия \\d+\\.\\d+','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(505,72,'NPort Search Utility','Идет в месте с железкой','NPort Search Utility','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(506,242,'BlueStacks App Player','Лицензия Freeware. The software\'s basic features are free to download and use. Advanced optional features require a paid monthly subscription.','BlueStacks App Player','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(507,243,'U90 Ladder','к железке. U90Ladder (the \"Program\") is licensed, not sold, by Unitronics (R\"G) (1989) Ltd. (\"Unitronics\") pursuant to the terms and conditions of this Software License Agreement.Under this license one person or legal entity may use the Program.','Unitronics U90 Ladder','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(508,244,'Very Sleepy CS','Very Sleepy is a free C/C++ CPU profiler for Windows systems. Very Sleepy is released under the GNU Public License, so you\'re guaranteed the right to the source code and to change it how you wish.','Very Sleepy CS version \\d+\\.\\d+','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(509,245,'Пакет драйверов Windows ','','Пакет драйверов Windows - ARM Ltd','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(510,103,'Пакет драйверов Windows','','Пакет драйверов Windows - Altera \\(WinUSB\\)','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(511,245,'ARM DS-5','Может ставится в месте с quartus или отдельно. DS-5 Community This free edition of DS-5. Платные версии - \"Professional\" и \"Ultimate\". ','ARM DS-5 v5','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(512,103,'SDK for OpenCL *','','Altera SDK for OpenCL \\d+\\.\\d+\\.\\d+\\.\\d{3}','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(514,103,'ModelSim-Altera Starter Edition 15','ModelSim*-Intel® FPGA Starter Edition Software - Free no license required','ModelSim-Altera Starter Edition 15','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(515,103,'ModelSim-Altera Starter Edition 16','ModelSim*-Intel® FPGA Starter Edition Software - Free no license required','ModelSim-Altera Starter Edition 16','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(517,103,'Quartus Prime Programmer and Tools 16','Additional Software for Quartus Prime Standard Edition','Quartus Prime Programmer and Tools 16','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(519,103,'Quartus Prime Standard Edition 16','','Quartus Prime Standard Edition 16','ModelSim-Altera Starter Edition 16','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(520,103,'SoC Embedded Design Suite (EDS) 15','Платное ПО. Среда SoC EDS доступна как в бесплатном варианте с ограничениями (Web Edition), так и в полном варианте (Subscription Edition)','SoC Embedded Design Suite \\(EDS\\) 15','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(521,246,'4D VIEW','','4D VIEW','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(522,247,'HTC Home *','HTC Home 3 is a free set of widgets for Windows like on HTC Smartphones','HTC Home \\d+\\.\\d+','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(523,248,'Vardex TM CNC Generator Version 12.0.6','Не понятно, свободно скачивается, про лицензию нет упоминания','Vardex TM CNC Generator Version 12.0.6','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(524,248,'Vardex TT Generator 5.0.7','','Vardex TT Generator 5.0.7','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(525,4,'MySQL Installer for Windows - Community','','MySQL Installer for Windows - Community','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(526,3,'Skype Toolbars','','Skype Toolbars','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(527,249,'IntelliJ IDEA Community Edition','Community Edition - Open-source, Apache 2.0. Интегрированная среда разработки программного обеспечения для многих языков программирования (ideaIU - платное, ideaIC - бесплатное. Значение из build.txt). Ultimate - платная версия. Community -бесплатная.','IntelliJ IDEA Community Edition \\d{4}\\.\\d+','','2023-09-05 05:39:25','reviakin.a',NULL,NULL,NULL),(528,250,'Blender','Open Source 3D creation. Free to use for any purpose, forever. www.blender.org','Blender \\(remove only\\)','','2023-09-05 05:39:25',NULL,NULL,NULL,NULL),(529,71,'Ferma Calculation','Данное приложение распространяется по принципу as is, на условиях свободного, не ограниченного по времени использования (Freeware). ','Ferma Calculation \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(530,251,'Pamela Basic 4.8','Версия \"Basic\" FREEWARE','Pamela Basic 4.8','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(531,125,'PC Wizard 2012','','PC Wizard 2012','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(532,252,'Win32DiskImager version 1.0.0','Бесплатная и легкая в эксплуатации утилита, которая позволяет создавать точные копии USB флешек и карт памяти SD, сохранять их в виде IMG файлов, а также записывать эти образы обратно на съемные носители.','Win32DiskImager version 1.0.0','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(533,253,'Предприятие 8.2','','1C:Предприятие 8.2','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(534,96,'/InterBase(r) ODBC driver','Open Source Licensing  The driver source is available under the Initial Developers Public Licence (IDPL), a variant of the InterBase Public Licence (IPL)','Firebird\\/InterBase\\(r\\) ODBC driver \\d+\\.\\d+','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(535,254,'8GadgetPack','Free','8GadgetPack','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(536,255,'OpenOffice','','OpenOffice','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(537,256,'PDFsam Basic','Бесплатное. Cнять галку с платного ПО PDFsam Enhanced','PDFsam Basic','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(538,257,'EasyNetMonitor 2.70','EasyNetMonitor — бесплатная, простая и удобная программа для определения работоспособности компьютеров, принтеров, сайтов в сети. Платные версии EasyNetMonitor PRO, EasyNetMonitor SE','EasyNetMonitor 2.70','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(539,258,'Context 7.0','','Context 7.0','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(540,160,'AutoCAD 2010','','AutoCAD 2010 - Русский','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(541,259,'LP Viewer V2010','','LP Viewer V2010','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(542,260,'CorelDRAW Graphics Suite X5','','CorelDRAW\\(R\\) Graphics Suite X5','Corel Graphics - Windows Shell Extension\nCorelDRAW Graphics Suite X5 - Extra Content','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(543,16,'Wireless LAN Driver and Utility','','REALTEK Wireless LAN Driver and Utility','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(544,152,'Пакет драйверов Windows - Texas Instruments CDM Driver Package (**/**/20* *.**.**)','','Пакет драйверов Windows - Texas Instruments CDM Driver Package \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\.\\d+\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(545,152,'Пакет драйверов Windows - Texas Instruments, Inc. (WinUSB) StellarisICDIDeviceClass  (**/**/**** *.*.****)','','Пакет драйверов Windows - Texas Instruments, Inc\\. \\(WinUSB\\) StellarisICDIDeviceClass  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\.\\d{4}\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(546,261,'Пакет драйверов Windows - Texas Instruments Inc.','','Пакет драйверов Windows - Texas Instruments Inc\nTI USB 3.0 Host Controller Driver','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(547,179,'Пакет драйверов Windows','','Пакет драйверов Windows - IAR','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(548,262,'Windows Driver Package - STMicroelectronics (stice 64bits) USB  (11/15/2013 2.1)','','Windows Driver Package - STMicroelectronics \\(stice 64bits\\) USB  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(549,262,'Windows Driver Package - STMicroelectronics (usbser) Ports  (*/*/**** *.*)','','Windows Driver Package - STMicroelectronics \\(usbser\\) Ports  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(550,263,'Пакет драйверов Windows - Segger (jlink) USB  (*)','','Пакет драйверов Windows - Segger \\(jlink\\) USB  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\.\\d+\\.\\d+\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(551,171,'CCleaner','','CCleaner','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(552,154,'Mobile Device Support','','Apple Mobile Device Support','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(553,262,'Windows Driver Package - STMicroelectronics USBDevice  (12/05/2012 13.54.20.543)','','Windows Driver Package - STMicroelectronics USBDevice  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\.\\d+\\.\\d{3}\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(554,262,'Windows Driver Package - STMicroelectronics USBDevice  (*)','','Windows Driver Package - STMicroelectronics USBDevice  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\.\\d+\\.\\d{3}\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(555,152,'Пакет драйверов Windows - Texas Instruments, Inc. (usbser) Ports  (*)','','Пакет драйверов Windows - Texas Instruments, Inc\\. \\(usbser\\) Ports  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\.\\d{4}\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(556,262,'Windows Driver Package - STMicroelectronics (WinUSB) STLinkWinUSB  (*)','','Windows Driver Package - STMicroelectronics \\(WinUSB\\) STLinkWinUSB  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(557,264,'CamStudio','You can download and use it completely free - yep - completely 100% free for your personal and commercial projects as CamStudio and the Codec are released under the GPL','CamStudio','CamStudio Lossless Codec v\\d+\\.\\d+','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(558,265,'Driver Files','','Atmel Driver Files\nAtmel LibUSB0 Driver\nAtmel Segger USB Drivers\nAtmel WinDriver\nAtmel WinUSB','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(559,265,'Studio 7','Основанная на Visual Studio бесплатная проприетарная интегрированная среда разработки (IDE)','Atmel Studio 7.0','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(560,3,'Document Explorer','','Microsoft Document Explorer \\d{4}','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(561,266,'DXF Viewer','DXF Viewer is a free viewer for DXF files','DXF Viewer','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(562,60,'Qt Eclipse Integration','','Qt Eclipse Integration','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(563,267,'Пакет драйверов Windows - libusb-win32 (libusb0) libusb-win32 devices  (*)','','Пакет драйверов Windows - libusb-win32 \\(libusb0\\) libusb-win32 devices  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\.\\d+\\.\\d+\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(564,263,'Пакет драйверов Windows - SEGGER (JLinkCDC_x64) Ports  (*)','','Пакет драйверов Windows - SEGGER \\(JLinkCDC_x64\\) Ports  \\(\\d+\\/\\d+\\/\\d{4} \\d+\\.\\d+\\.\\d{4}\\.\\d+\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(565,16,'TP-LINK USB Ethernet Adapter Driver','','TP-LINK USB Ethernet Adapter Driver','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(566,268,'DriverToolkit','','DriverToolkit version \\d+.\\d+.\\d+.\\d+','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(567,269,'LTspice XVII','Платное ПО. Бессрочное право оценивать продукты LTC. This software is copyrighted.  You are granted a non-exclusive, non-transferable, non-sublicenseable, royalty-free right to evaluate LTC products and also to perform general circuit simulation. ','LTspice XVII','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(568,21,'Quartus Prime Standard Edition 17','','Quartus Prime Standard Edition 17','Quartus Prime Programmer and Tools 17\nSoC Embedded Design Suite \\(EDS\\) 17','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(569,270,'USBPcap','USBPcap is an open-source USB sniffer for Windows.','USBPcap \\d+\\.\\d+\\.\\d+.\\d+','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(570,152,'XDCtools 3.23.03.53','Бесплатное ПО','XDCtools \\d+\\.\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(571,152,'Code Composer Studio v5','With the release of CCSv7 all previous v4, v5 and v6 releases are free of charge.','Code Composer Studio v5','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(573,272,'Emulation Device Drivers for Windows','Starting with Code Composer Ctudio v4, Blackhawk support (device drivers and updates) has been include with the TI distribution media and download.','Blackhawk Emulation Device Drivers for Windows','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(575,3,'OneDrive','','Microsoft OneDrive','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(576,273,'Avast Free Antivirus','Free in business. www.avast.com/eula','Avast Free Antivirus','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(577,263,'J-Link V610n','Free','J-Link V610n','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(578,262,'STM32CubeMX','Бесплатное ПО','STM32CubeMX','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(579,274,'GNU Tools for ARM Embedded Processors','Бесплатное ПО','GNU Tools for ARM Embedded Processors','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(580,275,'GNU ARM Eclipse Build Tools',' GNU GENERAL PUBLIC LICENSE GPLv3 GPLv2','GNU ARM Eclipse Build Tools','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(581,245,'Keil µVision4','Платное ПО, но есть бесплатная ограниченная лицензия. Keil development development tools without a current product license run as a Lite/Evaluation edition and have the following restrictions: http://www.keil.com/demo/limits.asp','Keil µVision4','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(582,3,'Hotfix KB','','\\(KB\\d{6,9}\\)','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(583,276,'B1 Free Archiver','Бесплатное ПО. Архиватор','B1 Free Archiver','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(584,277,'InfraRecorder','Free. InfraRecorder is an open source CD and DVD writing program for Microsoft Windows.','InfraRecorder','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(585,3,'Windows Desktop Search','','Windows Desktop Search','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(586,278,'BurnRights','входит в  sine Nero 10 и выше','Nero BurnRights','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(587,154,'Поддержка программ Apple','','Поддержка программ Apple','','2023-09-05 05:39:26','reviakin.a',NULL,NULL,NULL),(588,128,'Microsoft redistributable runtime DLLs VS**** SP1 (x**)','','Microsoft redistributable runtime DLLs VS\\d{4} SP1 \\(x\\d{2}\\)','','2023-09-05 05:39:26',NULL,NULL,NULL,NULL),(589,128,'Microsoft redistributable runtime DLLs VS2008 SP1(x86)','','Microsoft redistributable runtime DLLs VS\\d{4} SP1\\(x\\d{2}\\)','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(590,128,'Microsoft redistributable runtime DLLs VS20** SP1(x**)','','Microsoft redistributable runtime DLLs VS\\d{4} SP1\\(x\\d{2}\\)','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(591,3,'User-Mode Driver Framework Feature Pack','','Microsoft User-Mode Driver Framework Feature Pack \\d+\\.\\d+','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(592,128,'Setup Automatic Workstation Update Service','','SAPSetup Automatic Workstation Update Service','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(593,279,'xp-AntiSpy','Бесплатное ПО','xp-AntiSpy \\d+\\.\\d+-\\d+','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(594,280,'Avant Browser','License: Freeware','Avant Browser','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(595,281,'Ogg Codecs 0.81.15562','Free','Ogg Codecs \\d+\\.\\d+\\.\\d{5}','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(596,282,'QIP 2005 8092','','QIP 20\\d+ \\d{4}','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(597,16,'Diagnostics Utility','','Diagnostics Utility','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(598,283,'USBscope50','Программа к железке USB-SCOPE50','USBscope50','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(599,284,'XP Tweaker 1.50','Бесплатное ПО','XP Tweaker 1\\.50','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(600,285,'softMCCS','Free','softMCCS','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(601,286,'RiDoc 4.3.7.1','Платное ПО. Используйте RiDoc 30 дней БЕСПЛАТНО в режиме полного функционала (без ограничений) для ознакомления возможностей программы*.','RiDoc 4\\.3\\.7\\.1','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(603,287,'Flash Magic','You may install and use an unlimited number of copies of Flash Magic. Flash Magic is provided free as a DEVELOPMENT TOOL ONLY. It is NOT LICENSED FOR PRODUCTION, TESTING OR IN FIELD USE.  ','Flash Magic \\d+\\.\\d+','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(604,288,'MPLAB C18 v3.22 Student Edition','если вы загрузите «Студенческое издание» Программного обеспечения из Интернета, вы можете устанавливать и использовать такую ​​версию Программного обеспечения на неограниченном количестве компьютеры для коммерческого или образовательного использования.','MPLAB C18 v\\d+\\.\\d+ Student Edition','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(605,288,'MPLAB Tools','Бесплатное','MPLAB Tools','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(606,289,'Phone F USB Driver','','Phone F USB Driver','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(607,290,'RigExpert VHF Antenna Analyzer','Бесплатное ПО','RigExpert VHF Antenna Analyzer','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(608,291,'Kingston SSD Manager version 1.1.0.5','','Kingston SSD Manager version \\d+\\.\\d+\\.\\d+.\\d+','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(609,103,'SoC Embedded Design Suite (EDS) 16','Additional Software for Quartus Prime Standard Edition','SoC Embedded Design Suite \\(EDS\\) 16','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(610,292,'ChipProgLPT Programmer 5','Идет в месте с железкой','Phyton ChipProgLPT Programmer 5','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(611,288,'HI-TECH PICC-18 Compiler v8.20PL3','Платное ПО. Компилятор, больше не поддерживается. Есть  DEMO версия','HI-TECH PICC-18 Compiler v8.20PL3','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(612,120,'Sentinel System Driver','','Sentinel System Driver','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(613,293,'RivaTuner','License Freeware','RivaTuner v\\d+\\.\\d+','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(614,294,'TrafficCompressor','Лицензия Демо-режим, Shareware','TrafficCompressor','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(615,295,'Trojan Remover 6.8.2','Платное ПО. simplysup.com. You can download a free fully-working evaluation copy of Trojan Remover by clicking on one of the download links below. The program will work for a full 30 days before you need to either register or uninstall it.','Trojan Remover \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(616,179,'PIC18 Toolsuite Plug-in','','MPLAB IAR PIC18 Toolsuite Plug-in V\\d+\\.\\d+A','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(617,296,'АвтоГРАФ v.2.17.3','Платное ПО','АвтоГРАФ v.2.17.3','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(618,297,'HiJackThis','бесплатный инструмент с открытым исходным кодом','HiJackThis','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(619,298,'player','3DVIA Player - бесплатный плагин, который воспроизводит 3D-игры и отображает 3D-модели.','3DVIA player \\d+\\.\\d+','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(620,299,'PI Expert Suite v.*.*','Бесплатное','PI Expert Suite v.\\d+\\.\\d+','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(621,3,'MSXML 6','','MSXML 6.0 Parser\nMSXML 6 Service Pack 2','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(622,179,'Embedded Workbench Evaluation for PIC18','Платное ПО','IAR Embedded Workbench Evaluation for PIC18','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(623,300,'UT60E Interface Program_Ver 2.02','UNI-T UT61E data logging programs. Вроде как идет на диске (в инете доступна) в месте с UT61E multimeter','UT60E Interface Program_Ver 2.02','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(624,35,'Support Tools','Всякий предустановленный хлам','HP Solution Center\nHP Client Security Manager\nHP Connection Optimizer\nHP Documentation\nHP ePrint SW\nHP Hotkey Support\nHP JumpStart\nHP JumpStart\nHP MAC Address Manager\nHP Notifications\nHP Support Solutions\nShop for HP Supplies','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(625,35,'Photosmart','','Photosmart','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(626,301,'DSEcovery Tool','Платная. Fully Functional for 30 Days','DSEcovery Tool','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(627,120,'Sentinel HASP Run-time','','Sentinel HASP Run-time','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(628,21,'Matrix Storage Manager','','Intel® Matrix Storage Manager','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(629,55,'EPU-4 Engine','','EPU-4 Engine','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(630,302,'MiKTeX 2','open source','MiKTeX 2.*','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(631,303,'MotionStudio','Покупали двигатель. Идет скорее всего в месте с двигателем','MotionStudio','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(632,180,'LEAP Module','','Cisco LEAP Module','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(633,65,'Python 3','Python Software Foundation License - свободное','Python 3.*','Python Launcher','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(634,304,'- USB Protocol Analyzer','This is a fully functional trial versions of USBlyzer. You can evaluate it free of charge for 33 days.','USBlyzer - USB Protocol Analyzer','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(635,180,'EAP-FAST Module','','Cisco EAP-FAST Module','','2023-09-05 05:39:27','reviakin.a',NULL,NULL,NULL),(636,3,'Project 2016 Professional','','Microsoft Project профессиональный 2016','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(637,70,'Элементы Яндекса для Internet Explorer','','Элементы Яндекса [0-9.]* для Internet Explorer','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(638,263,'J-Link V***d','Может быть загружен и использован бесплатно любым владельцем модели SEGGER J-Link. Использование  для работы с клонами J-Link запрещено и незаконно','J-Link V\\d{3}\\w','SystemView V242','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(639,305,'Tftpd64 Standalone Edition (remove only)','Tftpd32 and Tftpd64 are released under the European Union Public License','Tftpd64 Standalone Edition \\(remove only\\)','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(640,262,'STLinkDriver','','STLinkDriver','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(641,306,'JMicron JMB36X Driver','','JMicron JMB36X Driver','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(642,178,'BitTorrent','','BitTorrent','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(643,307,'Qt','Для работы над проектом с использованием Qt каждый разработчик должен обладать лицензией. Она именная и привязывается к e-mail адресу (Qt Account). \r\nЕсли команда большая, то есть возможность приобрести “массовую” (site license) лицензию на всю компанию','Qt','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(644,96,'MSI to redistribute MS VS2005 CRT libraries','','MSI to redistribute MS VS2005 CRT libraries','','2023-09-05 05:39:27',NULL,NULL,NULL,NULL),(645,180,'PEAP Module','','Cisco PEAP Module','','2023-09-05 05:39:28','reviakin.a',NULL,NULL,NULL),(646,262,'STM32 ST-LINK Utility','Бесплатно программное обеспечение. STM32 ST-LINK Utility (STSW-LINK004) - полнофункциональный программный интерфейс для программирования микроконтроллеров STM32.','STM32 ST-LINK Utility','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(647,308,'1','Open source Geographic Information System (GIS)','OpenJUMP 1.*','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(648,309,'Editor','','Planoplan Editor','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(649,310,'XnView','Бесплатно для некоммерческого','XnView','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(650,281,'Open Codecs 0.85.17777','Бесплатное ПО','Xiph.Org Open Codecs \\d+\\.\\d+\\.\\d{5}','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(651,311,'CodeMeter Runtime Kit ','Поставляется вместе с защищаемым программным обеспечением','CodeMeter Runtime Kit .*','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(652,312,'Tools RUS','Обработка и уравнивание наблюдений, полученных различными геодезическими приборами. Платная','Topcon Tools RUS','Topcon Link v.*','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(653,169,'Sound Organizer','Аудиоприложение для импорта, воспроизведения и редактирования файлов, записанных с помощью цифрового диктофона Sony','Sound Organizer','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(654,313,'Directshow Filters','Бесплатные инструменты с открытым исходным кодом','WebM Project Directshow Filters','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(655,314,'LEICA Geo Office','Платная','LEICA Geo Office','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(656,192,'nanoCAD Электро x64 5','','nanoCAD Электро x64 5.*','nanoCAD Электро. Редактор БД .*','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(657,315,'POV-Ray for Windows v3','Бесплатное программное обеспечение','POV-Ray for Windows v3[0-9.]*$','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(658,316,'ImagePrinter 2.0.1','Обычная версия под лицензией GNU (GPL). Версия Pro - Платное ПО, если вы используете это ПО после периода оценки (30 дней) требуется регистрационный сбор.','ImagePrinter 2.0.1','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(659,55,'Smart Doctor','','ASUS Smart Doctor','','2023-09-05 05:39:28','reviakin.a',NULL,NULL,NULL),(660,317,'Агент администрирования Лаборатории Касперского','','Агент администрирования Лаборатории Касперского','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(661,318,'CDR Viewer','Free for use software tool','CDR Viewer','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(662,319,'PonyProg2000 v2.07c','published by the Free Software Foundation','PonyProg2000 v\\d+\\.\\d+c','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(663,55,'Gamer OSD','','ASUS Gamer OSD','','2023-09-05 05:39:28','reviakin.a',NULL,NULL,NULL),(664,320,'Ethernet Utility','','Atheros Ethernet Utility\nAtheros Communications.* Ethernet Driver','','2023-09-05 05:39:28','reviakin.a',NULL,NULL,NULL),(665,321,'Bluesoleil2.6.0.8 Release 070517','License Shareware. Various Bluetooth dongles are delivered with an obsolete or demonstration version of Bluesoleil. New versions are available as a standalone purchase from the vendor\'s website. ','Bluesoleil\\d+\\.\\d+\\.\\d+.\\d+ Release \\d{6}','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(666,192,'NormaCS 3.0 Demo Client','','Nanosoft NormaCS 3.0 Demo Client','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(667,1,'Gaming Software','','Logitech Gaming Software .*','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(668,3,'Элемент управления Windows Live Mesh ActiveX для удаленных подключений','','Элемент управления Windows Live Mesh ActiveX для удаленных подключений','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(669,60,'Пакет драйверов Windows','','Пакет драйверов Windows - Nokia','','2023-09-05 05:39:28','reviakin.a',NULL,NULL,NULL),(670,322,'ATI Problem Report Wizard','','ATI Problem Report Wizard','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(671,2,'HydraVision','','HydraVision','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(672,323,'Evernote','EVERNOTE BASIC - бесплатна. EVERNOTE PREMIUM, EVERNOTE BUSINESS - платные версии','Evernote v.','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(673,324,'','Платное ПО. WinImage is shareware. You may evaluate it for a period of 30 days. After 30 days, you need to register it if you intend to continue using WinImage.','WinImage','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(674,325,'Prism Video File Converter','Платное ПО. Non-commercial home use only','Prism Video File Converter','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(675,326,'PhotoLine 16.5.0.0','Платное ПО. You are allowed to use this version for 30 days for testing. There are few little limitations in the testing time','PhotoLine 16.5.0.0','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(677,3,'Windows SDK AddOn','','Windows SDK AddOn','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(678,327,'OpenSSH for Windows (remove only)','','OpenSSH for Windows \\(remove only\\)','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(679,3,'Visual Studio 2017','Бесплатна Community Edition и Express. См. условия лицензии программного обеспечения Microsoft Visual Studio 2017 («программное обеспечение»), так как они регулируют использование этого дополнительного компонента. Продолжительность пробного периода — 30 д','Microsoft Visual Studio 2017','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(680,328,'Sublime Text Build 3126','\"Sublime Text может быть загружен и оценен бесплатно, однако лицензия должна быть приобретена для дальнейшего использования. В настоящее время нет установленного срока для оценки\"!','Sublime Text Build 3126','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(681,329,'EZ-USB FX3 SDK','ПО к usb. If you are using Cypress FX3 family device in the design, you can use the FX3 SDK and other software tools provided by Cypress free of cost. It is prohibited to use FX3 SDK and any other Cypress-provided software tools for non-Cypress produc','EZ-USB FX3 SDK','','2023-09-05 05:39:28','reviakin.a',NULL,NULL,NULL),(682,21,'Integrated Performance Primitives 2018','License Proprietary, freeware. Intel Integrated Performance Primitives (Intel IPP) is a multi-threaded software library of functions for multimedia and data processing applications. It is available separately or as a part of Intel Parallel Studio','Intel Integrated Performance Primitives 2018','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(683,3,'Windows Software Development Kit - Windows *','Windows SDKs are available for free; they were once available on Microsoft Download Center but were moved to MSDN in 2012','Windows Software Development Kit - Windows .*','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(684,160,'EAGLE 8.5.2','EAGLE FREE - бесплатна (Limited PCB design software for hobbyists and makers). EAGLE Standard, EAGLE Premium - платные версии.','EAGLE 8.5.2','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(685,330,'SketchUp 2016','SketchUp 2016 старые версии есть в версии \"free\", но non-commercial use only. Последняя free версия SketchUp 2018, не устанавливается, а запускается через браузер.','SketchUp 2016','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(686,152,'CCSv4','4,5,6 версии бесплатны. With the release of CCSv7 all previous v4, v5 and v6 releases are free of charge.','5.42.02.10 CCSv4','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(687,152,'LM Flash Programmer','LM Flash Programmer is a free flash programming utility intended to be used with Texas Instruments Tiva™ C Series and Stellaris® microcontrollers development boards, or evaluation boards.','LM Flash Programmer','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(688,265,'AVR Studio','Atmel Studio (ранее AVR Studio)  основанная на Visual Studio бесплатная проприетарная интегрированная среда разработки (IDE) для разработки приложений для микроконтроллеров семейства AVR и микроконтроллеров семейства ARM от компании Atmel','AVR Studio\nAVRStudio4','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(689,331,'CoIDE_V2Beta','CooCox CoIDE, a free and highly-integrated software development environment for ARM Cortex MCUs','CoIDE_V2Beta','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(690,332,'Hard Disk Low Level Format Tool','Free for personal/home use(ограничение скорости), платная для коммерческого использования','Hard Disk Low Level Format Tool','','2023-09-05 05:39:28','reviakin.a',NULL,NULL,NULL),(691,333,'Hetman Partition Recovery','Hetman Partition Recovery – условно-бесплатная программа. В пробной версии программы вы не можете восстанавливать файлы, вы можете только находить их.','Hetman Partition Recovery','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(692,334,'KDevelop','KDevelop is a free and open-source integrated development environment for Unix-like computer operating systems and Microsoft Windows.','KDevelop','','2023-09-05 05:39:28','reviakin.a',NULL,NULL,NULL),(693,335,'','GNU General Public License — лицензия на свободное программное обеспечение','qutIM \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:28',NULL,NULL,NULL,NULL),(694,336,'SDCC','The Small Device C Compiler (SDCC) is a free-software, partially retargetable[1] C compiler for microcontrollers. It is distributed under the GNU General Public License.','SDCC','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(695,337,'WinTopo','Freeware','WinTopo','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(696,338,'','Free','Image Resizer','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(697,339,'SX1272 LoRa calculator','Возможно к железке Semtech SX1272 Дальний радиочастотный приемопередатчик.','SX1272 LoRa calculator','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(698,3,'SQL Server 2005','','Microsoft SQL Server 2005','Microsoft Visual Basic PowerPacks 10.0\nMicrosoft SQL Server VSS Writer','2023-08-30 13:51:47',NULL,NULL,NULL,NULL),(699,340,'mini dB-Calculator 1','Бесплатный','mini dB-Calculator 1.*','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(700,55,'Пакет драйверов Windows','','Пакет драйверов Windows - ASUSTeK','','2023-09-05 05:39:29','reviakin.a',NULL,NULL,NULL),(701,341,'SSD Toolbox','','Kingston SSD Toolbox .*','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(702,342,'Trillian','','Trillian','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(703,343,'WinAVR 20100110 (remove only)','WinAVRTM (pronounced \"whenever\") is a suite of executable, open source software development tools for the Atmel AVR series of RISC microprocessors hosted on the Windows platform.','WinAVR 20100110 \\(remove only\\)','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(704,303,'MOVITOOLS®','Customers receive SEW-EURODRIVE software free of charge and as  a special service.','MOVITOOLS® \\d+\\.\\d+','PLCEditorGatewayServer','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(705,344,'SEW-Communication-Server','Customers receive SEW-EURODRIVE software free of charge and as  a special service. ','SEW-Communication-Server','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(706,345,'PLCEditorGatewayServer','PLC Editor входит в  MOVITOOLS MotionStudio','PLCEditorGatewayServer','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(707,262,'FlashLoader Demonstrator *.*.*','he STM32 Flash loader demonstrator (FLASHER-STM32) is a free software PC utility from STMicroelectronics, which runs on Microsoft® OSs and communicates through the RS232 with the STM32 system memory bootloader.','FlashLoader Demonstrator \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(708,346,'ST Toolset','Free. Большинство компонентов предоставляются на основе \'GNU General Public License\'.','ST Toolset','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(709,265,'AVR Jungo USB','драйвера','AVR Jungo USB','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(710,152,'bios 6_53_02_00','Бесплатно. Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:...','bios 6_53_02_00','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(711,347,'CoreUtils','Бесплатное ПО','GnuWin32: CoreUtils','','2023-09-05 05:39:29','reviakin.a',NULL,NULL,NULL),(712,347,'Make','Бесплатное ПО. Make: GNU make utility to maintain groups of programs','GnuWin32: Make-\\d+\\.\\d+','','2023-09-05 05:39:29','reviakin.a',NULL,NULL,NULL),(714,128,'Business Explorer','','SAP Business Explorer','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(715,349,'Captura','Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the \"Software\"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify...','Captura v\\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(716,55,'TurboV','','TurboV','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(717,3,'Комплект средств для развертывания и оценки Windows for Windows 8.1','','Комплект средств для развертывания и оценки Windows for Windows 8.1','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(718,3,'Server Speech Platform Runtime','','Microsoft Server Speech Platform Runtime','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(719,3,'Server Speech Text to Speech Voice','Язык для движка произношения текста','Microsoft Server Speech Text to Speech Voice','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(720,3,'SQL Server Browser for SQL Server 2012','','SQL Server Browser for SQL Server 2012','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(721,350,'Nmap','The Npcap License allows end users to download, install, and use Npcap from our site for free.','Nmap \\d+\\.\\d+','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(722,18,'Teamcenter Applications for Microsoft Office','','Teamcenter Applications for Microsoft Office','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(724,352,'MMANA-GAL Basic, версия 3','Базовая версия MMANA-GAL бесплатна и свободна только для некоммерческого использования (например, радиолюбительских и приватных целей). Для любых других применений должна использоваться лицензированная MMANA-GAL RPO.','MMANA-GAL_Basic, версия 3','','2023-09-05 05:39:29','reviakin.a',NULL,NULL,NULL),(725,3,'Office Live Meeting 2007','','Microsoft Office Live Meeting 2007','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(726,283,'USBwave12','ПО для железки USB-WAVE12 Elan 12.5MHz USB pen-style Function Generator','USBwave12','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(727,353,'FlashGet','FlashGet - бесплатная программа без рекламного ПО или программ-шпионов.','FlashGet \\d+\\.\\d+\\.\\d+\\.\\d{4}','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(728,354,'Moleskinsoft Clone Remover 3.8','Незарегистрированная пробная версия Remover Clone Remover может свободно распространятся. Лицензия: shareware. условно-бесплатная программа','Moleskinsoft Clone Remover 3.8','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(729,355,'Карта Москвы MosMap v. 3.1 Lite ','','Карта Москвы MosMap v. 3.1 Lite','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(730,59,'AIMP2','Вы имеете право устанавливать и использовать AIMP как на домашних компьютерах, так и на компьютерах в организациях любой формы собственности, в том числе в государственных и муниципальных учреждениях.','AIMP2','','2023-09-05 05:39:29','reviakin.a',NULL,NULL,NULL),(731,356,'Auslogics Duplicate File Finder','Эта программа абсолютно бесплатна без ограничений по срокам для домашнего или коммерческого использования. ','Auslogics Duplicate File Finder','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(732,356,'AusLogics Disk Defrag','','AusLogics Disk Defrag','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(733,357,'WIDCOMM Bluetooth Software','WIDCOMM Bluetooth Software - бесплатное приложение Bluetooth','WIDCOMM Bluetooth Software','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(734,100,'Программа Spu_orb (remove only)','','QIP Infium 2.0.9018 RC3\nПрограмма Spu_orb (remove only)','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(736,359,'DSO-2090 USB(V*.*.*.*)','Драйвер к железке - цифровой осциллограф','DSO-2090 USB\\(V\\d+\\.\\d+\\.\\d+.\\d+\\)','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(737,292,'ChipProgLPT Programmer 4','','Phyton ChipProgLPT Programmer 4','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(738,292,'ChipProgUSB Programmer 5','ПО для железки, идет с железкой','Phyton ChipProgUSB Programmer 5','','2023-09-05 05:39:29',NULL,NULL,NULL,NULL),(739,360,'Интернет-Клиент','','ОАО Банк ВТБ Интернет-Клиент','','2023-09-05 05:39:29','reviakin.a',NULL,NULL,NULL),(740,361,'PhonerLite','программный телефон','PhonerLite','','2023-09-05 05:39:29','reviakin.a',NULL,NULL,NULL),(741,362,'Lightshot-5.4.0.35','','Lightshot-5.4.0.35','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(742,362,'Lightshot-5.4.0.10','','Lightshot-5.4.0.10','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(743,3,'DHTML Editing Component','','DHTML Editing Component','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(744,363,'Asmedia USB Host Controller Driver','','Asmedia USB Host Controller Driver','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(745,72,'UPort Windows Driver','','MOXA UPort 1110\\/1130\\/1150 Windows Driver\nMOXA UPort 1110\\/1130 Windows Driver','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(746,364,'Milestone XProtect Smart Client 2017 R2 (64-bit)','видеонаблюдение','Milestone XProtect Smart Client 2017 R2 (64-bit)','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(747,365,'NetWorx 5.5.5','','NetWorx 5.5.5','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(748,3,'Visual Studio 2015','','Microsoft Visual Studio 2015$','Microsoft Visual Studio 2015 Shell','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(749,2,'Software','Драйвера ','AMD Software','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(750,366,'MSI Afterburner ','','MSI Afterburner [0-9.]*','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(751,367,'HWiNFO64 ','','HWiNFO64 Version [0-9.]*','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(752,317,'Kaspersky Endpoint Security для Windows','','Kaspersky Endpoint Security для Windows\nKaspersky Endpoint Security for Windows','Расширение для Microsoft Exchange ActiveSync\nРасширение для iOS Mobile Device Management\nАгент администрирования Kaspersky Security Center\nKaspersky Endpoint Agent .*\nKaspersky Endpoint Security','2023-08-31 04:43:10','reviakin.a',NULL,NULL,NULL),(753,368,'Пакет драйверов Windows','','Пакет драйверов Windows','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(754,312,'MAGNET Office','Для камеральной обработки полевых геодезических измерений, полученных различными приборами','MAGNET Office v.[0-9.]*$','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(755,330,'SketchUp 8','Бесплатная для некоммерческого использования','SketchUp 8$','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(756,57,'КОМПАС-3D Viewer V15','','КОМПАС-3D Viewer V15','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(759,192,'nanoCAD СПДС 9.0 x64','','nanoCAD СПДС 9.0 x64','','2018-11-01 07:54:37',NULL,NULL,NULL,NULL),(760,369,'Python 3','Без ограничений в любых приложениях, включая проприетарные','Python 3[0-9.]* \\(Anaconda3 [0-9.]* 64-bit\\)','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(761,370,'IrfanView','Бесплатный просмотрщик изображений','IrfanView','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(762,9,'Client Integration Plug-in','','VMware Client Integration Plug-in \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(763,20,'IP Office Admin Suite','','IP Office Admin Suite','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(764,371,'UltraVnc','','UltraVnc','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(765,53,'PL2303 USB-to-Serial','Бесплатное ПО. PL2303 Windows Driver Download   USB to UART RS232 Serial','PL2303 USB-to-Serial','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(766,372,'7.2','','Альт-Инвест 7.2','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(767,3,'Windows 8 Professional','','Майкрософт Windows 8 Профессиональная','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(768,373,'Resistor Color Code','','Resistor Color Code','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(769,374,'','','Спутник','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(770,375,'Hex Editor Neo','Hex Editor Neo - Платная(Free Trial Versions), Free Hex Editor Neo - бесплатная','HHD Software Hex Editor Neo','','2023-09-05 05:39:30','reviakin.a',NULL,NULL,NULL),(771,376,'Scientific Calculator','','Scientific Calculator','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(772,57,'КОМПАС-3D Viewer V13','','КОМПАС-3D Viewer V13','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(773,377,'PDF Printer','','Bullzip PDF Printer','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(774,51,'Advanced PDF Editor 3','','Foxit Advanced PDF Editor 3','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(775,192,'nanoCAD Геоника x64 8','','nanoCAD Геоника x64 8','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(776,378,'Русская - Точка на цифровой клавиатуре','Раскладка клавиатуры, в списках Программ не видна','Русская - Точка на цифровой клавиатуре','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(777,379,'Nextcloud','Для азимутовского некстклауда','Nextcloud','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(778,380,'HxD Hex Editor','is free of charge for private and commercial use','HxD Hex Editor [0-9.]*$','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(779,381,'Wolfram Mathematica 7 ','Платная','Wolfram Mathematica 7 \\(M-WIN-L [0-9. ]*\\)','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(780,382,'ITS HF Propagation','HF propagation software available free of charge','ITS HF Propagation [0-9.]*$','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(781,383,'MMANA-GAL pro 2.5','Про версия платная','MMANA-GALpro 2.5','','2023-09-05 05:39:30','reviakin.a',NULL,NULL,NULL),(782,384,'Embarcadero RAD Studio XE5','Платная. Среда разработки','Embarcadero RAD Studio XE5','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(783,385,'OriginPro 8','Коммерческое программное обеспечение','OriginPro 8','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(784,386,'Пакет драйверов Windows','','Пакет драйверов Windows','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(785,387,'Пакет драйверов Windows','','Пакет драйверов Windows','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(786,388,'Пакет драйверов Windows','','Пакет драйверов Windows - CraftUnique Ltd.','','2023-09-05 05:39:30','reviakin.a',NULL,NULL,NULL),(787,389,'DipTrace','Платная','DipTrace','','2018-11-08 10:27:39',NULL,NULL,NULL,NULL),(788,390,'NetBeans IDE 8','Свободная интегрированная среда разработки приложений ','NetBeans IDE 8[.0-9]*$','','2023-09-05 05:39:30',NULL,NULL,NULL,NULL),(789,391,'Stardock Start8','Платная. Замена кнопки пуск для вин8','Stardock Start8','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(790,392,'-Win-Client-Pack','Бесплатный','UNetLab-Win-Client-Pack','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(791,4,'MySQL Workbench CE','Community Edition — распространяется под свободной лицензией GNU GPL','MySQL Workbench [0-9.]* CE$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(793,394,'ConEmu','Бесплатный. Эмулятор терминала для операционной системы Windows','ConEmu [0-9.gx]*$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(794,7,'Backup and Sync from Google','Бесплатно','Backup and Sync from Google','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(795,395,'AnVir Task Manager','Бесплатная системная утилита, которая позволяет контролировать всё, что запущено на компьютере','AnVir Task Manager','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(797,396,'Brennig\'s','Бесплатная программа для просмотра мультимедийных файлов','Brennig\'s [0-9.]*$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(798,388,'CraftWare','FREE, fast, easy-to-use slicer software that converts your digital 3D object into a .gcode toolpath format understood by most 3D printers','CraftWare 1[0-9.]*$','','2023-09-05 05:39:31','reviakin.a',NULL,NULL,NULL),(799,397,'Dev-C++','Свободная интегрированная среда разработки приложений для языков программирования C/C++.','Dev-C\\+\\+','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(800,4,'Database 11g Express Edition','Express Edition (XE) - бесплатная редакция','Oracle Database 11g Express Edition','Oracle Data Provider for \\.NET Help','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(801,398,'MicroSIP','GNU General Public License','MicroSIP$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(802,399,'Pencil','Free and open-source GUI prototyping tool ','Pencil$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(803,400,'SmartGit','Бесплатен для некоммерческого использования. Для коммерческого использования и получения технической поддержки нужно приобрести лицензию.','SmartGit$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(804,401,'','MIT License — лицензия открытого программного обеспечения','TaoFramework [0-9.]*$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(805,402,'USB Safely Remove *','ПЛАТНАЯ','USB Safely Remove [0-9.]*$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(806,403,'Visual CertExam Suite','ПЛАТНЫЙ','Visual CertExam Suite','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(807,347,'Wget','wget для винды. Бесплатно','GnuWin32\\: Wget-[0-9.-]*$','','2023-09-05 05:39:31','reviakin.a',NULL,NULL,NULL),(808,404,'Yate 5','Свободная и бесплатная кроссплатформенная программная VoIP-система, предназначенная для создания телефонных IP-АТС','Yate 5[0-9.]* - \\d$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(809,405,'Proteus Professional','ПЛАТНАЯ','Proteus Professional','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(810,406,'calibre','Свободное и открытое программное обеспечение для чтения, создания и хранения в электронной библиотеке электронных книг','calibre$','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(811,407,'Qualcomm USB Drivers For Windows','','Qualcomm USB Drivers For Windows','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(813,409,'','Бесплатная система мгновенного обмена текстовыми сообщениями ','WhatsApp','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(814,410,'Bandicam','','Bandicam','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(815,3,'Office 365 ProPlus','','Microsoft Office 365 ProPlus - ru-ru','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(816,411,'RADEX Data Center *','ПО для счетчика Гейгера','RADEX Data Center [0-9.]*\\, версия RADEX Data Center [0-9.]*','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(817,412,'x86_64-posix-seh-rt','GNU GPL. Порт линух утилит под винду','x86_64-[0-9.]*-posix-seh-rt_v\\d+-rev\\d+','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(818,57,'КОМПАС-3D Viewer v18','Бесплатный','КОМПАС-3D Viewer v18 x64\nКОМПАС-3D Viewer v18.1 x64','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(820,3,'SQL Server 2008','','Microsoft SQL Server 2008','Microsoft SQL Server VSS Writer\nMicrosoft SQL Server 2008 Native Client\nMicrosoft SQL Server 2008 Browser\nMicrosoft Visual Basic PowerPacks 10.0','2023-08-30 13:51:47',NULL,NULL,NULL,NULL),(821,3,'Visual C++ 2008, экспресс-выпуск','','Microsoft Visual C\\+\\+ 2008, экспресс-выпуск','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(822,3,'SQL Server 2014 Express LocalDB','Express бесплатный','Microsoft SQL Server 2014 Express LocalDB','Microsoft SQL Server 2014 Transact-SQL ScriptDom\nMicrosoft SQL Server 2014 T-SQL Language Service','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(823,3,'Windows SDK for Windows Server 2008','','Microsoft Windows SDK for Windows Server 2008','Microsoft Visual Studio 2008 Remote Debugger Light \\(x64\\) - RUS\nMicrosoft Windows SDK for Visual Studio 2008 Headers and Libraries\nMicrosoft Windows SDK for Visual Studio 2008 SP1 Express Tools for','2023-09-05 05:39:31','reviakin.a',NULL,NULL,NULL),(824,3,'WinUsb CoInstallers','Драйвер юсб от МС','WinUsb CoInstallers','WinUSB Compatible ID Drivers\nWinUSB Drivers ext','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(825,413,'Free Video Call Recorder for Skype','Бесплатно','Free Video Call Recorder for Skype','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(826,414,'X2Go Client for Windows','GNU GPLv2','X2Go Client for Windows','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(827,415,'SourceTree','Бесплатный клиент Git для Windows','SourceTree','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(828,416,'World of Tanks','Бесплатно :)','World of Tanks','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(829,417,'ATN Element Manager','Разработано для Азимута. В процессе разработки. Частично оплачено.','ATN Element Manager','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(830,418,'Telegram Desktop','Бесплатно','Telegram Desktop version [0-9.]*','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(831,60,'Qt SDK','Платный','Qt SDK','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(832,3,'Office 2016 Home and Business','офисный комплект','Microsoft Office Home and Business 2016\nMicrosoft Office для дома и бизнеса 2016 - ru-ru','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(833,419,'SpeedFan (remove only)','','SpeedFan \\(remove only\\)','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(834,72,'PComm Lite','','PComm Lite','','2023-09-05 05:39:31','reviakin.a',NULL,NULL,NULL),(835,420,'TMCS Client XR*','Разработка Азимута. Игнорируется','TMCS Client XR.*','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(836,420,'TMCS Server XR*','Разработка Азимута. Игнорируется','TMCS Server XR.*','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(837,420,'TMCS2000 VcsBrowser *','Разработка Азимута. Игнорируется','TMCS2000 VcsBrowser .*','','2023-09-05 05:39:31',NULL,NULL,NULL,NULL),(838,421,'@Text Replacer','Бесплатно','@Text Replacer','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(839,72,'ioSearch','Поставляется для определенной железки','Moxa ioSearch','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(840,72,'Network Enabler Administrator','Поставляется вместе с железками','Network Enabler Administrator .*','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(841,422,'KDiff3 ','Бесплатно','KDiff3 .*','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(842,423,'Драйвер Pinnacle Video Driver','','Драйвер Pinnacle Video Driver','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(843,423,'USB-2 Device Drivers','','Pinnacle Systems USB-2 Device Drivers','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(844,424,'3GP Player 2011','Бесплатное ПО','3GP Player 2011','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(845,317,'Kaspersky Endpoint Security 10 для Windows','','Kaspersky Endpoint Security 10 для Windows','Агент администрирования Kaspersky Security Center','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(846,128,'JNet','Java 1.7 requires JNET installation as part of GUI installer (SAP GUI for Windows 7.20)','SAP JNet','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(847,425,'Delta Design 2.6','Платная. САПР, реализующая сквозной цикл проектирования печатных плат','Delta Design 2.6','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(848,262,'STM8CubeMX','','STM8CubeMX','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(849,3,'Project 2007 Professional','','Microsoft Office Project Профессиональный 2007','Compatibility Pack for the 2007 Office system','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(850,16,'USB Audio','Фиг знает че это, но что-то в ноуте','Realtek USB Audio','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(851,3,'Local Administrator Password Solution','','Local Administrator Password Solution','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(852,151,'FineReader 9 Professional Edition','','ABBYY FineReader 9\\.0 Professional Edition','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(853,260,'CorelDRAW Graphics Suite X3','','CorelDRAW Graphics Suite X3','Corel Graphics - Windows Shell Extension','2023-09-05 05:39:32','reviakin.a',NULL,NULL,NULL),(854,3,'Visio 2013 Standard','','Microsoft Visio стандартный 2013','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(855,151,'FineReader 15','','ABBYY FineReader 15\nABBYY FineReader PDF 15','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(856,95,'СКБ Контур','','СКБ Контур','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(857,197,'Oxygen XML Author','','Oxygen XML Author','','2019-12-06 13:39:34',NULL,NULL,NULL,NULL),(858,190,'ГРАНД-СтройИнфо 2019','','ГРАНД-СтройИнфо 2019.*','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(859,192,'NormaCS 4.x Lite Клиент','','Nanosoft NormaCS 4.* Lite Клиент','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(860,192,'nanoCAD x64 Plus 11','','nanoCAD x64 Plus 11.*','','2019-12-24 12:07:38',NULL,NULL,NULL,NULL),(861,3,'Access database engine 2010','','Microsoft Access database engine 2010.*','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(862,311,'WibuKey Setup (WibuKey Remove)','Дрова для хаспа','WibuKey Setup.*','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(863,190,'ГРАНД-Смета 2019','','ГРАНД-Смета 2019','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(864,190,'ГРАНД-Смета, версия 7','','ГРАНД-Смета, версия 7','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(865,192,'nanoCAD СПДС Стройплощадка 8','','nanoCAD СПДС Стройплощадка 8.*','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(866,192,'nanoCAD Конструкции 6','','nanoCAD Конструкции 6.*','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(867,426,'Sentinel Runtime','Дрова к хаспу','Sentinel Runtime','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(868,4,'Java 7 Update 65 (64-bit)','','Java 7 Update 65 (64-bit)','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(869,1,'Options','ПО для мышей. По типу SetPoint, только для более новых моделей','Logitech Options','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(870,192,'nanoCAD x64 Plus 20','','nanoCAD x64 Plus 20','','2020-03-12 13:22:03',NULL,NULL,NULL,NULL),(871,3,'Teams','','Microsoft Teams','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(872,189,'Декларация 2018','','Декларация 2018','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(873,427,': Проект (клиент)','','АДЕПТ: Проект \\(клиент\\)','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(874,427,'\"АДЕПТ: Проект (клиент) 12.6\"','','\"АДЕПТ: Проект (клиент) 12.6\"','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(875,190,'ГРАНД-Смета 2020','','ГРАНД-Смета 2020.*','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(876,192,'NormaCS 4','','NormaCS 4.*','NormaCS 4.* Agent','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(877,76,'Драйверы Guardant','','Драйверы Guardant.*','','2023-09-05 05:39:32','reviakin.a',NULL,NULL,NULL),(878,317,'Агент администрирования Kaspersky Security Center','','Агент администрирования Kaspersky Security Center','','2023-10-27 04:56:04','admin',NULL,NULL,NULL),(879,428,'Agent for Microsoft Windows','','Veeam Agent for Microsoft Windows','Veeam Installer Service','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(880,3,'Windows Server 2012 Datacenter','','Microsoft Windows Server 2012 Datacenter','','2020-11-10 10:38:38',NULL,NULL,NULL,NULL),(881,3,'Windows Server 2016 Standard','','Майкрософт Windows Server 2016 Standard','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(882,3,'Windows Server 2003 Standard','','Microsoft\\(R\\) Windows\\(R\\) Server 2003, Standard Edition','Windows Server 2003 - обновления программного обеспечения\nWindows Server 2003 - Обновление программного обеспечения\nОбновление безопасности для проигрывателя Windows Media 6.4','2023-08-30 13:51:48',NULL,NULL,NULL,NULL),(883,3,'Windows Server 2008 R2 Standard','','Microsoft Windows Server 2008 R2 Standard','','2020-11-10 16:53:07',NULL,NULL,NULL,NULL),(884,3,'Windows Server 2012 Standard','','Microsoft Windows Server 2012 Standard','','2020-11-10 16:59:25',NULL,NULL,NULL,NULL),(885,3,'Windows Server 2012 R2 Datacenter','','Microsoft Windows Server 2012 R2 Datacenter','','2020-11-10 16:59:52',NULL,NULL,NULL,NULL),(886,3,'Windows Server 2012 R2 Standard','','Microsoft Windows Server 2012 R2 Standard','','2020-11-10 17:00:31',NULL,NULL,NULL,NULL),(887,3,'Windows Server 2008 Standard','','Microsoft Windows Server 2008 Standard','','2020-11-10 17:02:37',NULL,NULL,NULL,NULL),(889,2,'Processor Driver','','AMD Processor Driver','ATI - Software Uninstall Utility','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(891,3,'Office 2003 Professional','','Microsoft Office - профессиональный выпуск версии 2003','Compatibility Pack for the 2007 Office system\nMicrosoft Office 2003 Web Components','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(892,260,'WinZip','','WinZip','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(893,3,'Visio 2003 Professional','','Microsoft Office Visio Professional 2003','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(894,429,'Ghostscript','','GPL Ghostscript','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(895,57,'КОМПАС-3D V13','','КОМПАС-3D V13','','2023-09-05 05:39:32',NULL,NULL,NULL,NULL),(896,57,'КОМПАС-3D V10','','КОМПАС-3D V10','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(897,260,'CorelDRAW Graphics Suite 2019','','CorelDRAW Graphics Suite 2019','Corel Graphics - Windows Shell Extension\nGhostscript GPL','2023-09-03 08:15:14','reviakin.a',NULL,NULL,NULL),(898,22,'Пакет драйверов Windows','Для работы внутри Xen','Пакет драйверов Windows - Citrix\nCitrix Xen Windows x64 PV Drivers\nCitrix XenServer Windows Guest Agent','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(899,253,'Предприятие 8.3','','1C:Предприятие 8 \\(8.3','','2023-08-31 03:38:46','reviakin.a',NULL,NULL,NULL),(900,260,'CorelDRAW Graphics Suite X6','','CorelDRAW Graphics Suite X6','Corel Graphics - Windows Shell Extension','2023-09-05 05:39:33','reviakin.a',NULL,NULL,NULL),(901,317,'Kaspersky Security для Windows Server','','Kaspersky Security .* для Windows Server','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(902,260,'CorelDRAW Graphics Suite 2017','','CorelDRAW Graphics Suite 2017','Corel Graphics - Windows Shell Extension\nGhostscript GPL','2023-09-03 08:14:52','reviakin.a',NULL,NULL,NULL),(903,260,'Ghostscript GPL','Идет в комплекте с корелом. Видимо остается после удаления','Ghostscript GPL','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(904,55,'AO Help','','AO Help','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(905,3,'Update Health Tools','','Microsoft Update Health Tools','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(906,430,'GetVideo','','GetVideo','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(907,350,'Npcap','The Npcap License allows end users to download, install, and use Npcap from our site for free.','Npcap','','2023-09-05 05:39:33','reviakin.a',NULL,NULL,NULL),(908,81,'ICQ','ICQ от mail.ru. Бесплатно','ICQ','','2023-09-05 05:39:33','reviakin.a',NULL,NULL,NULL),(909,431,'AdmiLink','','AdmiLink','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(910,60,'Qt OpenSource','','Qt OpenSource','','2023-09-05 05:39:33','reviakin.a',NULL,NULL,NULL),(911,3,'Visual Studio Installer','','Microsoft Visual Studio Installer','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(912,432,'ONLYOFFICE Desktop Editors','Бесплатный редактор документов','ONLYOFFICE Desktop Editors','','2023-09-05 05:39:33','reviakin.a',NULL,NULL,NULL),(913,433,'Rocket.Chat','','Rocket.Chat','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(914,434,'WinDirStat','','WinDirStat','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(915,3,'Lync Web App Plug-in','','Microsoft Lync Web App Plug-in','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(916,317,'Консоль администрирования Kaspersky Security Center','','Консоль администрирования Kaspersky Security Center','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(917,3,'Office 2007 Primary Interop Assemblies','free','Microsoft Office 2007 Primary Interop Assemblies','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(918,435,'Zoom','','Zoom','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(919,55,'GPU Drivers','','ASUS GPU TweakII\nGPU Boost Driver','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(920,65,'Python Launcher','All Python releases are Open Source (see https://opensource.org/ for the Open Source Definition).','Python Launcher','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(921,4,'VM VirtualBox Guest Additions 5.0.2','','Oracle VM VirtualBox Guest Additions 5\\.0\\.2','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(922,3,'Пакет обеспечения совместимости для выпуска 2007 системы Microsoft Office','','Пакет обеспечения совместимости для выпуска 2007 системы Microsoft Office','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(923,436,'Mathcad 15 M010','Платное ПО. https://www.mathcad.com/en/try-and-buy/free-trial','Mathcad 15 M010','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(924,437,'Total Commander','Платное ПО, This program is Shareware, бесплатный переиод для теста 1 месяц, после удалить либо зарегистрировать','Total Commander \\(Remove or Repair\\)\nTotal Commander 64-bit \\(Remove or Repair\\)','','2023-09-05 05:39:33','reviakin.a',NULL,NULL,NULL),(925,3,'Fortran PowerStation 4.0','Платное ПО. https://news.microsoft.com/1997/03/11/digital-and-microsoft-announce-developer-studio-licensing-agreement/','Microsoft Fortran PowerStation 4.0','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(926,93,'LiteManager Pro - Viewer','Платное ПО удаленного управления рабочими станциями','LiteManager Pro - Viewer','','2021-01-26 05:31:52',NULL,NULL,NULL,NULL),(927,317,'Kaspersky Embedded Systems Security','','Kaspersky Embedded Systems Security','','2021-01-26 10:39:26',NULL,NULL,NULL,NULL),(928,93,'LiteManager Pro - Server','','LiteManager Pro - Server','','2023-10-27 04:56:00','admin',NULL,NULL,NULL),(929,83,'Пакет драйверов Windows - Planar','','Пакет драйверов Windows - Planar','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(930,438,'Graphviz','Eclipse Public License — лицензия открытого программного обеспечения','Graphviz','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(931,155,'VISA Shared Components','Лицензия не требуется. https://www.keysight.com/main/software.jspx?cc=DE&lc=ger&nid=-11143.0.00&id=2504667&pageMode=PV','VISA Shared Components 64-Bit','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(932,3,'Visual Studio Build Tools 2019','ПРАВА НА УСТАНОВКУ И ИСПОЛЬЗОВАНИЕ.  Вы можете установить и использовать любое количество экземпляров программного обеспечения для использования исключительно с Visual Studio Community, Visual Studio Professional и Visual Studio Enterprise с целью разрабо','Visual Studio Build Tools 2019','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(933,21,'Integrated Performance Primitives 2020','https://software.intel.com/content/www/us/en/develop/articles/end-user-license-agreement.html','Intel\\(R\\) Integrated Performance Primitives*','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(934,439,'DwimPerl','https://perlmaven.com/dwimperl','DwimPerl','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(935,78,'R&S NRP-Toolkit','Open Source Acknowledgment. https://scdn.rohde-schwarz.com/ur/pws/dl_downloads/dl_software/nrp_toolkit/NRP_Toolkit_OpenSourceAcknowledgment_en_06.pdf','R&S NRP-Toolkit','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(936,3,'Compatibility Pack for the 2007 Office system','','Compatibility Pack for the 2007 Office system','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(937,440,'Orbitum','Браузер, https://orbitum.com/ru/setup/','Orbitum','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(938,128,'3D Visual Enterprise Viewer 9','','SAP 3D Visual Enterprise Viewer 9','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(939,128,'Interactive Excel','','SAP Interactive Excel','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(940,128,'Business Client 6.5','','SAP Business Client 6\\.5','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(941,441,'Qt Creator','','Qt Creator','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(942,7,'Talk Plugin','','Google Talk Plugin','','2023-09-05 05:39:33',NULL,NULL,NULL,NULL),(943,442,'Windows Driver Package','','Windows Driver Package - Analog Devices \\(CYUSB\\) USB  *','','2023-09-05 05:39:33','reviakin.a',NULL,NULL,NULL),(944,263,'Пакет драйверов Windows - SEGGER (JLinkCDC) Ports  (*','','Пакет драйверов Windows - SEGGER \\(JLinkCDC\\) Ports  \\(*','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(945,443,'Пакет драйверов Windows ','','Пакет драйверов Windows - KEIL','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(946,444,'Пакет драйверов Windows - SEGGER Microcontroller GmbH (WinUSB) USBDevice  (*','','Пакет драйверов Windows - SEGGER Microcontroller GmbH \\(WinUSB\\) USBDevice  \\(*','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(947,83,'TRVNA','','TRVNA','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(948,3,'Visual C++ 2015-2019 Redistributable','','Microsoft Visual C\\+\\+ 2015-2019 Redistributable \\(x\\d\\d\\)','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(949,262,'STM32CubeIDE','Open Source Software','STMicroelectronics STM32CubeIDE','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(950,262,'stlink-server','Open Source Software','STMicroelectronics stlink-server','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(951,3,'Windows Driver Kit','','Windows Driver Kit - Windows','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(952,445,'Настройки камеры Logitech','','Настройки камеры Logitech','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(953,446,'ADIsimPE','Вы устанавливаете бесплатную демонстрационную версию Программного обеспечения. Вы можете установить эту версию Программного обеспечения на любое количество машин. Вам разрешено коммерческое использование.','ADIsimPE','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(954,78,'R&S Power Viewer','open source https://www.rohde-schwarz.com/ru/software/nrp-toolkit/','R&S Power Viewer','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(955,3,'System CLR Types для SQL Server *','','Microsoft System CLR Types для SQL Server *','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(956,447,'FileZilla Client','программное обеспечение с открытым исходным кодом, которое распространяется бесплатно в соответствии с условиями Стандартной общественной лицензии GNU.','FileZilla Client','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(957,448,'Пакет драйверов Windows','','Пакет драйверов Windows - Copper Mountain Technologies','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(959,450,'Revo Uninstaller','Revo Uninstaller FREEWARE. Версия Pro платная','Revo Uninstaller','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(960,35,'ePrint SW','','HP ePrint SW','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(962,3,'Visual Studio 2007 Tools for Applications - ENU','','Microsoft Visual Studio 2007 Tools for Applications - ENU','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(963,3,'Visual Studio 2005 Tools for Office','','Visual Studio 2005 Tools for Office','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(964,413,'Free Audio Converter','','Free Audio Converter','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(965,137,'Free Studio version 2013','','Free Studio version 2013','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(966,452,'Пакет драйверов Windows - Silicon Laboratories, Inc. (usbser) Ports  (09/01/2015 6.1.7601.17515)','','Пакет драйверов Windows - Silicon Laboratories, Inc\\. \\(usbser\\) Ports  \\(09\\/01\\/2015 6\\.1\\.7601\\.17515\\)','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(967,3,'SQL Server 2000','','Microsoft SQL Server 2000','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(968,453,'MySQL Connector/ODBC','https://dev.mysql.com/downloads/connector/odbc/','MySQL Connector\\/ODBC','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(969,70,'Yandex','','Yandex','','2023-10-27 04:56:02','admin',NULL,NULL,NULL),(970,282,'QIP 2010','','QIP 2010','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(971,454,'CodeBlocks','Code :: Blocks - это бесплатная IDE на C, C ++ и Fortran, созданная для удовлетворения самых взыскательных потребностей пользователей. http://www.codeblocks.org/','CodeBlocks','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(972,455,'X-HDL','https://www.xtekllc.com/','X-HDL','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(973,375,'Free Hex Editor Neo','Версия FREE бесплатна','HHD Software Free Hex Editor Neo','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(974,442,'SigmaStudio','Бесплатное ПО, но есть корпоротивная многопользовательская версия(читать лиц. соглош). Графический инструмент проектирования SigmaStudio™ - это программное обеспечения для разработки кода, программирования и настройки параметров аудиопроцессоров SigmaDSP','Analog Devices SigmaStudio v*','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(975,456,'Disk-O','','Disk-O','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(976,269,'LTpowerCADv2','вам будет предоставлено неисключительное, непередаваемое и бесплатное право исключительно на проектирование материалов с продуктами ADI','LTpowerCADv2','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(977,457,'ADIsimPLL Ver *','предоставляет вам неисключительные, не подлежащие переуступке, бесплатные лицензии на авторские права для оценки и тестирования Программного обеспечения. Использование Программного обеспечения любым другим способом запрещено, если иное не разрешено ADI в ','ADIsimPLL Ver *','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(978,152,'Clock Design Tool','NATIONAL SEMICONDUCTOR ПРЕДОСТАВЛЯЕТ ВАШЕЙ ОРГАНИЗАЦИИ БЕСПЛАТНУЮ ОГРАНИЧЕННУЮ ЛИЦЕНЗИЮ НА ИСПОЛЬЗОВАНИЕ ИНСТРУМЕНТА ДИЗАЙНА НАЦИОНАЛЬНЫХ ЧАСОВ','Clock Design Tool *','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(979,152,'TI PLLatinum Sim','','TI PLLatinum Sim','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(980,152,'TICS Pro','предоставляет вам ограниченную, полностью оплачиваемую, бесплатную лицензию только на создание копий, подготовку производных произведений, демонстрации и внутреннего использования Лицензионных материалов исключительно в связи с устройствами TI.','TICS Pro','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(981,3,'Visio','','Microsoft Visio - ru-ru','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(982,458,'FreeVimager','','FreeVimager','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(983,3,'Teams Machine-Wide Installer','','Teams Machine-Wide Installer','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(984,53,'PL23XX USB-to-Serial','','PL23XX USB-to-Serial','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(985,459,'IL-NT-Install','Софтина к железке','IL-NT-Install','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(986,460,'Silver Pilot 1.12 Trial','Ограничение пробной версии - сохраняет изображение не более 1024x1024 пикселей. Условно бесплатная. https://www.colorpilot.ru/pilot.html. Название теперь Color Pilot.','Silver Pilot 1.12 Trial','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(987,152,'TI-RTOS for C2000 2.16.01.14','ПО идет к микросхемам используемым в Азимуте. TI-RTOS поставляет компоненты, которые позволяют инженерам разрабатывать приложения на микроконтроллерах Texas Instruments','TI-RTOS for C\\d+ \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(988,461,'ALchemy','','Creative ALchemy','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(989,461,'ASIO','','Creative ASIO \\(USB\\)','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(990,461,'Dolby Digital Live Pack','','Dolby Digital Live Pack','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(991,461,'System Information','','Creative System Information','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(992,461,'Sound Blaster X-Fi Surround 5.1 Pro','','Sound Blaster X-Fi Surround \\d+\\.\\d+ Pro','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(993,72,'Device Manager','','MOXA Device Manager','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(994,462,'VNC Viewer','','VNC Viewer \\d+\\.\\d+\\.\\d+','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(995,463,'Killer Performance Suite','','Killer Performance Suite','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(996,3,'X86 Debuggers And Tools','','X86 Debuggers And Tools','','2023-09-05 05:39:34','reviakin.a',NULL,NULL,NULL),(997,21,'Microsoft Edge Update','','Microsoft Edge Update','','2023-09-05 05:39:34',NULL,NULL,NULL,NULL),(998,3,'Edge','','Microsoft Edge','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(999,3,'X64 Debuggers And Tools','','X64 Debuggers And Tools','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1000,3,'Visual Studio Build Tools 2017','','Visual Studio Build Tools 2017','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1001,21,'oneAPI Base Toolkit','','Intel® oneAPI Base Toolkit','','2023-09-05 05:39:35','reviakin.a',NULL,NULL,NULL),(1002,464,'Drivers','','AVerMedia C353 HD Capture Device','','2023-09-05 05:39:35','reviakin.a',NULL,NULL,NULL),(1003,21,'Parallel Studio XE 2017','опробовать в течение 30 дней новую версию можно, как всегда, абсолютно безвозмездно\r\nhttps://software.intel.com/content/www/us/en/develop/tools/oneapi/commercial-base-hpc.html#gs.z8e7mf','Intel Parallel Studio XE 2017','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1004,101,'COSMOSFloWorks 2008 SP05 x64 Edition','','COSMOSFloWorks 2008 SP05 x64 Edition','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1005,101,'COSMOSM 2008 x64 Edition (2008/275)','','COSMOSM 2008 x64 Edition \\(2008\\/275\\)','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1006,101,'2008 API SDK','','SolidWorks 2008 API SDK','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1007,18,'Teamcenter 12 (C:\\Siemens\\TC12CLIENT)','','Teamcenter 12 \\(C:\\\\Siemens\\\\TC12CLIENT\\)\nTeamcenter Visualization 12.4 64-bit','Teamcenter Integration for Altium Designer 5.0.1\nTeamcenter EDA 5','2023-08-30 13:51:49',NULL,NULL,NULL,NULL),(1008,465,'Business Studio 5','','Business Studio 4.2\nBusiness Studio 5','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1009,3,'Project','','Microsoft Project - ru-ru','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1010,190,'ГРАНД-Смета 2021','','ГРАНД-Смета, версия 2021','','2021-10-17 18:19:38',NULL,NULL,NULL,NULL),(1011,18,'NX','Почему-то в версии 19 мажорная версия перестала входить в название продукта.','Siemens NX','','2021-10-20 05:45:48',NULL,NULL,NULL,NULL),(1013,466,'The Bat! Professional','','The Bat! Professional','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1014,467,'Git','GNU GPL 2','Git','','2023-09-05 05:39:35','reviakin.a',NULL,NULL,NULL),(1015,461,'Sound Blaster Play!','Драйвер для дискретной звуковой карты','Sound Blaster Play','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1016,468,'GeoGebra Geometry','Онлайн? бесплатная вроде','GeoGebra Geometry','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1017,469,'Xilinx Design Tools ISE','Shareware. Xilinx ISE is a discontinued software tool from Xilinx for synthesis and analysis of HDL designs, which primarily targets development of embedded firmware for Xilinx FPGA and CPLD integrated circuit (IC) product families. ','Xilinx Design Tools ISE\nXilinx Design Tools  ISE','Xilinx Design Tools Xilinx Documentation Navigator','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1018,470,'Classic Shell','Classic Shell — бесплатный набор утилит с открытым исходным кодом (до версии 3.6.8) для возвращения вида интерфейса Windows XP в операционные системы Windows Vista и выше. Лицензия MIT','Classic Shell','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1019,21,'Quartus Prime Lite Edition (Free)','','Quartus Prime Lite Edition \\(Free\\)','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1020,471,'OCCT','OCCT is the most popular all-in-one stability check & stress test tool available.','OCCT','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1021,472,'Digilent Software','Проприетарное бесплатное ПО для работы с платами','Digilent Software','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1023,473,'AWR Design Environment 14','Платное. The AWR Design Environment platform provides RF/microwave engineers with integrated high-frequency circuit (Microwave Office), system (VSS), and EM (AXIEM/Analyst) simulation technologies and design automation','AWR Design Environment 14','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1024,130,'RapidDeveloper S','Компонент TC10','RapidDeveloper S','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1025,151,'FlexiCapture 12 Stations','','ABBYY FlexiCapture 12 Stations','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1026,474,'CoolUtils PCL Viewer','','CoolUtils PCL Viewer','Coolutils PCL Viewer бесплатен. Конечно, в нем нет никаких \"встроенных средств монетизации\": тулбаров, платных апгрейдов, призывов купить Про версию и пр. Теперь для просмотра PCL не нужно покупать дорогие программы, достаточно скачать Coolutils PCL Viewer!','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1027,475,'GerbView 6.04','','GerbView 6.04','в ознакомительном режиме в течение 30 дней.\nПо истечении этого срока вы должны приобрести лицензию, если хотите и дальше использовать продукт.','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1028,476,'Semantic 2015. МиС','','Semantic 2015. МиС','бесплатную версию электронного справочника Материалы и Сортаменты — Seman­tic МиС v.2015','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1029,158,'SystemVue 2009','платное надо сносить','SystemVue 2009.08','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1030,477,'ГИС для УПРЗА','','ГИС для УПРЗА','ГИС \\«Стандарт\\»','2021-10-27 12:00:56',NULL,NULL,NULL,NULL),(1031,477,'Сварка (версия 3.0)','','Сварка \\(версия 3\\.0\\)','','2021-10-27 12:01:20',NULL,NULL,NULL,NULL),(1032,477,'УПРЗА «Эколог»','','УПРЗА \\«Эколог\\»','','2021-10-27 12:02:03',NULL,NULL,NULL,NULL),(1033,477,'Эколог-Шум 2','','Эколог-Шум 2','АТП-Эколог\\. Версия .*\nЭколог-Шум\\. Каталог шумовых .*','2023-08-30 13:51:49',NULL,NULL,NULL,NULL),(1034,477,'Отходы строительства ','','Отходы строительства \\(версия .*','','2021-10-27 12:05:23',NULL,NULL,NULL,NULL),(1035,233,'SCAD Office','','SCAD Office','','2021-10-27 12:59:17',NULL,NULL,NULL,NULL),(1036,3,'Windows Server 2008 R2 Enterprise','','Microsoft Windows Server 2008 R2 Enterprise','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1037,478,'VisualSVN Server','Бесплатный пакет Apache Subversion server для Windows','VisualSVN Server','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1038,72,'Smartio/Industio Windows Driver','','MOXA Smartio\\/Industio Windows Driver','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1039,479,'PCI ICOM Driver','','Advantech PCI ICOM Driver','','2023-09-05 05:39:35',NULL,NULL,NULL,NULL),(1040,480,'Docklight','Платная программа симулятор RS232 протокола или ком-порта','Docklight','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1041,481,'ActiveDSO','ActiveDSO is an ActiveXTM control that enables Teledyne LeCroy oscilloscopes and LSA-1000 series embedded signal analyzers to be controlled by and exchange data with a variety of Windows applications that support the ActiveX standard','ActiveDSO','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1042,479,'Device Driver','','Advantech Device Driver','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1043,482,'OPC Core Components Redistributable','Бесплатное https://opcfoundation.org/license/redistributables/1.3/index.html','OPC Core Components Redistributable','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1044,483,'LIRA-SAPR 2019 R2','','LIRA-SAPR 2019 R2','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1047,483,'ESPRI 2014 R3','','ESPRI 2014 R3','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1048,485,'ФОК+ЛЕНТ-ПК Версия 2010 года','','ФОК\\+ЛЕНТ\\-ПК Версия 2010 года','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1049,486,'SAPFIR 2019 R2','','SAPFIR 2019 R2','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1050,487,'Super Charger','какие-то дрова','MSI Super Charger','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1051,488,'Agent','Эта маленькая, бесплатная утилита, самостоятельно обнаружит на вашем компьютере неизвестные устройства и автоматически установит для них драйверы.','DevID Agent','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1052,101,'eDrawings 2012','Не professional редакция бесплатна','SolidWorks eDrawings 2012','SolidWorks 2012 Document Manager API','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1053,489,'Wireless LAN Card Drivers','','Ralink RT2860 Wireless LAN Card','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1054,18,'JT2Go','JT2Go is the industry leading no charge 3D JT viewing tool','JT2Go','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1055,479,'DAQNavi Products','DAQNavi is free for any user. You can download it from Advantech\'s website at www.advantech.com and search using \"DAQNavi\" as the keyword and there will be a download link.','Advantech DAQNavi Products','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1056,265,'AvrTools','бесплатное ПО для работы с железом ATMEL AVR','AvrTools','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1057,72,'Active OPC Server','сервер сбора событий с оборудования MOXA','Moxa Active OPC Server','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1058,490,'UProg','Программатор противопожарного оборудования Болид: \"Сигнал-20П\" вер.3.10 и \"С2000-Ethernet\" вер.3.00-3.05, \"С2000-ПП\" вер. 2.00 - UProg, С2000-Ethernet, С2000-ПП, Сигнал-20П, Сигнал-20П SMD  ','Uninstall UProg','Orion2srv','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1059,490,'PProg','Программатор для противопожарного оборудования НПО Болид: Внимание! Предназначен только для конфигурирования \"С2000-КДЛ-2И исп.01\" - UProg, С2000-КДЛ-2И исп.01 ','PProg','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1060,491,'KeePass 2','Бесплатная хранилка паролей. Используется пользователями для хранения каких-то своих личных паролей. ','KeePass 2.*','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1061,492,'PDF24 Creator','Согласованный \"редактор PDF\" (может склеивать, разделять документы, поворачивать листы и т.п)','PDF24 Creator','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1062,493,'Офис. Профессиональный','','Р7-Офис. Профессиональный \\(десктопная версия\\)','','2023-09-05 05:39:36','reviakin.a',NULL,NULL,NULL),(1063,3,'Windows Embedded 8 Standard','','Microsoft Windows Embedded 8 Standard','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1064,3,'Windows Embedded Standard','','Microsoft Windows Embedded Standard','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1065,494,'ONTAP 8.2','','NetApp Release 8.2.2P1 7-Mode','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1066,3,'MS Access 2016','','','','2022-08-15 07:12:22',NULL,NULL,NULL,NULL),(1067,3,'Visual C++ 2015-2022 Redistributable','','Microsoft Visual C\\+\\+ 2015-2022 Redistributable \\(x\\d\\d\\)','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1068,3,'SQL Server 2014','','Microsoft SQL Server 2014 \\(64-разрядная версия\\)','Установка Microsoft SQL Server 2014 \\(на русском языке\\)','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1069,3,'Windows Server 2019 Standard','','Майкрософт Windows Server 2019 Standard','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1070,3,'Exchange Server 2019','','Microsoft Exchange Server 2019 Cumulative Update','Microsoft Unified Communications Managed API','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1071,151,'FineReader 11 Pro','','','','2023-01-31 10:44:30',NULL,NULL,NULL,NULL),(1072,151,'FineReader 12 Pro','','','','2023-01-31 10:52:36',NULL,NULL,NULL,NULL),(1073,151,'Finereader 15 Business','','','','2023-08-30 13:51:49',NULL,NULL,NULL,NULL),(1074,11,'Photoshop CS6','','','','2023-02-01 05:45:06',NULL,NULL,NULL,NULL),(1075,465,'Business Studio Enterprise','','Business Studio Enterprise','','2023-02-02 07:03:54',NULL,NULL,NULL,NULL),(1076,495,'DeviceLock','','','','2023-02-02 07:28:41',NULL,NULL,NULL,NULL),(1077,216,'ELMA BPM ECM+','Платное','','','2023-02-06 07:11:20',NULL,NULL,NULL,NULL),(1078,192,'nanoCAD СПДС','nanoCAD СПДС','','','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1079,3,'SQL Server 2016','','Microsoft SQL Server 2016 \\(64-bit\\)','Microsoft SQL Server 2016 Setup \\(English\\)\nMicrosoft SQL Server 2016 T-SQL ScriptDom\nMicrosoft VSS Writer for SQL Server 2016\nMicrosoft SQL Server 2016 T-SQL Language Service\nBrowser for SQL Server 2016','2023-09-05 05:39:36',NULL,NULL,NULL,NULL),(1080,428,'Backup & Replication','','Veeam Backup & Replication','Veeam Backup Transport\nVeeam Agent for Mac Redistributable','2023-09-05 05:39:36',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `soft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soft_hits`
--

DROP TABLE IF EXISTS `soft_hits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soft_hits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `soft_id` int NOT NULL,
  `comp_id` int NOT NULL,
  `hits` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `comp_id` (`comp_id`),
  KEY `soft_id` (`soft_id`),
  CONSTRAINT `comp_id_restr` FOREIGN KEY (`comp_id`) REFERENCES `comps` (`id`),
  CONSTRAINT `soft_id_restr` FOREIGN KEY (`soft_id`) REFERENCES `soft` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Софт автоматически обнаруженный на компах';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soft_hits`
--

LOCK TABLES `soft_hits` WRITE;
/*!40000 ALTER TABLE `soft_hits` DISABLE KEYS */;
INSERT INTO `soft_hits` VALUES (1,997,3,NULL),(2,998,3,NULL),(3,76,5,NULL),(4,998,5,NULL),(5,878,5,NULL),(6,490,5,NULL),(7,905,5,NULL),(8,115,5,NULL),(9,928,5,NULL),(10,997,5,NULL),(11,575,5,NULL),(12,969,5,NULL),(13,908,5,NULL),(14,76,6,NULL),(15,998,6,NULL),(16,878,6,NULL),(17,490,6,NULL),(18,905,6,NULL),(19,115,6,NULL),(20,928,6,NULL),(21,997,6,NULL),(22,575,6,NULL),(23,969,6,NULL),(24,908,6,NULL),(25,76,7,NULL),(26,998,7,NULL),(27,878,7,NULL),(28,490,7,NULL),(29,905,7,NULL),(30,115,7,NULL),(31,928,7,NULL),(32,997,7,NULL),(33,575,7,NULL),(34,969,7,NULL),(35,908,7,NULL),(36,76,8,NULL),(37,998,8,NULL),(38,878,8,NULL),(39,490,8,NULL),(40,905,8,NULL),(41,115,8,NULL),(42,928,8,NULL),(43,997,8,NULL),(44,575,8,NULL),(45,969,8,NULL),(46,908,8,NULL),(47,76,9,NULL),(48,998,9,NULL),(49,878,9,NULL),(50,490,9,NULL),(51,905,9,NULL),(52,115,9,NULL),(53,928,9,NULL),(54,997,9,NULL),(55,575,9,NULL),(56,969,9,NULL),(57,908,9,NULL),(58,76,10,NULL),(59,998,10,NULL),(60,878,10,NULL),(61,490,10,NULL),(62,905,10,NULL),(63,115,10,NULL),(64,928,10,NULL),(65,997,10,NULL),(66,575,10,NULL),(67,969,10,NULL),(68,908,10,NULL),(69,76,11,NULL),(70,998,11,NULL),(71,878,11,NULL),(72,490,11,NULL),(73,905,11,NULL),(74,115,11,NULL),(75,928,11,NULL),(76,997,11,NULL),(77,575,11,NULL),(78,969,11,NULL),(79,908,11,NULL),(80,76,12,NULL),(81,998,12,NULL),(82,878,12,NULL),(83,490,12,NULL),(84,905,12,NULL),(85,115,12,NULL),(86,928,12,NULL),(87,997,12,NULL),(88,575,12,NULL),(89,969,12,NULL),(90,908,12,NULL),(91,76,13,NULL),(92,998,13,NULL),(93,878,13,NULL),(94,490,13,NULL),(95,905,13,NULL),(96,115,13,NULL),(97,928,13,NULL),(98,997,13,NULL),(99,575,13,NULL),(100,969,13,NULL),(101,908,13,NULL),(102,76,14,NULL),(103,998,14,NULL),(104,878,14,NULL),(105,490,14,NULL),(106,905,14,NULL),(107,115,14,NULL),(108,928,14,NULL),(109,997,14,NULL),(110,575,14,NULL),(111,969,14,NULL),(112,908,14,NULL),(113,997,17,NULL),(114,998,17,NULL),(115,997,18,NULL),(116,998,18,NULL),(117,997,19,NULL),(118,998,19,NULL),(120,122,5,NULL),(121,122,6,NULL),(122,122,7,NULL),(123,122,8,NULL),(124,122,9,NULL),(125,122,10,NULL),(126,122,11,NULL),(127,122,12,NULL),(128,122,13,NULL),(129,122,14,NULL);
/*!40000 ALTER TABLE `soft_hits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soft_in_comps`
--

DROP TABLE IF EXISTS `soft_in_comps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soft_in_comps` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `comp_id` int NOT NULL COMMENT 'Компьютер',
  `soft_id` int NOT NULL COMMENT 'ПО',
  PRIMARY KEY (`id`),
  KEY `soft_id_idx` (`soft_id`),
  KEY `comp_id_idx` (`comp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COMMENT='Отношение софта и компов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soft_in_comps`
--

LOCK TABLES `soft_in_comps` WRITE;
/*!40000 ALTER TABLE `soft_in_comps` DISABLE KEYS */;
INSERT INTO `soft_in_comps` VALUES (1,9,76),(2,9,878),(3,9,115),(4,9,928),(5,9,969);
/*!40000 ALTER TABLE `soft_in_comps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soft_in_lics`
--

DROP TABLE IF EXISTS `soft_in_lics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soft_in_lics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `soft_id` int NOT NULL,
  `lics_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `soft_id` (`soft_id`),
  KEY `lics_id` (`lics_id`),
  CONSTRAINT `soft_in_lics_ibfk_1` FOREIGN KEY (`soft_id`) REFERENCES `soft` (`id`),
  CONSTRAINT `soft_in_lics_ibfk_2` FOREIGN KEY (`lics_id`) REFERENCES `lic_groups` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soft_in_lics`
--

LOCK TABLES `soft_in_lics` WRITE;
/*!40000 ALTER TABLE `soft_in_lics` DISABLE KEYS */;
INSERT INTO `soft_in_lics` VALUES (129,76,1),(130,880,2),(131,882,2),(132,883,2),(133,884,2),(134,885,2),(135,886,2),(136,887,2),(137,163,3),(138,450,3),(139,698,3),(140,820,3),(141,926,4),(148,1035,7),(150,1010,9),(212,15,22),(213,370,23),(214,1066,24),(219,1074,29),(223,902,33),(224,897,34),(233,79,60),(234,43,43),(235,174,44),(236,224,45),(237,501,46),(239,104,47),(240,104,48),(245,169,6),(247,752,54),(249,927,55),(250,927,56),(265,899,25);
/*!40000 ALTER TABLE `soft_in_lics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soft_in_lists`
--

DROP TABLE IF EXISTS `soft_in_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soft_in_lists` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `soft_id` int NOT NULL,
  `list_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `soft_id` (`soft_id`,`list_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soft_in_lists`
--

LOCK TABLES `soft_in_lists` WRITE;
/*!40000 ALTER TABLE `soft_in_lists` DISABLE KEYS */;
INSERT INTO `soft_in_lists` VALUES (1,115,1),(2,928,1),(3,969,1),(4,878,1),(5,76,1),(6,122,1);
/*!40000 ALTER TABLE `soft_in_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soft_lists`
--

DROP TABLE IF EXISTS `soft_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soft_lists` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descr` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Списки ПО';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soft_lists`
--

LOCK TABLES `soft_lists` WRITE;
/*!40000 ALTER TABLE `soft_lists` DISABLE KEYS */;
INSERT INTO `soft_lists` VALUES (1,'soft_agreed','Согласованное ПО','ПО из этого списка может быть установлено и внесено в паспорт'),(2,'warning','Запрещенное ПО','ПО запрещенное к установке');
/*!40000 ALTER TABLE `soft_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tech_models`
--

DROP TABLE IF EXISTS `tech_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tech_models` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `type_id` int DEFAULT NULL,
  `manufacturers_id` int DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Модель',
  `short` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Короткое имя',
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ссылки',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Комментарий',
  `individual_specs` int DEFAULT '0',
  `ports` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `scans_id` int DEFAULT NULL,
  `front_rack_layout` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contain_front_rack` tinyint(1) DEFAULT '0',
  `front_rack_two_sided` tinyint(1) DEFAULT '0',
  `back_rack_layout` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contain_back_rack` tinyint(1) DEFAULT '0',
  `back_rack_two_sided` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `type_id` (`type_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `short` (`short`),
  KEY `idx-tech_models-archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=392 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tech_models`
--

LOCK TABLES `tech_models` WRITE;
/*!40000 ALTER TABLE `tech_models` DISABLE KEYS */;
INSERT INTO `tech_models` VALUES (2,2,180,'SPA 504G','','Cisco https://www.cisco.com/c/en/us/support/collaboration-endpoints/spa504g-4-line-ip-phone/model.html','Количество линий - 4\r\nУмеет провижнинг - да\r\nПитание POE 802.3af 2W\r\nБП в комплекте - нет\r\nМожно включать бриджом - да\r\nНужен блок питания или PoE!\r\nМодели совместимых блоков питания:\r\nLinksys PA100-EU\r\nCisco SB PA100-EU',0,'LAN\r\nPC',199,NULL,0,0,NULL,0,0,'2023-09-08 03:19:59',NULL,NULL),(3,3,35,'ProLiant DL380 Gen6','DL380 G6','quick specs https://h20195.www2.hpe.com/v2/getpdf.aspx/c04282582.pdf','2 units\r\nMB: Intel® 5520 Chipset\r\nCPU: 2 CPU Sockets\r\nNET: 4 NIC\r\nRAM: 12 Слотов DDR3 (192Gb Max 12x16Gb)\r\niLO\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(4,3,35,'ProLiant DL380 Gen9','DL380 G9','quick specs https://h20195.www2.hpe.com/V2/GetPDF.aspx/c04346247.pdf','CPU: 2 Sockets: Intel® E5-2600 v3,v4 Processor Family\r\nMB: Intel® C610 Series Chipset\r\nRAM: 24 DDR4 Slots (RDIMM/LRDIMM/NVDIMM)\r\nNET: 4 NICs',1,'iLO\r\neth1\r\neth2\r\neth3\r\neth4',282,'',0,0,'',0,0,'2023-09-13 16:15:57','admin',NULL),(5,3,35,'ProLiant BL460c Gen8','BL460c G8','quick specs https://h20195.www2.hpe.com/v2/getpdf.aspx/c04123239.pdf?ver=40','Блейд сервер для HP BladeSystem c3000 и c7000\r\nCPU: Up to 2x CPU: Intel® E5-2600 and 2600v2 Processor Families\r\nMB: Intel® C600 Series Chipset\r\nRAM: 16slots DDR3 (LRDIMM/RDIMM/UDIMM)\r\nVideo: Integrated Matrox G200 video standard\r\nHP iLO (Firmware: HP iLO 4)',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(6,3,35,'ProLiant BL460c Gen9','BL460c G9','quick specs https://h20195.www2.hpe.com/V2/GetPDF.aspx/c04347343.pdf','Блейд сервер для HP BladeSystem c3000 и c7000\r\nCPU: Up to two (2) Intel® Xeon® E5-2600 v3 or v4 family\r\nMB: Intel® C610 Series Chipset\r\nRAM: 16 DIMM slots (8 per cpu) DDR4 (LRDIMM/RDIMM)\r\nVideo: Integrated Matrox G200eh\r\nHP iLO (Firmware: HP iLO 4 2.0)',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(7,3,496,'RH5885H V3','','quick specs http://www.advanserv.ru/!upload/t_models/318f1c8e995a6b13334be2294499efef.pdf','4U rack server\r\nCPU: 2/4 x Intel Xeon® E7-4800 v2 processor\r\nRAM: 96 DDR3 DIMMs, up to 6 TB (using 64 GB DIMMs)\r\nNET: 2 or 4 x GE ports, or 2 x 10GE ports',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(8,3,496,'FusionServer CH121 V3','CH121 V3','specs https://e.huawei.com/ru/products/servers/e-series/ch121-v3-node','Лезвие для E9000\r\nCPU: 2x Socket for E5-2600 v3\r\nRAM: 24x DDR4 DIMM',1,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(9,3,496,'RH1288 V3','','support: https://support.huawei.com/enterprise/ru/intelligent-servers/rh1288-v3-pid-9901873','1U rack server\r\nCPU: Up to 2 Intel Xeon E5-2600 V3\r\nRAM: 16 RDIMM/LRDIMM DDR4\r\nHDD: RAID 0, 1, 10, 5, 50, 6 или 60',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(10,3,496,'2288H V5','','','2U rack server\r\n8 CPUs x Intel(R) Xeon(R) Silver 4110 CPU @ 2.10GHz   \r\nRAM: 64 RDIMM/LRDIMM DDR4\r\nHDD: RAID 0, 1, 10, 5, 50, 6 или 60',1,'',200,NULL,0,0,NULL,0,0,'2023-09-08 03:19:59',NULL,NULL),(11,4,180,'CISCO2911','','','CISCO2911\r\n3 порта (2 WAN 1 LAN)',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(12,4,497,'RB951G-2HnD','','','5 портов 10/100/1000MBit\r\n1 USB type A\r\nWiFi 802.11b/g/n\r\nПитание: 9-30V БП или passive PoE-IN (7W max)\r\nCPU AR9344 1core x 600 MHz (MIPSBE)\r\nRAM 128MB\r\nБП в комплекте',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(13,3,55,'SPB-server','SPB-server','','SPB-server',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(14,5,498,'DP750','','Описание (англ) http://www.grandstream.com/products/ip-voice-telephony/dect-cordless/product/dp750\r\nPDF datasheet (руск) http://www.grandstream.com/sites/default/files/Resources/datasheet_dp750_russian.pdf\r\n','до 10 SIP аккаунтов \r\nпровижн через TLS/SRTP/ HTTPS и TR-069\r\nУмеет в отказоустойчивость, но медленно\r\n3-х сторонняя конференция\r\nподдержка PoE',0,NULL,201,NULL,0,0,NULL,0,0,'2023-09-08 03:19:59',NULL,NULL),(15,2,498,'DP720','','Описание http://www.grandstream.com/products/ip-voice-telephony/dect-cordless/product/dp720\r\nPDF datasheet http://www.grandstream.com/sites/default/files/Resources/datasheet_dp720_russian.pdf','DECT Трубка к шлюзу DP750\r\nУмеет провижионинг и отказоустойчивость (медленно перекл)\r\nПоставляется с зарядным стаканом (5V 1A БП в комплекте)\r\nМожно подключать к зарядке microusb\r\nВнутри аккумуляторы ААА (2шт) - 250ч ожидания/20ч разговора\r\nЦветной TFT дисплей 1,8\" 128x160px\r\n3х сторонняя конференция\r\nСброс настроек Нажмите “Menu” -> “Settings” -> “Advanced Settings” -> “Factory Reset” \r\nЛогин пароль по умолчанию admin/admin',0,'',288,'',0,0,'',0,0,'2023-09-15 05:14:52','admin',NULL),(16,6,99,'Ecosys M8124cidn','M8124cidn','спецификация https://www.kyoceradocumentsolutions.ru/index/products/product/ecosysm8124cidn.technical_specification.html\r\n','Цветное лазерное МФУ А3\r\nДвусторонняя печать\r\nДвустороннее сканирование\r\nСканирование на почту/SMB/FTP\r\nВход: Admin:Admin\r\nКартриджи:\r\nTK-8115K Черный тонер, 12000 страниц\r\nTK-8115C Голубой тонер, 6000 страниц\r\nTK-8115M Пурпурный тонер, 6000 страниц\r\nTK-8115Y Желтый тонер, 6000 страниц',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:24',NULL,NULL),(17,1,162,'Самосбор','Самосбор','','Самостоятельно собранный системный блок не типовой конфигурации из компонент разных производителей.',0,'Lan',202,NULL,0,0,NULL,0,0,'2023-09-08 03:20:00',NULL,NULL),(18,6,35,'LaserJet Pro M132fw','','http://www8.hp.com/ru/ru/products/printers/product-detail.html?oid=9365260#!tab=features\r\n','Печать, копирование, сканирование, факс\r\nТип сканера: Устройство АПД, планшетный сканер\r\nСканирование на почту, SMB\r\nОдностороннее сканирование\r\n10000 стр/мес\r\nКартридж HP LaserJet CF218A (18А)',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(19,6,35,'LaserJet Pro 400 M475dn','','Спецификация на nix.ru https://www.nix.ru/autocatalog/printers_hp/hp-LaserJet-Pro-400-COLOR-MFP-M475dn-CE863A-A4-20str-min-192Mb-LCD-MFU-faks-USB20-setevoj-dvustpechat-DADF_129036.html\r\nДрайверы https://support.hp.com/us-en/drivers/selfservice/HP-LaserJet-Pro-400-color-MFP-M475/4337543/model/4337754','Двусторонняя печать\r\nДвустороннее сканирование\r\nДля интенсивного использования\r\nКартриджи: \r\nчерный HP CE410A (305A), \r\nголубой HP CE411A (305A),\r\nжелтый HP CE412A (305A), \r\nпурпурный HP CE413A (305A).',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(20,6,112,'imageRUNNER 2202N','iR2202N','Спецификация https://www.canon-europe.com/for_work/products/office_print_copy_solutions/office_black_white/imagerunner_2202n/specification.aspx\r\nДрайверы https://www.canon.ru/support/products/imagerunner/imagerunner-2202n.aspx?type=drivers','Двусторонняя печать: опционально\r\n(requires optional Duplex Unit-C1)\r\nДвустороннее сканирование: опционально\r\n(requires optional DADF-AM1 unit)\r\nСканирование на E-Mail\r\nLogin/Pass: 7654321/7654321\r\n\r\nКартридж Canon C-EXV42 \r\nФотобарабан Canon C-EXV42 Drum',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(21,6,112,'i-SENSYS MF249dw','MF249dw','nix https://www.nix.ru/autocatalog/printers_canon/Canon-i-SENSYS-MF249dw-A4-512Mb-27-str-min-lazernoe-MFU-faks-LCD-DADF-dvustoronnyaya-pechat-USB-20-setevoj-WiFi_289514.html','Двусторонняя печать\r\nДвустороннее сканирование\r\nСетевой\r\nкартриджи: Cartridge 737, CF283\r\n10000 стр/мес\r\nSN(web): Монитор -> Сведения об устр.',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(22,7,499,'DGS-1100-10/ME','','Спецификация http://www.dlink.ru/u/products/1/1976_b.html\r\nnix https://www.nix.ru/autocatalog/networking_d_link/D-Link-DGS-1100-10-ME-Gigabit-Smart-Switch-8UTP-1000Mbps-plus-2Combo-1000BASE-T-SFP_197318.html','Управляемый коммутатор 2-го уровня \r\n8 портов 10/100/1000Base-T \r\n2 комбо-портов 100/1000Base-T/SFP\r\nPoE отсутствует',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(23,6,112,'imageRUNNER 2204','iR2204','Спецификация https://www.canon.ru/for_work/products/office_print_copy_solutions/office_black_white/imagerunner_2204/','Не сетевой. Только USB!\r\nОдносторонняя печать и сканирование\r\nПодатчика сканера нет.\r\nПИН Админа: 7654321\r\n\r\nЧерный тонер C-EXV 42\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(24,6,35,'LaserJet Pro M477fdw','','http://www8.hp.com/ru/ru/products/printers/product-detail.html?oid=7257107#!tab=specs','Двусторонняя печать A4 цв.\r\nКартриджи: \r\nчерный HP CF410A (410A), \r\nголубой HP CF411A (410A), \r\nжелтый HP CF412A (410A),\r\nпурпурный HP CF413A (410A).\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(25,6,99,'Ecosys M4132idn','','https://www.kyoceradocumentsolutions.ru/index/products/product/ecosysm4132idn.html','Двусторонняя печать A3 ч/б\r\nДля интенсивного использования\r\nTK-6115 (tk6115)     Тонер-картридж Kyocera (оригинальный)\r\n\r\nMK-6110 (mk6110)  Сервисный комплект для автоподатчика оригиналов (оригинальный)\r\nMK-6115 (mk6115)  Сервисный комплект Kyocera (оригинальный)\r\nDV-6115 (dv6115)   Блок проявки (оригинальный)\r\nDK-6115 (dk6115)   Блок фотобарабана (оригинальный)\r\nFK-6115 (fk6115)    Термоблок (оригинальный)\r\n302K394480           Основной узел подачи в сборе (оригинальный)\r\nTR-6115 (tr6115)     Блок переноса (оригинальный)\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(26,7,499,'DGS-1100-16','','Specs http://www.dlink.ru/r/products/1/1941_b.html','Управляемый коммутатор EasySmart\r\n16 портов 10/100/1000Base-T\r\nPoE отсутствует',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(27,7,499,'DGS-1210-48','','Спецификации http://www.dlink.ru/ru/products/1/1316_b.html','Управляемый WebSmart коммутатор\r\n44 порта 10/100/1000Base-T\r\n4 комбо-порта 1000Base-T/SFP',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(28,8,500,'Back-UPS BR500CI-RS','BR500CI-RS','nix https://www.nix.ru/autocatalog/apc/UPS-500VA-Back-RS-APC-BR500CI-RS_91920.html','500ВА/300Вт\r\n3 розетки IEC-320-C13 (компьютерный штекер)\r\nАккумулятор RBC114 ',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(29,9,501,'UniFi AP','UAP','datasheet https://dl.ubnt.com/datasheets/unifi/UniFi_AP_DS.pdf\r\nОКОНЧАНИЕ ПОДДЕРЖКИ https://community.ui.com/questions/Select-UniFi-AP-models-with-support-ending-Mar-2021/65487283-ce9d-49f4-85b9-b6aa54659ef7','Для установки внутри помещений\r\nСтандарты 802.11 b/g/n 2.4Ghz\r\nПроп. способность: 300Mbps\r\nРадиус: 122m\r\nПитание по PoE: Passive PoE (12-24V)\r\nМакс. энергопотребление: 4W\r\nИнжектор в комплекте: 24V, 0.5A PoE Adapter\r\nВНИМАНИЕ! окончание поддержки! (см ссылки)',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(30,7,499,'DGS-1100-24P/ME','','http://www.dlink.ru/ch/products/1/2005.html','Управляемый коммутатор EasySmart\r\n24 порта 10/100/1000Base-T\r\n12 портов с поддержкой PoE 802.3af/802.3at (30 Вт)\r\nPoE-бюджет 100 Вт',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(31,9,501,'UniFi AP AC LR','UAP-AC-LR','Страничка модели https://www.ui.com/unifi/unifi-ap-ac-lr/\r\ndatasheet https://dl.ubnt.com/datasheets/unifi/UniFi_AC_APs_DS.pdf','Для установки внутри помещений\r\nСтандарты: 802.11 a/b/g/n/r/k/v/ac 2.4GHz, 5GHz\r\nПропуск. способность: 867Mbps (5GHz), 300 Mbps(2.4GHz)\r\nПитание по PoE:\r\n- 802.3af/A PoE\r\n- 24V Passive PoE\r\nМаксимальное энергопотребление: 6.5W\r\nИнжектор в комплекте: 24V, 0.5A Gigabit PoE Adapter\r\nСудя по спецификации, отличается от LIte антенной',0,NULL,204,NULL,0,0,NULL,0,0,'2023-09-08 03:20:01',NULL,NULL),(32,6,35,'LaserJet Pro M377dw','','Спецификация https://www8.hp.com/ru/ru/products/printers/product-detail.html?oid=8109052#!tab=specs\r\nnix https://chel.nix.ru/autocatalog/printers_hp/HP-COLOR-LaserJet-Pro-MFP-M377dw-M5H23A-A4-24str-min-256Mb-LCD-lazMFU-4kraski-USB20-set-WiFi-ADF-dvustpechat_271127.html','Сетевой\r\nДвусторонняя печать\r\nДвустороннее сканирование\r\nСканирование на почту и SMB\r\nЧерный: CF410A (№410A), CF410X (№410X) (экономичный)\r\nГолубой: CF411A (№410A), CF411X (№410X) (экономичный)\r\nЖелтый: CF412A (№410A), CF412X (№410X) (экономичный)\r\nПурпурный: CF413A (№410A), CF413X (№410X) (экономичный)',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(33,7,499,'DGS-1210-52MP','DGS-1210-52MP','nix https://chel.nix.ru/autocatalog/networking_d_link/D-Link-DGS-1210-52MP-F1A-Upravlyaemyj-kommutator-48UTP-1000Mbps-PoE-plus-4-SFP_329499.html','Управляемый WebSmart коммутатор\r\n48 портов 10/100/1000Base-T с поддержкой PoE+ \r\n4 комбо порта 1000Base-T/SFP\r\nвент: ADDA AD0412UB-C56 40x40x20 8500rpm 12V 0.14A 3-pin',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(34,6,99,'Ecosys M3145dn','M3145dn','Спецификация https://www.kyoceradocumentsolutions.ru/index/products/product/ecosysm3145dn.technical_specification.html','Сетевой\r\nДвусторонняя печать\r\nДвустороннее сканирование (с податчика)\r\nСканирование на почту, SMB, FTP, флешку\r\nПредельная нагрузка: 150000 лист./мес.\r\nТонер-барабан TK-3160 на 12500 стр',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(35,8,500,'SMX2200HVNC','','Спецификация https://www.apc.com/shop/ru/ru/products/APC-Smart-UPS-X-2200-200-240-/P-SMX2200HVNC\r\nБатарейный картридж https://www.apc.com/shop/ru/ru/products/-APC-143/P-APCRBC143\r\nДополнительный бат. модуль https://www.apc.com/shop/ru/ru/products/-APC-Smart-UPS-X-120-/P-SMX120BP\r\nГрафики автономной работы https://www.apc.com/products/runtimegraph/runtime_graph.cfm?base_sku=SMX2200HVNC&chartSize=large','Управляемый по сети (предустановленая карта управления)\r\nМакс. вых. мощность 1.98 KВатт / 2.2 kВА\r\n2шт IEC 320 C19 - здоровые разъемы (как на ввод)\r\n8шт IEC 320 C13 - обычный выход с ИБП (нужны соотв кабели)\r\nБатарейный картридж APCRBC143 \r\nДоп батарейный модель SMX120BP',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(36,8,502,'Back Basic 650 Euro','Back Basic 650','https://www.nix.ru/autocatalog/ippon/UPS-650VA-Ippon-Back-Basic-650-USB_280482.html','Максимальная выходная мощность:	650 ВА\r\nЭффективная мощность:	360 Ватт\r\nКол-во розеток с батарейной поддержкой:	3\r\nТип розеток:	Компьютерные С13 (IEC-320-C13)\r\nАккумуляторы:	1 аккумулятор 12В, 7 Ач\r\nРазмеры сменного аккумулятора (ШхВхГ):	151 х 100 х 65 мм (12В, 7/9 Ач)\r\nВозможность установки доп бат. модулей: нет\r\nИнтерфейс:	USB',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(37,4,497,'RB2011UiAS-2HnD-IN','','спецификация https://mikrotik.com/product/RB2011UiAS-2HnD-IN','10 портов: \r\n5портов х 1ГБит, 5портов х 100Мбит\r\n1 х SFP, 1 x USB\r\nWiFi 802.11b/g/n с 2 внешними антеннами\r\nPassive PoE-out на Eth10 (510mA)\r\nПитание: 8-30 V БП или passive PoE-IN (11W max)\r\nCPU AR9344 1core x 600 MHz\r\nRAM 128MB\r\nЭкран',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(38,10,503,'BD4640DS','','спец https://www.beward.ru/katalog/ip-kamery/ip-kamery-serii-bd/ip-kamera-bd4640ds/','Цифровая, внутренняя\r\n4Мп, 84° по гориз (3.6 мм), записб звука\r\nНочной режим без подсветки\r\nПо умолч 192.168.0.99/24 admin:admin',0,NULL,293,NULL,0,0,NULL,0,0,'2023-09-15 14:38:00','admin',NULL),(39,8,502,'SMART POWER PRO II 2200','Smart 2200','Спец. http://ippon.ru/catalog/item/smartpowerproII\r\nАкк. http://ippon.ru/catalog/item/ip12-9','Управляемый ИБП, подкл по USB\r\n2200ВА, 1200Вт, Бат.: 12В/9Ач х 2 шт\r\n12мин при нагр 400Вт\r\nВыход на 4 розетки IEC 320 C13 (под шнур ИБП)\r\nАкк. 12V 9Ah Ippon IP12-9 (или любой 12в 9Ач)',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(40,7,499,'DES-1050G','DES-1050G','','Неуправляемый.\r\n48 портов 10/100/1000BASE-T\r\n2 комбо-порта 10/100/1000BASE-T/SFP\r\nPoE отсутствует\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(41,8,500,'SMX3000RMHV2UNC','SMX 3000VA ','Оф. сайт https://www.apc.com/shop/ru/ru/products/APC-Smart-UPS-X-3000-200-240-/P-SMX3000RMHV2UNC\r\nГрафик времени работы от нагрузки https://www.apc.com/products/runtimegraph/runtime_graph.cfm?base_sku=SMX3000RMHV2UNC&chartSize=large\r\nСменный батарейный блок: APCRBC117 https://www.apc.com/shop/ru/ru/products/-APC-117/P-APCRBC117\r\nДоп. батарейный модуль: APCRBC118 https://www.apc.com/shop/ru/ru/products/-APC-118/P-APCRBC118 \r\nТип совместимых аккумуляторов: WP5-12SHR 12V 5Ah https://www.avacom.cz/Datasheety/LONG/PBLO-12V005-F2AH.pdf','Управляемый, линейно-интерактивный ИБП\r\nМакс мощность 2700Вт, емкость 738 ВАч\r\nРаботает 26мин при 1000Вт, 59мин при 500Вт\r\nПоддержка доп. модулей SMX120RMBP2U\r\n8 розеток IEC 320 C13\r\nБатарейная сборка APCRBC117, 600ВАч\r\nКарта управления AP9631\r\nLogin: apc / apc\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(42,7,499,'DGS-1210-10/F1A','DGS-1210-10','nix https://chel.nix.ru/autocatalog/networking_d_link/D-Link-DGS-1210-10-F1A-Web-Smart-Switch-8UTP-1000Mbps-plus-2SFP_320742.html','Управляемый коммутатор\r\n8 портов RJ45 x1Gbit\r\n2 порта SFP',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(43,4,497,'RBSXTR&R11e-4G','Mikrotik SXT 4G kit ','','4G Маршрутизатор Mikrotik SXT 4G kit\r\n4G/LTE CPE\r\n10.5dBi 60 degree antenna\r\n2x Ethernet ports (one with PoE out)\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(44,8,500,'Smart-UPS 1500 SMT1500I','SMT1500I','Диаграмма времени работы от нагрузки https://www.apc.com/products/runtimegraph/runtime_graph.cfm?base_sku=SMT1500I&chartSize=large\r\nспецификация https://www.apc.com/shop/ru/ru/products/APC-Smart-UPS-1500-230-/P-SMT1500I','Неуправляемый\r\n408 Вт*ч / макс нагрузка 1КВт\r\n30 минут при нагрузке 400Вт\r\nПоп батарейных модулей нет\r\n8 розеток C13 (компьютерных)\r\nБат. сборка RBC7: 2хбат 12Вх17Ач\r\nсовм. батарея CSB GP 12170 (2шт)',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(45,7,499,'DGS-1100-08P','','http://www.dlink.ru/uk/products/1/1940.html','Настраиваемый коммутатор EasySmart\r\n8 портов 10/100/1000Base-T \r\nвсе порты с поддержкой PoE 802.3af/802.3at (30 Вт)\r\nPoE‑бюджет 64 Вт',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(46,7,499,'DGS-1100-24P','','http://www.dlink.ru/ru/products/1/2005_d.html','Управляемый коммутатор EasySmart\r\n24 портами 10/100/1000Base-T\r\n12 портов с поддержкой PoE 802.3af/802.3at (30 Вт)\r\nPoE-бюджет 100 Вт',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(47,7,499,'DGS-1100-08','','http://www.dlink.ru/ru/products/1/1939_d.html','Настраиваемый коммутатор EasySmart\r\n8 портов 10/100/1000Base-T\r\nPoE отсутствует',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(48,8,500,'Smart-UPS 1000 SUA1000I','SUA1000I','Спецификации https://www.apc.com/shop/ru/ru/products/APC-Smart-UPS-1000-USB-230-/P-SUA1000I\r\nграфик времени автономной работы https://www.apc.com/products/runtimegraph/runtime_graph.cfm?base_sku=SUA1000I&chartSize=large\r\nБат. сборка RBC6 https://www.apc.com/shop/ru/ru/products/-APC-6/P-RBC6\r\n\r\n','Управляемый (USB)\r\n1000VA, 670W, емкость 12Vx12Ah\r\nДоп. бат. модули не предусмотрены\r\n8 розеток C13 (переходник) - все зарезервированы\r\nБатарейная сборка RBC6\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(49,7,35,'OfficeConnect 8 Port Gigabit Switch 1420 (JH329A)','1420 8G Switch','nix https://www.nix.ru/autocatalog/networking_3com/HP-1420-8G-JH329A-Neupravlyaemyj-kommutator-8UTP-1000Mbps_279363.html\r\nhpe https://www.hpe.com/ru/ru/product-catalog/networking/networking-switches/pip.specifications.switches.1008831946.html','Неуправляемый, без PoE, 8 Гигабитных портов\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(50,6,112,'i-SENSYS MF212w','MF212w','Canon https://www.canon.ru/for_home/product_finder/multifunctionals/laser/i-sensys_mf212w/','Сетевой\r\nОдносторонняя печать\r\nОдностороннее сканировние (податчика нет)\r\nСканирования на почту, папку нет\r\nНагрузка 8000стр/мес\r\nКартридж 737 (2400стр)',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(51,9,501,'NanoStation M2','','','WiFi мост\r\nуличное исполнение\r\nдо 150Мбит/с\r\nдо 13км\r\nPoE адаптер в комплекте',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(52,7,499,'DGS-1210-20/ME','','dlink.ru http://dlink.ru/Ru/products/1/1981.html','Управляемый\r\n16Gbe + 4SFP порта\r\nPoE отсутствует',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(53,6,99,'Ecosys M2040dn','','Спецификация https://www.kyoceradocumentsolutions.ru/index/products/product/ecosysm2040dn.technical_specification.html','МФУ, А4, Чёрно-белый\r\nСетевой: Да\r\nПечать: 2-сторонняя\r\nСканер: Податчик с 2-сторонним сканированием на почту, FTP, SMB\r\nДопустимая нагрузка по печати: до 50 000 стр/мес\r\nМодель картриджа: TK-1170 \r\nAdmin:Admin',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(54,2,498,'GAC2500','','product page http://www.grandstream.com/products/business-conferencing/audio-conferencing/product/gac2500\r\ndatasheet pdf http://www.grandstream.com/sites/default/files/Resources/datasheet_gac2500_russian.pdf','Конференц телефон на базе Android\r\n3 кардиоидных микрофона; Bluetooth; WiFi\r\nРасстояние захвата 12 фут., 360° покрытие\r\n6 SIP-аккаунтов\r\n4.3” IPS ЖК дисплей с разрешением 800x480\r\nЗаявлен провижн через XML\r\nУниверсальный блок питания: 12V,2A / PoE+\r\n\r\n',0,NULL,290,NULL,0,0,NULL,0,0,'2023-09-15 05:21:23','admin',NULL),(55,8,500,'Back-UPS ES700 BE700G-RS','ES700','','Интерфейсный порт (ы) USB\r\n405Ватт / 700ВА\r\n4 розетки с батарейной поддержкой, 4 - только фильтрация помех\r\nАккумуляторы RBC17 (151 х 100 х 65 мм) (12В, 7/9 Ач)',0,'',205,NULL,0,0,NULL,0,0,'2023-09-08 03:20:01',NULL,NULL),(56,1,504,'Neos CF201','Neos CF201','','Depo Neos CF201',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(57,10,186,'P5532 PTZ Network Camera','P5532','Видеонаблюдение Махачкала\r\nhttps://inventory.azimuth.holding.local/web/services/view?id=282','Цифровая, внутренняя\r\nPTZ: присутствует\r\nУгол обзора (гориз.): 53.1° – 2.0°\r\nРазрешение: 720x576\r\nПередача аудио в обе стороны',0,'',206,'',0,0,'',0,0,'2023-09-08 03:20:02',NULL,NULL),(58,10,186,'232D Network Dome Camera','232D','','цифровая, внутренняя\r\n18-кратный оптическим и 12-кратным цифровой зум\r\nУгол обзора: 2.8° - 48° \r\nРазрешение видео: 160х120 - 704х576 \r\nМаксимальная частота кадров 30/25 к/с \r\nАудио: нет, Микрофон: нет ',0,'',207,'',0,0,'',0,0,'2023-09-08 03:20:02',NULL,NULL),(59,8,500,'Back-Ups BK650EI CS 650','BK650EI','nix https://www.nix.ru/autocatalog/apc/UPS-650VA-Back-CS-APC-BK650EI-zashhita-telefonnoj-linii-USB_30169.html#','Мощность 400 Вт / 650 ВA\r\nаккумулятор 12В, 9 Ач\r\n3х розетки C13 с батарейной поддержкой \r\n1х розетки C13 с фильтрацией 1  \r\nпри нагрузке 100 Вт: 35 мин.\r\nпри нагрузке 200 Вт: 15 мин.\r\nпри нагрузке 300 Вт: 8 мин.\r\nБатарея 	RBC17',0,'',208,NULL,0,0,NULL,0,0,'2023-09-08 03:20:02',NULL,NULL),(60,8,500,'Smart-UPS SUA1000XL','','','Модель SMC2000I  \r\nPartNumber/Артикул Производителя SMC1500I  \r\nТип line-interactive  \r\nМощность (Вт) 1300 Вт \r\nМощность (ВА) 2000 ВA \r\nИнформационный LCD-дисплей есть  \r\nВходное напряжение 170-300В  \r\nЧастота входного напряжения 47-63Гц  \r\nВходной разъем IEC-320-C20  \r\nВыходные розетки типа IEC320, байпассные, с фильтрацией 6  \r\nВыходные розетки типа IEC320 С19, с батарейной поддержкой 1  \r\nНапряжение при питании от батареи 230 +/- 5% В \r\nЧастота при питании от батареи 50 Гц \r\n',0,'',NULL,'',0,0,'',0,0,'2023-09-05 14:10:25',NULL,NULL),(61,8,505,'Black Star 600 Plus','BS600Plus','','Производитель\r\nМакс. мощность 360 Ватт/600 ВА \r\nЕмкость батареи 7 Ач\r\nВых. на 2 евро-розетки\r\nВремя работы от аккумуляторов\r\n23мин 17c при нагрузке 30%\r\n11мин 49 c при нагрузке 50%\r\n8мин 15 c при нагрузке 80%\r\n6мин 28 c при нагрузке 100% \r\nРазмеры сменного аккумулятора (ШхВхГ)\r\n151 х94 х65 мм (12В, 7/9 Ач) \r\n\r\n',0,'',210,NULL,0,0,NULL,0,0,'2023-09-08 03:20:03',NULL,NULL),(62,7,499,'DGS-3100-24','DGS-3100','http://dlink.ru/ru/products/1/721.html','Управляемый стекируемый коммутатор 2 уровня с 20 портами \r\n10/100/1000Base-T + 4 комбо-портами 1000Base-T/SFP\r\n\r\n',0,'1\r\n2\r\n3\r\n4\r\n5\r\n6\r\n7\r\n8\r\n9\r\n10\r\n11\r\n12\r\n13\r\n14\r\n15\r\n16\r\n17\r\n18\r\n19\r\n20\r\n21\r\n22\r\n23\r\n24',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(63,8,500,'Back-UPS BE400-RS','BE400-RS','','Выходная мощность полная 400 В*А \r\nВыходная мощность активная 240 Вт Другие товары \r\nВремя переключения на батарею 10 мс \r\nХолодный старт Есть \r\nИндикация \r\nЗвуковой сигнал Есть \r\nБатарея \r\nКоличество батарей 1 \r\nВремя зарядки 960 мин \r\nЗащита батарей Автоматическое тестирование батарей \r\nЗащитные системы \r\nЗащита телефонной линии Есть \r\nЗащита локальной сети Есть \r\n Цвет Черный \r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(64,8,500,'Back-UPS BC650-RS','BC650-RS','','Выходная мощность полная 650 В*А \r\nВыходная мощность активная 390 Вт Другие товары \r\nТип выходных разъемов питания CEE 7 (евророзетка) Другие товары \r\nОбщее количество выходных разъемов питания 4 Другие товары \r\nВремя переключения на батарею 6 мс \r\nХолодный старт Есть \r\nФорма выходного сигнала Ступенчатая аппроксимация синусоиды Другие товары \r\nМаксимальная поглощаемая энергия импульса 273 Дж \r\nКоэффициент полезного действия 96.1 % \r\nКрест-фактор 3:1 \r\nУровень шума 45 дБ \r\nРазъемы \r\nИнтерфейс USB Есть \r\nИндикация \r\nОтображение информации Светодиоды \r\nЗвуковой сигнал Есть \r\nПитание \r\nМинимальное входное напряжение 160 В \r\nМаксимальное входное напряжение 278 В \r\nМинимальная входная частота 40 Гц \r\nМаксимальная входная частота 50 Гц \r\nМинимальная выходная частота 49 Гц \r\nМаксимальная выходная частота 61 Гц \r\nБатарея \r\nВремя зарядки 8 ч \r\nВозможность замены батарей Есть \r\nЗащитные системы \r\nЗащита от перегрузки Есть \r\nЗащита от высоковольтных импульсов Есть \r\nФильтрация помех Есть \r\nЗащита от короткого замыкания Есть \r\nПредохранитель Автоматический \r\nГабариты и вес \r\nВысота 20 см \r\nШирина 11.5 см \r\nГлубина 25.6 см \r\nВес 5.8 кг ',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(65,8,500,'Back-UPS CIS BX800CI-RS','BX800CI-RS','nix https://www.nix.ru/autocatalog/apc/UPS-800VA-Back-APC-BX800CI-RS-zashhita-telefonnoj-linii-USB_143438.html\r\nграфик работы от аккумуляторов https://www.apc.com/products/runtime_for_extendedruntime.cfm?upsfamily=29&ISOCountryCode=ru\r\n','line-interactive ИБП\r\nЕмкость батарей  9 Ач\r\nМощность 480 Вт / 800 ВA \r\nВремя работы при нагрузке 100 Вт: 26 мин.\r\nВремя работы при нагрузке 200 Вт: 10 мин.\r\nРозетки типа EURO/Schuko 4 шт.\r\nАккумулятор RBC17\r\n',0,'',212,NULL,0,0,NULL,0,0,'2023-09-08 03:20:04',NULL,NULL),(66,8,500,'Back-UPS BK650MI','BK650MI','','Максимальная задаваемая мощность(Вт) \r\n400Ватт / 650ВА\r\nТопология \r\nРежим ожидания\r\nВремя переключения \r\n4 ms typical : 8 ms maximum\r\nВход\r\nВходная частота \r\n50/60 Гц +/- 5 Гц Ручное переключение\r\nДиапазон входного напряжения при работе от сети \r\n160 - 286 Регулируем., 196 - 280В\r\nКоличество кабелей питания \r\n1\r\nБатареи и продолжительность автономной работы\r\nТип батарей \r\nСвинцово-кислотная батарея\r\nТиповое время перезарядки \r\n11часов\r\nСменная батарея \r\nRBC4\r\n\r\nhttps://www.apc.com/shop/ru/ru/products/APC-Back-UPS-650VA-230V/P-BK650MI',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:25',NULL,NULL),(67,11,499,'DIR-400','DIR-400','','Беспроводной 2,4 ГГц (802.11g) 4-х портовый маршрутизатор, до 108 Мбит/с\r\n\r\nhttp://dlink.ru/ru/products/5/760.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(68,8,500,'Smart-UPS 3000 SUA3000RMXLi3U','3000XL','','Выходная мощность 2.7кВт / 3.0кВА\r\nВысота стойки 3U\r\nВыходные соединители \r\n(8) IEC 320 C13 (Батарейное резервное питание)\r\n(2) IEC Jumpers (Батарейное резервное питание)\r\n(1) IEC 320 C19 (Батарейное резервное питание)\r\nНоминальное выходное напряжение 230V\r\nНоминальное входное напряжение 230V\r\nТип входного соединения \r\nBS1363A British, IEC 320 C20, Schuko CEE 7 / EU1-16P\r\nДлина шнура 1.8 м\r\n\r\nhttps://www.apc.com/shop/ru/ru/products/APC-Smart-UPS-XL-3000-3U-230-/P-SUA3000RMXLI3U',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(69,8,500,'Back-UPS BX700UI','BX700UI','apc https://www.apc.com/shop/ru/ru/products/-APC-Back-UPS-700-230-IEC/P-BX700UI','Мощность: 390Вт / 700ВА\r\nаккумулятор 12В, 7.2 Ач\r\n4x розетки C13 от батареи\r\nпри нагрузке 100 Вт: 22 мин.\r\nпри нагрузке 200 Вт: 8 мин.\r\nпри нагрузке 300 Вт: 3 мин.\r\nБатарея RBC110\r\n',0,'',214,NULL,0,0,NULL,0,0,'2023-09-08 03:20:05',NULL,NULL),(70,8,502,'Smart Winner 1000','ISW 1000','','Модель 1000  \r\nPartNumber/Артикул Производителя G2 1000 EURO  \r\nОсобенности Режим ECO; Оценка остаточной емкости батареи;  \r\nТип online  \r\nМощность (Вт) 900 Вт \r\nМощность (ВА) 1000 ВA \r\nИнформационный LCD-дисплей есть  \r\nИндикация состояния Режим от батареи - Сигнал каждые 4 секунды;  \r\nВходное напряжение 176-300В  \r\nЧастота входного напряжения 40-70Гц  \r\nВходной разъем IEC-320-C14  \r\nВыходные розетки типа EURO, с батарейной поддержкой 4  \r\nНапряжение при питании от батареи 220/230/240 +/- 1% В \r\n\r\nhttps://ippon.ru/catalog/item/smartwinner2012/',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(71,8,506,'POWER BACK BNB-400','EXEGATE','','Технические характеристики\r\n\r\nТип интерактивный\r\nВыходная мощность 400 ВА / 240 Вт\r\nФорма выходного сигнала ступенчатая аппроксимация синусоиды\r\nВремя переключения на батарею 10 мс\r\nКоличество выходных разъемов питания 2 (из них с питанием от батарей - 2)\r\nТип выходных разъемов питания CEE 7 (евророзетка)\r\n\r\nВход / Выход\r\n\r\nНа входе 1-фазное напряжение\r\nНа выходе 1-фазное напряжение\r\nВходное напряжение 145 - 290 В\r\nСтабильность выходного напряжения (батарейный режим) ± 10 %\r\n\r\nФункциональность\r\n\r\nОтображение информации - светодиодные индикаторы\r\nЗвуковая сигнализация - есть\r\n\r\nБатарея\r\n\r\nВремя зарядки 8 час\r\n\r\nЗащита\r\n\r\nЗащита от перегрузки - есть\r\nЗащита от высоковольтных импульсов - есть\r\nФильтрация помех - есть\r\nЗащита от короткого замыкания - есть\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(72,8,500,'Back-UPS BK500MI','BK500MI','','Максимальная задаваемая мощность(Вт) 300Ватт / 500ВА\r\nВремя переключения 4 ms typical : 8 ms maximum\r\nВходная частота 50/60 Гц +/- 5 Гц Ручное переключение\r\nДиапазон входного напряжения при работе от сети 196 - 280В\r\nКоличество кабелей питания 1\r\nТип батарей Свинцово-кислотная батарея\r\nТиповое время перезарядки 7часов\r\nСменная батарея RBC2\r\n\r\nhttps://www.apc.com/shop/ru/ru/products/-APC-BK-500-230-/P-BK500MI',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(73,8,500,'Back-UPS BX950UI','BX950UI','apc https://www.apc.com/shop/ru/ru/products/-APC-Back-UPS-950-230-IEC/P-BX950UI\r\nnix https://www.nix.ru/autocatalog/apc/UPS-950VA-Back-APC-BX950UI-zashhita-telefonnoj-linii-USB_208024.html#','Мощность 480 Вт / 950 ВA \r\n1 аккумулятор 12В, 9 Ач\r\n6х розеток C13 от батареи\r\nБатарея RBC17\r\nпри нагрузке 100 Вт: 24 мин.\r\nпри нагрузке 200 Вт: 10 мин.\r\nпри нагрузке 300 Вт: 5 мин.',0,'',216,NULL,0,0,NULL,0,0,'2023-09-08 03:20:07',NULL,NULL),(74,8,507,'BRICs LCD BR850ELCD','BR850ELCD','CyberPower https://www.cyberpower.com/au/en/product/sku/br850elcd#specification\r\nnix https://www.nix.ru/autocatalog/cyberpower/UPS-850VA-CyberPower-BRICs-LCD-BR850ELCD-Black-zashhita-telefonnoj-linii-RJ45-USB-plus-USB-dlya-zaryadki-mobustrojstv_77998.html#','Мощность 510Ватт / 850ВА\r\nАккумулятор 12В, 9 Ач\r\n8 минут на нагрузке 50%\r\n4х евро-розетки от батареи\r\n4х евро-розетки только фильтрация\r\n\r\n\r\n',0,'',218,NULL,0,0,NULL,0,0,'2023-09-08 03:20:09',NULL,NULL),(75,8,500,'Back-UPS BX650CI-RS','BX650CI','APC https://www.apc.com/shop/ru/ru/products/-APC-Back-UPS-650-230-Schuko-/P-BX650CI-RS','Line Interactive\r\nМощность: 390Вт / 650ВА\r\nЕмкость: 1 батарея 12В, 7.2/9 Ач\r\nПри нагрузке 100 Вт: 22 мин.\r\nПри нагрузке 200 Вт: 8 мин.\r\nСменная батарея APCRBC110',0,'',219,NULL,0,0,NULL,0,0,'2023-09-08 03:20:10',NULL,NULL),(76,8,508,'5SC 500i','5SC 500i','','Основные характеристики Eaton 5SC500i: \r\nДиапазон входного напряжения без перехода на батареи при 100% нагрузке 184-276В \r\nДиапазон частоты 45-55 Гц \r\nНоминальное напряжение 230V (+6/–10 %)  (регулируется 220 В / 230 В / 240 В) \r\nНоминальная выходная частота 50/60 Гц +/- 0,1 % (автоопределение) \r\nРозетки на выходе (4) IEC-320-C13 \r\nКоммуникации   \r\nПорт RS-232 Есть \r\nПорт USB Есть (HID) \r\nСлот для дополнительных карт нет \r\nАвтоматическое тестирование батарей есть \r\nЗащита от полного разряда Есть \r\nОкружающая среда   \r\nШум < 40 dB \r\nРабочая температура От 0 до 35°C \r\nБезопасность МЭК/EN 62040-1, UL 1778 \r\nЭлектромагнитная совместимость МЭК//EN 62040-2, МЭК//EN 62040-3 (характеристики) \r\nПодтверждения CE, отчёт CB, TÜV \r\n\r\nhttps://eaton-powerware.ru/5SC500i.html\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(77,7,499,'DES-3028','DES-3028','http://dlink.ru/ru/products/1/706.html','Управляемый коммутатор 2 уровня с 24 портами 10/100 Мбит/с + 2 портами 1000BASE-T + 2 комбо-портами 1000BASE-T/SFP\r\n\r\n',0,'1\r\n2\r\n3\r\n4\r\n5\r\n6\r\n7\r\n8\r\n9\r\n10\r\n11\r\n12\r\n13\r\n14\r\n15\r\n16\r\n17\r\n18\r\n19\r\n20\r\n21\r\n22\r\n23\r\n24\r\n25\r\n26\r\n27\r\n28',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(78,7,499,'DES-1024D','DES-1024D','','Неуправляемый коммутатор DES-1024D с 24 портами 10/100Base-TX представляет собой недорогое решение для сетей SOHO и предприятий малого и среднего бизнеса (SMB). Каждый порт коммутатора обеспечивает передачу файлов и потокового мультимедиа на скорости до 200 Мбит/с в режиме полного дуплекса без задержек. DES-1024D поддерживает технологию Plug-and-play, позволяющую подключать к нему устройства без произведения дополнительных настроек.\r\n\r\nhttp://dlink.ru/ru/products/1/2249.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(79,7,499,'DES-1016D','DES-1016D','','Неуправляемый коммутатор DES-1016D с 16 портами 10/100Base-TX представляет собой недорогое решение для сетей SOHO и предприятий малого и среднего бизнеса (SMB). Каждый порт коммутатора обеспечивает передачу файлов и потокового мультимедиа на скорости до 200 Мбит/с в режиме полного дуплекса без задержек. DES-1016D поддерживает технологию Plug-and-play, позволяющую подключать к нему устройства без произведения дополнительных настроек\r\n\r\nhttp://dlink.ru/ru/products/1/2248.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(80,6,112,'imageRUNNER 2018','IR2018','support https://www.canon.ru/support/products/imagerunner/ir2018.html?type=drivers&language=ru','Ч/Б МФУ формат А3, черно-белый\r\nСетевой, лазерный\r\nПоддержка двусторонней печати\r\nСканер планшетный двусторонний\r\nСканирование в SMB, FTP и Почту\r\nДопустимая нагрузка 5000 стр. в месяц\r\nКартридж C-EXV14 (черно-белый, 8300 страниц)\r\nОчевидное расположение серийного номера\r\n\r\n\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(81,6,35,'LaserJet Pro M1212nf','M1212NF MFP','https://support.hp.com/ru-ru/drivers/selfservice/hp-laserjet-pro-m1212nf-multifunction-printer-series/3965847/model/3965848','принтер/сканер/копир/факс\r\nЦветность печати - черно-белая\r\nТехнология печати - лазерная\r\nМаксимальный формат - A4\r\nКартридж HP 85А,CE285A\r\n\r\nТип сканера- планшетный/протяжный\r\nМаксимальный формат оригинала- A4\r\nУстройство автоподачи оригиналов - одностороннее\r\nЕмкость устройства автоподачи оригиналов - 35 листов\r\nПоддержка стандартов TWAIN, WIA\r\n\r\nЛотки\r\nПодача бумаги 150 лист. (стандартная)\r\nВывод бумаги 100 лист. (стандартный)\r\n\r\nИнтерфейсы Ethernet (RJ-45), USB 2.0\r\n\r\nПоддержка PostScript нет\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(82,6,35,'LaserJet M1522nf','M1522NF','https://support.hp.com/ru-ru/product/hp-laserjet-m1522-multifunction-printer-series/3442750/model/3442754/product-info','Принтер/сканер/копир/факс\r\nЦветность печати - черно-белая, лазерная\r\nМаксимальный формат A4\r\nТип сканера - планшетный/протяжный\r\nМаксимальный формат оригинала A4\r\nУстройство автоподачи оригиналов - одностороннее\r\nТип картриджа/тонера - черный HP LaserJet CB436A\r\nИнтерфейсы - Ethernet (RJ-45), USB\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(83,6,35,'LaserJet Pro M1132','LJ M1132','https://support.hp.com/ru-ru/drivers/selfservice/HP-LaserJet-M1132-Multifunction-Printer/3965842/model/3965843','МФУ, формат до A4, черно-белый, лазерный\r\nСетевой\r\nЕсть поддержка двусторонней печати\r\nСканирование планшетное\r\nСканирование в SMB, FTP и Почту\r\nДопустимая нагрузка по печати 8000 стр/мес\r\nКартридж  CE285A, НР 85А (черно-белый, 1600 стр)\r\nРасположение серийного номера сзади',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(84,6,35,'LaserJet Pro 400 M425dn','M425dn','https://support.hp.com/ru-ru/drivers/selfservice/hp-laserjet-pro-400-mfp-m425/5096243/model/5096244','МФУ, формат A4, черно-белый\r\nСетевой\r\nЕсть поддержка двусторонней печати\r\nСканирование с податчика и планшетное.\r\nЕсть поддержка двустороннего сканирования\r\nСканирование в SMB, FTP и Почту\r\nДопустимая нагрузка по печати 50000 стр/мес\r\nМодель картриджа: CF280A (черно-белый, 2700 стр)\r\nРасположение серийного номера сзади \r\n\r\n\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(85,6,112,'imageRUNNER 1600','iR 1600','','МФУ, формат A3 и A4, черно-белый\r\nСетевой\r\nЕсть поддержка двусторонней печати\r\nСканирование с податчика.\r\nПоддержка двустороннего сканирования есть\r\nСканирование в SMB, FTP и Почту\r\nДопустимая нагрузка по печати 10000 стр/мес\r\nМодель картриджа C-EXV5 (черно-белый, 15700 стр/мес)\r\nРасположение серийного номера сзади',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(86,6,35,'LaserJet Pro MFP M477fnw','M477fnw','','МФУ, формат A4, цветной\r\nСетевой\r\nЕсть поддержка двусторонней печати\r\nСканирование с податчика и планшетное.\r\nЕсть поддержка двустороннего сканирования.\r\nСканирование в SMB, FTP и Почту\r\nДопустимая нагрузка по печати 50000 стр/мес\r\nМодели картриджей: черный HP410A (CF410A), HP410X (CF410X)(6500 стр.), голубой HP410A (CF411A), HP410X (CF411X)(5000 стр.), желтый HP410A (CF412A), HP410X (CF412X)(5000 стр.), пурпурный HP410A (CF413A), HP410X (CF413X)(5000 стр.)\r\nРасположение серийного номера сзади\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(87,6,35,'LaserJet M1120','LJ M1120','Поддержка HP https://support.hp.com/ru-ru/drivers/selfservice/hp-laserjet-m1120-multifunction-printer-series/3447595/model/3447596','МФУ, формат бумаги до A4, черно-белый\r\nСетевой\r\nЕсть поддержка двусторонней печати\r\nСканирование планшетное\r\nСканирование в SMB, FTP и Почту\r\nДопустимая нагрузка по печати 8000 стр/мес	 \r\nМодель картриджа CB436A (черно-белая, 2000 стр)\r\nРасположение серийного номера сзади',0,'',221,NULL,0,0,NULL,0,0,'2023-09-08 03:20:11',NULL,NULL),(88,6,99,'TASKalfa 4012i','TASKalfa 4012i','','Серия	 TASKalfa	 \r\nТип принтера	 лазерный\r\nНагрузка на принтер	 100000 стр/мес	 \r\nТип печати	 монохромная	 \r\nРазрешение печати	 1200х1200 т/д	 \r\nМакс. размер бумаги	 A3	 \r\nСкорость печати текста до	 40 стр/мин	 \r\nВстроенная память (ROM)	?    2048 МБ	 \r\nВоз-ть увеличения памяти	 Да	 \r\nКопирование без компьютера	 Да	 \r\nСкорость копирования	 36 стр/мин	 \r\nМакс. разрешение копира	 1200x1200 т/д	 \r\nМасштабирование	 25 - 400 %	 \r\nТип сканера	 планшетный	 \r\nОбласть сканирования	 A3	 \r\nСканирование на USB-накопитель	 Да	 \r\nОптическое разреш. сканера	 600x600 т/д	 \r\nДиагональ дисплея	 9 \"	 \r\nТип дисплея	 цветной	 \r\nСенсорный экран	 Да	 \r\nПоддержка Apple AirPrint	 Да	 \r\nЁмкость лотка для подачи бумаги	 500/500 листов	 \r\nЁмкость лотка приоритет. подачи	 100 лист.	 \r\nПорт USB 2.0 тип A	 1 шт	 \r\nВход LAN (RJ-45)	 1 шт	 \r\nИнтерфейс связи с ПК	 USB 2.0; LPT	 \r\nТип сетевой карты	 10/100 Fast Ethernet	 \r\nКартридж №1	 TK-7225 \r\n\r\nhttps://www.kyoceradocumentsolutions.ru/ru/products/mfp/TASKALFA4012I.html',0,NULL,222,NULL,0,0,NULL,0,0,'2023-09-08 03:20:11',NULL,NULL),(89,8,500,'SMT3000RMI2U','','График времени работы от нагрузки https://www.apc.com/products/runtimegraph/runtime_graph.cfm?base_sku=SMT3000RMI2U&chartSize=large\r\nОф. страница https://www.apc.com/shop/ru/ru/products/APC-Smart-UPS-3000-2U-230-/P-SMT3000RMI2U\r\nБатарейная сборка RBC43 https://www.apc.com/shop/ru/ru/products/-APC-43/P-RBC43','Управляемый, линейно-интерактивный ИБП\r\nМакс мощность 2700Вт, емкость 547 ВАч\r\nРаботает 17мин при 1000Вт, 38мин при 500Вт\r\nДоп. батарейные модули не поддерживаются\r\n8 розеток IEC 320 C13\r\nБатарейная сборка RBC43, 480ВАч\r\nУправление ИБП через порт USB',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(90,7,499,'DGS-1210-10/ME','DGS-1210-10/ME','','Управляемый L2 коммутатор с 8 портами 10/100/1000Base-T и 2 портами 1000Base-X SFP\r\n\r\nhttp://dlink.ru/ru/products/1/1980.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(91,7,499,'DES-1026G','DES-1026G','http://dlink.ru/ru/products/1/1876.html','Неуправляемый коммутатор с 24 портами 10/100Base-TХ и 2 комбо-портами 100/1000Base-T/SFP\r\n\r\n',0,'1\r\n2\r\n3\r\n4\r\n5\r\n6\r\n7\r\n8\r\n9\r\n10\r\n11\r\n12\r\n13\r\n14\r\n15\r\n16\r\n17\r\n18\r\n19\r\n20\r\n21\r\n22\r\n23\r\n24\r\n25 Combo\r\n26 Combo',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(94,7,499,'DES-1005D','DES-1005D','','Неуправляемый коммутатор с 5 портами 10/100Base-TX\r\n\r\nhttp://dlink.ru/ru/products/1/1949.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(95,7,499,'DES-1008D','DES-1008D','','Неуправляемый коммутатор с 8 портами 10/100Base-TX\r\n\r\nhttp://dlink.ru/ru/products/1/1950.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(96,7,499,'DGS-1008TL','DGS-1008TL','','Неуправляемый коммутатор с 8 медными портами Gigabit Ethernet\r\n\r\nhttp://dlink.ru/ru/products/1/96.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(97,7,499,'DGS-1008P','DGS-1008P','','Неуправляемый коммутатор с 8 портами 10/100/1000Base-T (4 порта PoE 802.3af/at, PoE‑бюджет 68 Вт)\r\n\r\nhttp://dlink.ru/ru/products/1/2297.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(98,7,499,'DES-3528','','','Управляемый L2 стекируемый коммутатор с 24 портами 10/100Base-TX, 2 портами 10/100/1000Base-T и 2 комбо-портами 100/1000Base-T/SFP\r\n\r\nhttp://dlink.ru/ru/products/1/1054_b.html',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(99,2,20,'1608-I','1608-I','https://wan-port.ru/700415557?yclid=5584318675439102238','Цепляется только к АТС Avaya\r\nДисплей  3.5\" (3 строки по 24 символа) \r\nPoE 802.3af (класс 2) Потребляемая мощность 7W\r\nУмеет провижн\r\nНе умеет отказоустойчивость\r\nУмеет в бридж\r\n',0,'LAN\r\nPC',223,'',0,0,'',0,0,'2023-09-08 03:20:12',NULL,NULL),(100,1,509,'Veriton N281G','N281G','На сайте Acer https://www.acer.com/ac/ru/RU/content/support-product/3235?b=1','Процессор: Intel Atom D425 (1.8 ГГц, 1 ядро, 10 Вт) \r\nПамять: 2 ГБ DDR3 расширяется до 4 Гб  \r\nЧипсет: Intel NM10 Express  \r\nВидео: Intel GMA 3150  \r\nHDD: SATA 160 Гб   \r\nWi-Fi: 802.11n,g,b\r\nEthernet: 1000Mbit/s\r\nMMC, SD, xD, MS  \r\nПорты: 6 x USB 2.0, RJ-45, COM, VGA (15-pin D-SUB)  \r\nРазъем 3.5 мм для наушников, разъем 3.5 мм для микрофона,\r\nОперационная система  Windows 7 Professional  \r\n',0,'',224,NULL,0,0,NULL,0,0,'2023-09-08 03:20:12',NULL,NULL),(101,1,504,'Neos 400Mini','Neos 400Mini','','Эргономичный высокопроизводительный бесшумный ПК является более удобным и выгодным по сравнению с моноблочным компьютером. Благодаря поддержке Vesa Мount/ возможно крепление ПК под столом, на монитор или между монитором и дополнительной подставкой',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(102,1,55,'EeeBox PC EB1502','EB1502','','OS Подлинная Windows® XP Home\r\nCPU Intel® Atom™ N270\r\nMemory 2 x So-DIMM Slots, DDR2-800 1G\r\nHDD SATA 2.5\" 160GB\r\nOptical Drive Slim Slot-in Super Multi DVD-RW\r\nChipset NVIDIA® ION™ LE\r\nGraphics NVIDIA® ION™ LE\r\nLAN 10/100/1000 Mbps\r\nWireless 802.11b/g/n\r\n\r\nFront Panel\r\n•Slot-in Super Multi DVD-RW \r\n•Card Reader x 1 \r\n•USB 2.0 x 2 \r\n•Headphone-out jack x 1 \r\n•MIC x 1\r\n\r\nRear Panel\r\n•Wi-Fi antenna x 1 (built-in) \r\n•USB 2.0 x 4 \r\n•D-Sub x 1 \r\n•HDMI out x 1 \r\n•eSATA x1 \r\n•Audio out (S/PDIF out) jack x1 \r\n•Giga Lan x 1\r\n\r\nPower Supply\r\n19Vdc, 3.42A, 65W Power Adaptor\r\n\r\nhttps://www.asus.com/ru/Mini-PCs/EeeBox_PC_EB1502/specifications/\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(103,1,35,'EliteOne 1000 G2 27','HP EliteOne','','•Windows 10 Pro 64\r\n•Процессор Intel® Core™ i5 8-го поколения\r\n•16 Гбайт памяти; твердотельный накопитель, 512 Гбайт\r\n•16 Гбайт (1 x 16 Гбайт) DDR4-2666 SDRAM 1 x 16 GB\r\n•Широкоформатный ЖК-дисплей 4K IPS (3840 × 2160) диагональю 68,58 см (27\") с антибликовым покрытием и белой светодиодной подсветкой [3,4,5]\r\n•Встроенный Графический адаптер Intel® UHD Graphics 630\r\n\r\nhttps://www8.hp.com/ru/ru/desktops/product-details/23431004',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(104,1,55,'EeeBox PC EB1012P','EB1012P','Сайт ASUS https://www.asus.com/ru/Mini-PCs/EeeBox_PC_EB1012P/specifications/','Процессор: Intel Atom D510 Dual Core\r\nЧипсет: Intel NM10\r\nПамять: 2 ГБ DDR2 (до 4 ГБ SO-DIMM 800 МГц)\r\nHDD SATA 250 ГБ\r\nWi-Fi: 802.11 b/g/n\r\nEthernet: 1Gbit/s\r\nКартридер: SD/SDHC/SDXC/MMC\r\n\r\nРазъемы на боковой панели\r\n1x USB 3.0, 1x e-SATA\r\nРазъемы на передней панели\r\n1x USB 3.0, 2x USB 2.0, Card Reader, Mic, Headphone\r\nРазъемы на задней панели\r\nVGA, HDMI, 2x USB 2.0, RJ45\r\n\r\nОC: Windows 7 Домашняя расширенная \r\nЭлектропитание - 65 Вт блок питания\r\n',0,'',225,NULL,0,0,NULL,0,0,'2023-09-08 03:20:13',NULL,NULL),(105,12,510,'TS-453 Pro','TS-453 Pro','Описание на сайте https://www.qnap.com/en/product/ts-453%20pro\r\nСпеки https://www.qnap.com/en/product/ts-453%20pro/specs/hardware\r\nПолка расширения UX-800P https://qnap.ru/ux-800p','Софтверный NAS (1 контроллер)\r\nCPU: Celeron J1900@2GHz, (4 cores, Boost to 2,41GHz)\r\nRAM - 2 Гб (DDR3) (может ставиться от 2 до 8 Гб)\r\nFlash Memory - 512 Мb (под систему)\r\nHDD: 4 × 3,5\"/2,5\" HDD/SDD SATA III (up to 8Gb) Hot swap bay\r\nNET: 4x RJ-45 1000Mbit ethernet\r\nPorts: 3× USB 3.0, 2× USB 2.0, HDMI\r\nMax capacity: 32 Tb (up to 72 with expansion UX-800P)\r\nSize: 177 × 180 × 235 mm',1,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(106,3,496,'FusionServer CH121 V5','CH121 V5','Описание на сайте https://e.huawei.com/kz/products/servers/e-series/ch121-v5-node\r\nОписание платформы E9000 https://e.huawei.com/kz/material/datacenter/server/8d00ee21c8ab4dda93368b4f0e60e814','Двухсокетный узел блейд-серверов для корзины E9000\r\nДля установки в слот половинной ширины\r\nCPU: Up to 2x Intel® Xeon® Scalable Up to (205Watt TDU)\r\nRAM: Up to 24x DIMM DDR4, 2666 MT/s (MegaTransactions/sec)\r\nDrives: Up to 2x 2,5\" HDD/SSD SAS/SATA (RAID 0,1)\r\n    2x NVMe SSD / 4x M.2 SSD (SATA) (RAID 0,1,10,5,6)\r\n2 mezonine slots PCIe x16\r\n1 half-size PCIe x16 (frontal access)\r\nSize: 60,46mm × 210mm × 537,2mm',1,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(107,9,501,'UniFi AP AC Lite','','страничка модели https://www.ui.com/unifi/unifi-ap-ac-lite/\r\ndatasheet https://dl.ubnt.com/datasheets/unifi/UniFi_AC_APs_DS.pdf','Для установки внутри помещений\r\nСтандарты: 802.11 a/b/g/n/r/k/v/ac 2.4GHz, 5GHz\r\nПропуск. способность: 867Mbps (5GHz), 300 Mbps(2.4GHz)\r\nПитание по PoE:\r\n - 802.3af/A PoE\r\n - 24V Passive PoE\r\nМаксимальное энергопотребление: 6.5W\r\nИнжектор в комплекте: 24V, 0.5A Gigabit PoE Adapter',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:26',NULL,NULL),(108,9,501,'UniFi AP AC Mesh PRO','UAP-AC-M-PRO','datasheet https://dl.ubnt.com/datasheets/unifi/UniFi_AC_Mesh_DS.pdf\r\nquickstart https://www.ui.com/download/unifi/unifi-mesh/default/unifi-ac-mesh-datasheet\r\ndownloads https://www.ui.com/download/unifi/unifi-mesh','Для установки вне помещений\r\nСтандарты: 802.11 802.11a/b/g/n/ac 2.4 GHz / 5 GHz\r\nПропуск. способность: 1300Mbps (5GHz), 450 Mbps(2.4GHz)\r\nПитание по PoE: 802.3af PoE (44 - 57V)\r\nМаксимальное энергопотребление: 9W\r\nИнжектор в комплекте: Gigabit PoE 48 В, 0.5 А\r\n(инжектор не поставляется в комплектах из нескольких точек)\r\n',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(109,3,496,'5288 V5','5288v5 Backup Srv','support https://support.huawei.com/enterprise/en/intelligent-servers/5288-v5-pid-22315644','Серверная платформа 4U\r\nCPU: up to 2 Intel Xeon (205W summary)\r\nRAM: 24 DDR4 DIMM 2666/2933\r\nNetwork: 2 х 10GE + 2GE\r\nPower: 2x Hot Spare PSU\r\nPCI: 10 х PCIe 3.0 slots\r\nup to 24 x HDD 3,5\" SAS/SATA front pane\r\nup to 16 х HDD 3,5\" rear pane\r\nup to 4 х HDD 3,5\" internal',1,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(110,3,496,'CH121 V5 Blade','Huawei CH121 V5 Blade','','Блейд-серверы второй волны закупок - для Jira/Exchange',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(111,3,35,'ProLiant DL380 Gen10','DL380 G10','quick specs https://h20195.www2.hpe.com/v2/getpdf.aspx/a00008180ENUS.pdf?ver=1','2U Rack\r\nHDD: Up to 30SFF / 19LFF\r\nCPU: 2 Sockets: 1st & 2nd Generation Intel® Xeon® Scalable Processor Family\r\nMB: Intel® Intel C621 Chipset\r\nRAM: 24 DDR4 Slots (RDIMM/LRDIMM/NVDIMM/Intel Optane PMEM)\r\nNET: 4 NICs',1,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(112,13,35,'Probook 450 G7','HP 450 G7','','Model ID: 8VU93EA#ACB\r\n\r\nWindows 10 Pro (64-разрядная)\r\nIntel® Core™ i5 10-го поколения (i5-10210U)\r\n4 Гбайт памяти; твердотельный накопитель, 256 Гбайт\r\nПамять DDR4-2666 SDRAM, 4 Гбайт (1 x 4 Гбайт) Скорость передачи данных до 2666 МТ/с.\r\nHD (1366 x 768), диагональ 39,6 см (15,6\"), антибликовое покрытие, 220 нит, NTSC 45% [14,15,16,17,39]\r\nВстроенный Intel® UHD Graphics 620 Дискретный NVIDIA® GeForce® MX130 (2 Гбайт выделенной памяти DDR5)\r\n\r\nhttps://www8.hp.com/ru/ru/laptops/product-details/34316836',0,NULL,NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(113,14,82,'Пилон','Пилон','','Состоит из Неттопа и камер(для сканирования РОГ)',0,'ethernet1',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(114,14,511,'Сетевой контроллер NC-8000','NC-8000','','Сетевой контроллер NC-8000',0,'ethernet1\r\n\r\n',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(115,7,512,'MS105','Mercusys ms105','','Неуправляемый коммутатор',0,'1\r\n2\r\n3\r\n4\r\n5',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(116,6,99,'TASKalfa 3253ci','TASKalfa 3253','Характеристики https://www.nix.ru/autocatalog/printers_mfu_kyocera/Kyocera-TASKalfa-3253ci-A3-32-str-min-4Gb-plus-SSD32Gb-LCD-USB20-setevoj-bez-kryshki-dvustpechat_414390.html','Большой, черного цвета, напольный, А3 цвет, двусторонний. \r\nКартриджи:\r\nTK-8335K - черный (ресурс 25000)\r\nTK-8335C - голубой (ресурсы цветных по 15000)\r\nTK-8335M - пурпурный\r\nTK-8335Y - желтый\r\nпароль для  WEB интерфейса Admin/Admin\r\nпароль для входа в сервисное меню аппарата 3200/3200',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(117,6,112,'imagePROGRAF iPF770','iPF770','Описание https://www.canon.ru/for_work/products/professional_print/large_format/ipf770/specification.html','Цветной, струйный, рулонный.\r\nКартриджи 130мл:\r\nPFI-107BK - черный\r\nPFI-107MBK - черный матовый 2 штуки!\r\nPFI-107C - голубой\r\nPFI-107M - пурпурный\r\nPFI-107Y - желтый\r\nЕсть увеличенные картриджи 300мл:\r\nPFI-207 теже самые по цвету\r\n\r\nпечатающая головка: PF-04\r\nКартридж для отработанных чернил: MC-10\r\nПароль или пустой или canon или admin\r\n\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(118,6,206,'Oce PlotWave 300','Oce PlotWave 300','Описание https://lekom.ru/knowas/tech/shirokoformat/oce_plotwave300.html','Ч/б, лазерный, рулонный. Есть сканер А0.\r\nКартридж OCE 1060074426 - подходит от 350 модели!\r\ndefaultsystem administrator password is SysAdm\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(119,7,513,'AJ820A - B-series 8/12c SAN Switch for BladeSystem c-Class','AJ820A-24port','Tech specs https://h10057.www1.hp.com/ecomcat/hpcatalog/specs/provisioner/05/AJ820A.htm\r\nhttps://tiscom.ru/blade/hp-812824-8-gb-san-switch-brocade','Управляемый FC коммутатор, для установки в блейд-системы компании HP\r\n24 порта 8Gb FC\r\nВнутренняя шина 384Gb полностью неблокируемая,\r\nГорячее подключение к шасси\r\nВозможность использования всеми вычислительными лезвиями.\r\nКоммутатор может быть приобретен в варианте \r\n * с 12 активными портами (12 портов в любой комбинации, HP 8/12) и \r\n * с 24 активными портами (16 внутренних и 8 наружных, HP 8/24). В последствии вариант с 12-ю активными портами может быть модернизирован до 24 портов путем покупки программной опции.\r\n\r\nИнформация о совместимости:\r\n * Сервера : Все сервера HP Proliant BLc\r\n * Шасси: HP BladeSystem c7000 и c3000\r\n * Карты Mezzanine:\r\n    * Brocade 804 8Gb FC HBA\r\n    * Emulex LPe 1205-HP 8Gb FC HBA\r\n    * Emulex LPe1105-HP 4Gb FC HBA\r\n    * QLogic QMH2562 8Gb FC HBA\r\n    * QLogic QMH2462 4Gb FC HBA\r\n\r\nИнформация для заказа:\r\n * HP B-Series 8/12c SAN Switch BladeSystem c-Class (1) 8 Gb SAN Switch; 12 ports enabled for any combination (internal and external); two (2) short wave 8 Gb SFP+s; full fabric connectivity; documentation AJ820A\r\n * HP B-Series 8/24c SAN Switch BladeSystem c-Class (1) 8 Gb SAN Switch; 24 ports enabled (16 internal, 8 external); four (4) short wave 8 Gb SFP+s; full fabric connectivity; documentation AJ821A\r\n * HP B-Series 8/24c SAN Switch Pwr Pk+ BladeSystem c-Class (1) 8 Gb SAN Switch; 24 ports enabled (16 internal, 8 external); four (4) short wave 8 Gb SFP+s; full fabric connectivity; documentation and includes Power Pack+ Bundle and management tools (Adaptive Networking, ISL Trunking, Advanced Performance Monitoring, Server Application Optimization (SAO), Extended Fabrics, Fabric Watch) AJ822A\r\n\r\nSFP:\r\n  * HP 8Gb SW B-series FC SFP+ AJ716A\r\n  * HP 8Gb Long Wave B-series 10km FC SFP+ 1Pack AJ717A\r\n  * HP 8Gb Long Wave B-series 25km FC SFP+ 1Pack AW538A\r\n  * HP 4Gb SW B-series FC SFP AJ715A\r\n  * HP 4Gb LW B-series 10km FC SFP AK870A\r\n  * HP 4Gb LW B-series 35km FC SFP 1 Pack AN211A',0,'17\r\n18\r\n19\r\n20\r\n21\r\n22\r\n23\r\n0',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(120,7,35,'ProCurve 6120XG Blade Switch (516733-B21)','ProCurve 6120XG','quick specs https://cdn.cnetcontent.com/syndication/feeds/hp-ent/inline-content/UZ/9/0/908AF44CD537F7643E5A8E47CA2D6B1C393AE9C3_source.PDF','Управляемый Блейд-коммутатор\r\n * для установки в полки c-Class BladeSystem и HP Integrity Superdome 2\r\n * 16 10Gbit портов Downlink (для лезвий)\r\n * 8 10Gbit SFP+ портов Uplink (2комбо CX4/SFP+)\r\nУправление через CLI/HTTP',0,'17\r\n18\r\n19\r\n20\r\n21\r\n22\r\n23\r\n24',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(121,7,496,'CX311 Switch Module V100R001C00','CX311','User guide https://support.huawei.com/enterprise/ru/doc/EDOC1000018097','Управляемый коммутатор для блейд систем E9000\r\n * 16 портов 10GE (Uplink)\r\n * 8 портов 8G FC\r\n * 32 порта 10GE (Downlink к лезвиям)\r\nСетевые порты нумеруются в зависимости от порта установки самого коммутатора.\r\nВ корзине есть порты 1E,2X,3X,4E. \r\nПорты установленные в порт 2X будут номероваться 10GE2/17/1-16',0,'10GE /17/1\r\n10GE /17/2\r\n10GE /17/3\r\n10GE /17/4\r\n10GE /17/5\r\n10GE /17/6\r\n10GE /17/7\r\n10GE /17/8\r\n10GE /17/9\r\n10GE /17/10\r\n10GE /17/11\r\n10GE /17/12\r\n10GE /17/13\r\n10GE /17/14\r\n10GE /17/15\r\n10GE /17/16\r\nExt1:0\r\nExt2:1\r\nExt3:2\r\nExt4:3\r\nExt5:4\r\nExt6:5\r\nExt7:6\r\nExt8:7',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(122,6,35,'Color LaserJet CM2320fxi MFP','HP Color LaserJet CM2320','https://support.hp.com/ru-ru/document/c04419733?jumpid=reg_r1002_ruru_c-001_title_r0003','МФУ, А4, Цветной\r\nСетевой: RJ-45\r\nПечать: 2-сторонняя\r\nСканер: Податчик с 1-сторонним сканированием\r\nМодель картриджа: черный CC530A, голубой CC531A, пурпурный CC533A, желтый CC532A',0,'RJ-45, USB 2.0',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(123,6,35,'LaserJet 5200dtn','hp LaserJet 5200dtn','','Устройство\r\nпринтер\r\nЦветность печати\r\nчерно-белая\r\nТехнология печати\r\nлазерная\r\nРазмещение\r\nнастольный\r\nОбласть применения\r\nсредний офис\r\nКоличество страниц в месяц\r\n65000\r\nПринтер\r\nМаксимальный формат\r\nA3\r\nАвтоматическая двусторонняя печать\r\nесть\r\nМаксимальное разрешение для ч/б печати\r\n1200x1200 dpi\r\nСкорость печати\r\n35 стр/мин (ч/б А4)\r\nВремя выхода первого отпечатка\r\n10 c (ч/б)\r\nЛотки\r\nПодача бумаги\r\n850 лист. (стандартная)\r\nВывод бумаги\r\n250 лист. (стандартный)\r\nРасходные материалы\r\nПлотность бумаги\r\n60-199 г/м2\r\nПечать на:\r\nпленках, этикетках, глянцевой бумаге, конвертах, матовой бумаге\r\nРесурс ч/б картриджа/тонера\r\n12000 страниц\r\nКоличество картриджей\r\n1\r\nТип картриджа/тонера\r\nQ7516A\r\nПамять/Процессор\r\nОбъем памяти\r\n128 МБ, максимальный 512 МБ\r\nПроцессор\r\nMIPS\r\nЧастота процессора\r\n460 МГц\r\nИнтерфейсы\r\nИнтерфейсы\r\nLPT, Ethernet (RJ-45), USB\r\nВерсия USB\r\n2.0\r\nЧисло слотов расширения\r\n1\r\nШрифты и языки управления\r\nПоддержка PostScript\r\nесть\r\nПоддержка\r\nPostScript 3, PCL 5e, PCL 6\r\nКоличество установленных шрифтов PostScript\r\n93\r\nКоличество установленных шрифтов PCL\r\n103\r\nДополнительная информация\r\nПоддержка ОС\r\nWindows, Mac OS\r\nМинимальные системные требования\r\nIntel Pentium + 16 Mb RAM\r\nОтображение информации\r\nЖК-панель\r\nПотребляемая мощность (при работе)\r\n600 Вт\r\nПотребляемая мощность (в режиме ожидания)\r\n27 Вт\r\nУровень шума при работе\r\n54 дБ\r\nГабариты (ШхВхГ)\r\n490x405x600 мм\r\nВес\r\n33.1 кг',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(124,6,35,'LaserJet m601n','LaserJet m601n','','Цветность печати\r\nчерно-белая\r\nТехнология печати\r\nлазерная\r\nРазмещение\r\nнастольный\r\nОбласть применения\r\nбольшой офис\r\nКоличество страниц в месяц\r\n175000\r\nПринтер\r\nМаксимальный формат\r\nA4\r\nМаксимальное разрешение для ч/б печати\r\n1200x1200 dpi\r\nСкорость печати\r\n43 стр/мин (ч/б А4)\r\nВремя выхода первого отпечатка\r\n8.50 c (ч/б)\r\nЛотки\r\nПодача бумаги\r\n600 лист. (стандартная), 3600 лист. (максимальная)\r\nВывод бумаги\r\n500 лист. (стандартный)\r\nЕмкость лотка ручной подачи\r\n100 лист.\r\nРасходные материалы\r\nПлотность бумаги\r\n60-200 г/м2\r\nПечать на:\r\nкарточках, пленках, этикетках, глянцевой бумаге, конвертах, матовой бумаге\r\nРесурс ч/б картриджа/тонера\r\n10000 страниц\r\nКоличество картриджей\r\n1\r\nТип картриджа/тонера\r\nCE390A\r\nПамять/Процессор\r\nОбъем памяти\r\n512 МБ, максимальный 1024 МБ\r\nЧастота процессора\r\n800 МГц\r\nИнтерфейсы\r\nИнтерфейсы\r\nEthernet (RJ-45), USB\r\nВерсия USB\r\n2.0\r\nПрямая печать\r\nесть\r\nВеб-интерфейс\r\nесть\r\nШрифты и языки управления\r\nПоддержка PostScript\r\nесть\r\nПоддержка\r\nPostScript 3, PCL 5c, PCL 6, PDF\r\nКоличество установленных шрифтов PostScript\r\n92\r\nКоличество установленных шрифтов PCL\r\n105\r\nДополнительная информация\r\nПоддержка ОС\r\nWindows, Linux, Mac OS\r\nМинимальные системные требования\r\nIntel Pentium + 64 Mb RAM\r\nОтображение информации\r\nцветной ЖК-дисплей\r\nПотребляемая мощность (при работе)\r\n790 Вт\r\nПотребляемая мощность (в режиме ожидания)\r\n21 Вт\r\nГабариты (ШхВхГ)\r\n415x398x428 мм\r\nВес\r\n23.7 кг',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(125,6,35,'LaserJet P2015dn','LaserJet P2015dn','','Лазерный Ч/Б Принтер, А4\r\nСетевой\r\nОдносторонняя печать\r\nкартриджи Q7553A (3000 стр.) или Q7553X (7000 стр.)\r\nЛогин и пароль по умолчанию отсутствуют\r\nЧтобы распечатать страницу с параметрами принтера, нужно нажать и держать зеленую кнопку на принтере до тех пор, пока не загорится оранжевый индикатор.\r\nЧтобы сбросить настройки принтера в дефолтные:\r\n1. Выключите принтер.\r\n2. Нажмите и удерживайте кнопку Go.\r\n3. Включите принтер и продолжайте удерживать кнопку Go (минимум 20 сек). В течении этого времени загорятся светодиоды Go, Attention и Ready.\r\n4. Отпустите кнопку Go. Индикаторы принтера начнут циклически повторяться.\r\nПосле инициализации NVRAM, принтер выйдет в готовность.',0,'1 RJ-45\r\n1 USB Type B',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(126,6,514,'MP 2001SP','MP 2001SP','','Устройство\r\nпринтер/сканер/копир/факс\r\nЦветность печати\r\nчерно-белая\r\nТехнология печати\r\nлазерная\r\nРазмещение\r\nнастольный\r\nПринтер\r\nМаксимальный формат\r\nA3\r\nМаксимальный размер отпечатка\r\n297 × 420 мм\r\nМаксимальное разрешение для ч/б печати\r\n600x600 dpi\r\nСкорость печати\r\n20 стр/мин (ч/б А4)\r\nВремя разогрева\r\n20 с\r\nВремя выхода первого отпечатка\r\n6 c (ч/б)\r\nСканер\r\nТип сканера\r\nпланшетный/протяжный\r\nМаксимальный формат оригинала\r\nA3\r\nМаксимальный размер сканирования\r\n297x420 мм\r\nРазрешение сканера\r\n600x600 dpi\r\nСкорость сканирования (цветн.)\r\n50 стр/мин\r\nСкорость сканирования (ч/б)\r\n50 стр/мин\r\nПоддержка стандартов\r\nTWAIN\r\nОтправка изображения по e-mail\r\nесть\r\nКопир\r\nМаксимальное разрешение копира (ч/б)\r\n600x600 dpi\r\nСкорость копирования\r\n20 стр/мин (ч/б А4)\r\nВремя выхода первой копии\r\n6 с\r\nИзменение масштаба\r\n50-200 %\r\nШаг масштабирования\r\n1 %\r\nМаксимальное количество копий за цикл\r\n99\r\nЛотки\r\nПодача бумаги\r\n350 лист. (стандартная), 1600 лист. (максимальная)\r\nВывод бумаги\r\n350 лист. (стандартный)\r\nРасходные материалы\r\nПлотность бумаги\r\n60-162 г/м2\r\nПечать на:\r\nкарточках, пленках, этикетках, глянцевой бумаге, конвертах, матовой бумаге\r\nРесурс девелопера\r\n60000 страниц\r\nРесурс фотобарабана\r\n60000 страниц\r\nРесурс ч/б картриджа/тонера\r\n9000 страниц\r\nКоличество картриджей\r\n1\r\nТип картриджа/тонера\r\nMP 2501\r\nПамять/Процессор\r\nОбъем памяти\r\n1024 МБ, максимальный 1536 МБ\r\nФакс\r\nМаксимальное разрешение факса\r\n200x200 dpi\r\nМаксимальная скорость передачи\r\n33.6 Кбит/c\r\nPC Fax\r\nесть\r\nИнтерфейсы\r\nИнтерфейсы\r\nEthernet (RJ-45), USB\r\nВерсия USB\r\n2.0\r\nШрифты и языки управления\r\nПоддержка PostScript\r\nопционально\r\nПоддержка\r\nPCL 5e, PCL 6\r\nДополнительная информация\r\nПоддержка ОС\r\nWindows, Mac OS\r\nОтображение информации\r\nцветной ЖК-дисплей\r\nДиагональ дисплея\r\n4.3 дюйм.\r\nПотребляемая мощность (при работе)\r\n1550 Вт\r\nПотребляемая мощность (в режиме ожидания)\r\n113 Вт\r\nГабариты (ШхВхГ)\r\n587x460x568 мм\r\nВес\r\n38 кг',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(127,7,515,'Baseline Switch 2952-SFP','Baseline Switch 2952-SFP','','Управляемый\r\n48x GE(10/100/1000 Mbit/s)\r\n4x SFP (1000BASE-SX) //трансиверы 3CSFP91\r\n1U\r\nPoE отсутствует',0,'1\r\n2\r\n3\r\n4\r\n5\r\n6\r\n7\r\n8\r\n9\r\n10\r\n11\r\n12\r\n13\r\n14\r\n15\r\n16\r\n17\r\n18\r\n19\r\n20\r\n21\r\n22\r\n23\r\n24\r\n25\r\n26\r\n27\r\n28\r\n29\r\n30\r\n31\r\n32\r\n33\r\n34\r\n35\r\n36\r\n37\r\n38\r\n39\r\n40\r\n41\r\n42\r\n43\r\n44\r\n45\r\n46\r\n47\r\n48',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(131,6,35,'LaserJet M9040 MFP','HP 9040','Описание https://market.yandex.ru/product--mfu-hp-laserjet-m9040-mfp-v-nizhnem-novgorode/2132180?track=tabs ','Напольный большой, лазерный, ч/б, А3, высокоскоростной, двусторонний, сканирование.\r\nКартридж C8543X на 35000 листов.\r\nПароль?',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(132,6,99,'Ecosys M6026cdn','Kyocera 6026','Описание https://market.yandex.ru/product--mfu-kyocera-ecosys-m6026cdn/10690077/spec?track=tabs','А4, цветной, лазерный, двусторонний принтер и сканер.\r\nКартриджи:\r\nчерный TK-590K, \r\nжелтый TK-590Y, \r\nпурпурный TK-590M, \r\nголубой TK-590C\r\nПароли 2600/2600	и Admin/Admin\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(133,6,204,'AR-M205','Sharp AR-M205','Описание https://market.yandex.ru/product--mfu-sharp-ar-m205/1025775/spec?track=tabs','Сетевой, А3, ч/б, лазерный, сканирование дебильное\r\nКартридж AR-202T\r\nПароль Sharp (с заглавной буквы) или admin. Логин: Либо admin либо user',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(134,6,35,'LaserJet 5200','HP 5200','Описание https://market.yandex.ru/product--printer-hp-laserjet-5200/1026967/spec?track=tabs','Сетевой, А3, ч/б, лазерный, односторонний.\r\nКартридж Q7516A\r\nПароля admin/admin или пустой?',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(135,6,35,'LaserJet Enterprise Color M551','HP M551 Color','Описание https://www.nix.ru/autocatalog/printers_hp/_123942.html','Сетевой, А4, цветной, лазерный, односторонний.\r\nКартриджи:\r\nCE400A, CE400X (экономичный); \r\nCE403A (пурпурный), \r\nCE402A (желтый), \r\nCE401A (голубой)\r\nПароль: admin/пустой пароль\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(136,6,35,'LaserJet Pro MFP M125rnw','HP M125','','Сетевой, А4, ч/б, односторонний, сканер плохой по сути только копир.\r\nКартридж CF283A.\r\nПароль Admin/пусто',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(137,6,35,'LaserJet 3055','HP 3055','Описание https://www.nix.ru/autocatalog/printers_hp/_46788.html','Сетевой, А4, ч/б, односторонний, сканер только планшет, считай только копир.\r\nКартридж Q2612A.\r\nПароль?',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(138,2,516,'SIP-T31G','T31G','Аппарат SIP-T31G https://ipmatika.ru/products/desktop/yealink-sip-t31p/\r\nБлок питания pa-0.6A https://ipmatika.ru/products/phones-accessories/pa-0.6A/','2 линии, 2.3\" ч/б LCD-экран 132х64px\r\nУмеет провижн\r\nОтказоустойчивость с двойной регистрацией\r\nПитание PoE/БП 5V/0.6A (БП-ОПЦИЯ! pa-0.6A) \r\nПотребление через PoE : 3-4 W\r\nМожет работать бриджом на разных с PC VLANах\r\nadmin/admin\r\n',0,'Internet\r\nPC',226,NULL,0,0,NULL,0,0,'2023-09-08 03:20:14',NULL,NULL),(139,2,516,'SIP-T46U','T46U','Телефон SIP-T46U https://www.ipmatika.ru/products/desktop/yealink-sip-t46U/\r\nБлок питания pa-2A https://www.ipmatika.ru/products/phones-accessories/pa-2A/\r\nBluetooth адаптер BT41 https://www.ipmatika.ru/products/phones-accessories/yealink-bt41/\r\nWiFi адаптер WF50 https://www.ipmatika.ru/products/phones-accessories/yealink-wf50-1/\r\n\r\n','Цветной 4.3\" LCD-экран 480х272 пикселей\r\n10 линий (кнопок), до 16 SIP аккаунтов\r\nКонференция с 9ю абонентами (10-way)\r\nУмеет провижн\r\nОтказоустойчивость с одновременной регистрацией\r\nПитание PoE/БП 5V/2A (pa-2A в комплекте отсутствует!)\r\nДоступные опции\r\n - Yealink BT41 - Bluetooth адаптер для покдл. BT гарнитуры\r\n - Yealink WF50 - WiFi адаптер\r\nУмеет в бридж с назначением VoIP VLAN\r\nadmin/admin',0,'Internet\r\nPC',227,NULL,0,0,NULL,0,0,'2023-09-08 03:20:14',NULL,NULL),(140,6,35,'LaserJet M1536dnf MFP','HP 1536','Описание https://www.nix.ru/autocatalog/printers_hp/_153851.html\r\n','А4, ч/б, МФУ, сетевой, односторонний.\r\nСканирование куда?\r\nКартридж: CE278A - ресурс 2100 стр\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(141,6,35,'LaserJet 700 Color MFP M775','HP 775','Описание https://market.yandex.ru/product--mfu-hp-laserjet-enterprise-700-color-mfp-m775dn-cc522a/8499878/spec?track=tabs\r\nКак достать термоблок чтобы вытащить из него замятую бумагу: https://support.hp.com/ua-uk/document/c05311296','А3, цветной, лазерный, сетевой, двусторонний сканер и принтер\r\nКартриджи:\r\nчерный CE340A 651A - ресурс 16000 стр; \r\nголубой CE341A 651A - ресурсы цвета 13500, \r\nжелтый CE342A 651A, \r\nпурпурный CE343A 651A\r\nНабор перемещателя CE516A\r\nНабор термоэлемента CE515A\r\nНабор подачи документов L2718A\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(142,6,35,'LaserJet MFP M426fdn','HP 426','Описание https://market.yandex.ru/product--mfu-hp-laserjet-pro-mfp-m426fdn/12915107/spec?track=tabs','А4, ч/б, сетевой, двусторонний.\r\nКартридж CF226A - ресурс 3100 или CF226X - ресурс 9000\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(143,6,35,'LaserJet Color CP5520 ','HP 5520','Описание https://market.yandex.ru/product--printer-hp-color-laserjet-enterprise-cp5525dn/6471344/spec?track=tabs','А3, цветной, лазерный, сетевой, двусторонний.\r\nКартриджи:\r\nчерный CE270A - ресурс 13500, \r\nголубой CE271A - ресурс цветных 15000, \r\nмалиновый CE273A, \r\nжелтый CE272A\r\nTransfer Kit CE979A\r\nFuser Kit CE978A',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(144,6,35,'LaserJet 500 colorMFP M570dn','HP 570','Описание https://market.yandex.ru/product--mfu-hp-laserjet-pro-500-color-mfp-m570dn/8491005/spec?track=tabs&cpa=0','А4, цветной, лазерный, двусторонний сканер и принтер, сетевой.\r\nКартриджи:\r\nчерный CE400A -ресурс 5500, CE400X - 11000; \r\nголубой CE401A - ресурс цветных 6000, \r\nжелтый CE402A, \r\nпурпурный CE403A',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(145,6,35,'LaserJet 700 M712','HP 712','Описание https://market.yandex.ru/product--printer-hp-laserjet-enterprise-700-printer-m712dn-cf236a/8499610/spec?track=tabs&cpa=0','А3, ч/б, сетевой, двусторонний.\r\nКартридж CF214X - ресурс 17500.\r\nMaintenance Kit CF254A',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(146,6,35,'LaserJet 400 color M451dn','HP 451','Описание https://market.yandex.ru/product--printer-hp-laserjet-pro-400-color-m451dn/7842966/spec?track=tabs','А4, цветной, лазерный, двусторонний сетевой принтер.\r\nКартриджи:\r\nчерный CE410X - ресурс 4000 стр; \r\nголубой CE411A - ресурсы цветных 2600 стр,\r\nпурпурный CE413A , \r\nжелтый CE412A \r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(147,6,35,'LaserJet Color M750','HP 750','Описание https://market.yandex.ru/product--printer-hp-color-laserjet-enterprise-m750dn/10553952/spec?track=tabs','А3, цветной, лазерный, двусторонний принтер.\r\nКартриджи:\r\nчерный CE270A - ресурс 13500 стр, \r\nголубой CE271A - ресурсы цветных 15000 стр, \r\nпурпурный CE273A, \r\nжелтый CE272A\r\nTransfer Kit CE516A,\r\nFuser Kit CE978A\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(148,6,99,'Ecosys M6035cidn','Kyocera 6035','Описание https://market.yandex.ru/product--mfu-kyocera-ecosys-m6035cidn/12659949/spec','А4, цветной, лазерный, двусторонний принтер и сканер.\r\nКартриджи:\r\nчерный TK-5150K - ресурс 12000, \r\nжелтый TK-5150Y - ресурс цветных 10000, \r\nпурпурный TK-5150M, \r\nголубой TK-5150C\r\nПароли 3500/3500	и Admin/Admin\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(149,6,99,'Ecosys M6630cidn','Kyocera 6630','Описание https://market.yandex.ru/product--mfu-kyocera-ecosys-m6630cidn/38018063/spec?cpa=0','А4, цветной, лазерный, двусторонний сканер и принтер.\r\nКартриджи:\r\nчерный TK-5270K - ресурс 8000, \r\nжелтый TK-5270Y - ресурс цветных 6000, \r\nпурпурный TK-5270M, \r\nголубой TK-5270C\r\nЛогин/пароль: 3000/3000 или Admin/Admin',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(150,6,99,'Ecosys M8130cidn','Kyocera 8130','Описание https://market.yandex.ru/product--mfu-kyocera-ecosys-m8130cidn/1858311872/spec?track=tabs','А3, цветной, лазерный, двусторонний сканер и принтер.\r\nКартриджи:\r\nчерный TK-8115K - ресурс 12000, \r\nголубой TK-8115C - ресурс 6000, \r\nпурпурный TK-8115M, \r\nжелтый TK-8115Y.\r\nЛогин/пароль: 3000/3000 или Admin/Admin',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:27',NULL,NULL),(151,7,180,'Catalyst WS-C3560X-24T-S','WS-C3560X-24T','','Управляемый L3\r\n24x 1000BaseT\r\nPoE отсутствует',1,'Ge0/1\r\nGe0/2\r\nGe0/3\r\nGe0/4\r\nGe0/5\r\nGe0/6\r\nGe0/7\r\nGe0/8\r\nGe0/9\r\nGe0/10\r\nGe0/11\r\nGe0/12\r\nGe0/13\r\nGe0/14\r\nGe0/15\r\nGe0/16\r\nGe0/17\r\nGe0/18\r\nGe0/19\r\nGe0/20\r\nGe0/21\r\nGe0/22\r\nGe0/23\r\nGe0/24',228,'',0,0,'',0,0,'2023-09-13 16:31:39','admin',NULL),(152,7,180,'Catalyst WS-C2960-24PC-L','WS-C2960-24PC','','Управляемый коммутатор\r\n24х 100BaseTx (PoE 802.3af 370W)\r\n2x Combo 1000BaseT | 1000BaseX SFP\r\n',0,'FE0/1\r\nFE0/2\r\nFE0/3\r\nFE0/4\r\nFE0/5\r\nFE0/6\r\nFE0/7\r\nFE0/8\r\nFE0/9\r\nFE0/10\r\nFE0/11\r\nFE0/12\r\nFE0/13\r\nFE0/14\r\nFE0/15\r\nFE0/16\r\nFE0/17\r\nFE0/18\r\nFE0/19\r\nFE0/20\r\nFE0/21\r\nFE0/22\r\nFE0/23\r\nFE0/24\r\nGE0/1\r\nGE0/2',230,'',0,0,'',0,0,'2023-09-10 06:15:52','admin',NULL),(153,7,180,'Catalyst WS-C2960-48PST-L','WS-C2960-48PST','','Catalyst 2960 (WS-C2960-48PST-L)',0,'',NULL,'',0,0,'',0,0,'2023-09-10 06:17:39','admin',NULL),(154,7,180,'Catalyst WS-C2960-48PST-S','WS-C2960-48PST-S','','Catalyst 2960 (WS-C2960-48PST-S)',0,'',NULL,'',0,0,'',0,0,'2023-09-10 06:18:17','admin',NULL),(155,7,180,'Catalyst WS-C2960X-24PSQ','WS-C2960X-24PSQ','','Q - Безвентиляторный',0,'',NULL,'',0,0,'',0,0,'2023-09-10 06:21:08','admin',NULL),(156,7,180,'Catalyst WS-C2960X-48FPS-L','WS-C2960X-48FPS','','Catalyst 2960X (WS-C2960X-48FPS-L)',0,'',NULL,'',0,0,'',0,0,'2023-09-10 06:23:01','admin',NULL),(157,7,180,'Catalyst WS-C2960RX-24PS-L','WS-C2960RX-24PS','','Catalyst 2960RX (WS-C2960RX-24PS-L)',0,'',NULL,'',0,0,'',0,0,'2023-09-10 06:20:33','admin',NULL),(158,7,180,'SG350-10MP','SG350-10MP','','Cisco SG350-10MP',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(159,7,497,'CRS354-48P-4S+2Q+','CRS354-48P','','Mikrotik CRS354-48P-4S+2Q+',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(160,7,180,'Catalyst WS-C3750X-24T-S','WS-C3750X-24T','','Catalyst 3750X (WS-C3750X-24T-S)',0,'Gi1/0/1\r\nGi1/0/2\r\nGi1/0/3\r\nGi1/0/4\r\nGi1/0/5\r\nGi1/0/6\r\nGi1/0/7\r\nGi1/0/8\r\nGi1/0/9\r\nGi1/0/10\r\nGi1/0/11\r\nGi1/0/12\r\nGi1/0/13\r\nGi1/0/14\r\nGi1/0/15\r\nGi1/0/16\r\nGi1/0/17\r\nGi1/0/18\r\nGi1/0/19\r\nGi1/0/20\r\nGi1/0/21\r\nGi1/0/22\r\nGi1/0/23\r\nGi1/0/24',NULL,'',0,0,'',0,0,'2023-09-10 06:24:02','admin',NULL),(162,6,112,' imagePROGRAF iPF780','iPF780','Описание https://market.yandex.ru/product--printer-canon-imageprograf-ipf780/10853862/spec?track=tabs','Цветной, струйный, рулонный.\r\nКартриджи 130мл:\r\nPFI-107BK - черный\r\nPFI-107MBK - черный матовый 2 штуки!\r\nPFI-107C - голубой\r\nPFI-107M - пурпурный\r\nPFI-107Y - желтый\r\nЕсть увеличенные картриджи 300мл:\r\nPFI-207 теже самые по цвету\r\n\r\nпечатающая головка: PF-04\r\nКартридж для отработанных чернил: MC-10\r\nПароль или пустой или canon или admin\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(163,5,20,'IP Office 500 v2','','','Гибридная (IP/Аналоговая) АТС\r\nБлок управления  содержит 4 слота карт расширения\r\nТакже возможна установка внешних модулей расширения',1,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(164,5,20,'INTUITY™ AUDIX® LX Multimedia Messaging Server','Audix','https://support.avaya.com/products/P0058/intuity-audix-lx-multimedia-messaging-server','The INTUITY™ AUDIX® LX Multimedia Messaging Server delivers robust applications that let businesses conveniently and flexibly create and respond to messages with any combination of voice, fax, text, and file attachments. INTUITY AUDIX LX messaging gives businesses convenient access to all messages using a phone or computer whether the message is delivered through voice mail, email, or fax. Employees can respond more quickly, resolve customer issues, and provide superior service.',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(165,5,20,'Сервер S8500C','Avaya S8500C','https://deltat.ru/catalog/servery/avaya-s8500-server/','Коммуникационный сервер Avaya S8500 предназначен для достаточно емких систем — до 2400 соединительных линий. Количество портов — 800. Количество обслуживаемых вызовов в часы наибольший нагрузки — 100 тысяч. Сервер Avaya S8500 применяется для функционирования контакт-центров как в средних компаниях, так и в крупном бизнесе.\r\nТехнические особенности\r\nУстройство работает на базе Intel Pentium M. Установленная операционная система — Linux Red Hat 8.0. S8500 обеспечивает при помощи данной системы качественное функционирование даже в случае повышенной нагрузки или отказа жесткого диска. Система обеспечивает связь в этом случае на протяжении 72 часов.\r\nКоммуникационный сервер Avaya S8500 имеет шлюзы типа G650. Также предусмотрены традиционные СМС в качестве кабинетов. Устройство со шлюзом G650 функционирует по типу IP connect. В этом случае используется протокол Н.248. Также сервер способен принимать вызовы из разных шлюзов.\r\n\r\nСтатив на шлюзе G650 может иметь до 64 портов. К устройству Avaya S8500 на шлюзе G150/G250/G350/G700 можно подключить до 250 портов. Используемый в этом случае режим — IP connect.',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(166,5,20,'G650 Media Gateway','Avaya G650','','The G650 Media Gateway features an 8U high, 14-slot chassis that can be installed in industry standard EIA-310 19\", 24\", or 600 mm ETSI open or closed racks. The G650 can accommodate a range of analog, digital, ISDN, and IP (over the LAN) phone station configurations, with voice transport options over IP, analog, TDM, or ATM. Available dual redundant, load-sharing power supplies with AC/DC inputs provide enhanced system reliability.',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(167,6,35,'LaserJet MFP M725','LaserJet MFP M725','','LaserJet MFP M725',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(168,6,99,'Ecosys M3040idn','ECOSYS M3040idn','','Ч/Б МФУ А4\r\nсетевой, лазерный\r\nДвусторонняя печать\r\nДвустороннее сканирование \r\nСканирование на SMB/FTP/в Почту/Флешку\r\nМакс нагрузка150000 стр/мес\r\nТонер-картридж TK-3150 (14500стр)',0,'',NULL,'',0,0,'',0,0,'2023-09-05 14:10:28','reviakin.a',NULL),(169,6,99,'Ecosys M3145idn','','Спецификация https://www.kyoceradocumentsolutions.ru/index/products/product/ecosysm3145dn.technical_specification.html','Сетевой\r\nДвусторонняя печать\r\nДвустороннее сканирование (с податчика)\r\nСканирование на почту, SMB, FTP, флешку\r\nПредельная нагрузка: 150000 лист./мес.\r\nТонер-барабан TK-3160 на 12500 стр',0,'',NULL,'',0,0,'',0,0,'2023-09-05 14:10:28','reviakin.a',NULL),(170,6,99,'Ecosys M3645idn','ECOSYS M3645idn','','ECOSYS M3645idn',0,'',NULL,'',0,0,'',0,0,'2023-09-05 14:10:28','reviakin.a',NULL),(171,5,20,'G430','Avaya G430','http://avaya.newsystems.ru/products/41/57/','Avaya G430.\r\n\r\n Avaya G430 представляет собой многофункциональный шлюз, который используется на предприятиях малого и среднего бизнеса (численность пользователей – 1-150). Шлюз имеет поддержку до двух дополнительных модулей EM200, которые позволяют увеличить как число абонентов, так и портов за счет наличия двух слотов для медиа-модулей. Avaya G430 работает под управлением ПО Avaya AuraTM Communication Manager от версии 5.2.\r\n\r\n Медиа шлюз Avaya G430 соединяет в себе функции сети передачи данных и АТС. При поступлении звонка он дает возможность обойти ТФоП по сети Internet с помощью маршрутизации данных и VoIP. Шлюз поддерживает цифровых и IP телефонных аппаратов Avaya, а также аналоговых устройств других производителей (аналоговые телефоны, модемы, факсы).\r\n\r\n В базовую конфигурацию Avaya G430 входит блок питания, внутренняя DSP карта, рассчитанная на 20 каналов VoIP, а также 256 Мб оперативной памяти. Конфигурацию можно расширить посредством добавления DSP карт (до 80 VoIP каналов), замены RAM на 512 Мб, применения дополнительной Compact flash (число объявлений увеличивается до 1024) и повышения количества времени объявлений до четырех часов.\r\n\r\n Возможности Avaya G430\r\n\r\n ·        Avaya G430 имеет 2 встроенных порта 10/100 Base-T LAN, сервисные порты, 2 порта под USB, 1 порт 10/100 Base-T WAN, Compact Flash, чтобы хранить дополнительные объявления, и Contact closure adjunct порт.\r\n\r\n·        Шлюз поддерживает стандартную и расширенную локальную выживаемость (SLS и ELS соответственно) при работе с сервером S8300.\r\n\r\n·        Avaya G430 оснащен повышенной защитой посредством поддержки шифрования типов VPN, SRTP, SSH/SCP, SNMP v3, а также широким возможностям управления паролями.\r\n\r\n·        Маршрутизация поддерживает протоколы RIP, OSPF и VRRP. К Avaya G430 можно подсоединить внешнее устройство WAN с помощью фиксированного порта 10/100 Ethernet WAN router.\r\n\r\n·        DSP ресурсы включают в себя 20 VoIP каналов с возможностью расширения до 100 каналов посредством дополнительных карт.',0,'',232,NULL,0,0,NULL,0,0,'2023-09-08 03:20:16',NULL,NULL),(172,5,20,'Сервер S8300','Avaya S8300','https://deltat.ru/catalog/servery/avaya-s8300-server/','Технические характеристики\r\n\r\nПоддерживает IP-телефоны, цифровые и аналоговые аппараты. Avaya S8300 D устанавливается в первый слот медиа-шлюза G700/G250/G350/G450/G430 и может быть использован в качестве основного или резервного процессора всей системы. Резервный, подключается автоматически в случае потери связи шлюза с головным процессором. Таким образом, Avaya S8300 D гарантирует надежную и бесперебойную связь.\r\n\r\nНа место процессора во всех шлюзах, кроме G250, можно установить любой доступный модуль телефонии. Возможная емкость процессора – от 450 до 1000 IP-абонентов и от 450 до 4000 транков (в зависимости от версии СМ). Avaya S8300 D имеет дополнительные модули для VoIP. Количество вызовов, которые происходят в часы наибольшей нагрузки, составляет 10 000.\r\n\r\nДополнительно коммуникационный сервер S8300 D обеспечивает поддержку голосовой почты и поддержку операторских центров обработки звонков. Администрирование сервера происходит через протокол TCP/IP (LAN).',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(173,5,20,'MM710','Avaya MM710','https://deltat.ru/catalog/shlyuzy/avaya-mm710-modul-rasshireniya/','Медиа модуль Avaya MM710 T1/E1 позволяет построить соединение по каналу Е1 (или Т1). Avaya ММ710 содержит встроенный обслуживающий блок (CSU), при его использовании внешний CSU не требуется. Модуль Avaya ММ710 поддерживается в следующих медиа шлюзах G700, G450, G430 и G350.\r\nМедиа модуль Avaya ММ710 поддерживает интерфейс DS1, по стандарту Е1 ITU-T G.703 2.048 Мб/сек. ММ710 не поддерживает формат кодирования линии Code Mark Inversion.\r\n\r\nMM710 Media Module использует эхо компенсацию в обоих направлениях и может компенсировать эхо с задержками до 96 мсек.\r\n\r\nПлата Avaya MM710 поддерживает компандирование по закону с А-характеристикой и компандирование по закону с мю-характеристикой.\r\n\r\nШесть круглых портов на панели Avaya MM710 предоставляют доступ для проведения тестирования:\r\n\r\nРежим сканирования SM позволяет проводить пассивное наблюдение за входящей линией связи.\r\nРежим ЕM позволяет проводить пассивное наблюдение за исходящей линией связи.\r\nФункция SO позволяет проводить интрузивное наблюдение за входящим сигналом сети. Гнездо SO прерывает соединение этого сигнала с выравнивателем строк.\r\nЭлектронная обработка изображений EI позволяет направлять сигнал к выравнивателю строк. Гнездо EI изолирует сигнал Rx сети.\r\nФункция EI позволяет направлять сигнал к сети. Гнездо SI не позволяет сигналу Tx выравнивателя строк выйти к сети.\r\nФункция ЕO позволяет проводить интрузивное наблюдение за входящим сигналом с выравнивателя строк. Гнездо ЕO прерывает соединение этого сигнала с сетевым гнездом RJ48C.',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(174,8,500,'Smart-UPS 1500 SUA1500I','SUA1500I','Спецификации https://www.apc.com/shop/ru/ru/products/APC-Smart-UPS-1500-USB-230-/P-SUA1500I\r\nграфик времени автономной работы https://www.apc.com/products/runtimegraph/runtime_graph.cfm?base_sku=SUA1500I&chartSize=large\r\nБат. сборка RBC7 https://apc.com/shop/ru/ru/products/-APC-7/P-RBC7','Управляемый (USB)\r\n1500VA, 980W, емкость 24Vx17Ah\r\nДоп. бат. модули не предусмотрены\r\n8 розеток C13 (переходник) - все зарезервированы\r\nБатарейная сборка RBC7 (12Vx17Ah x2шт)\r\nдержит 23мин при 500Вт',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(175,3,517,'5510.824','','manual /web/scans/SC_MANUAL.pdf','система удаленного IP мониторинга\r\n19\" шасси 1HU с встроенной \r\n8 аналоговых датчиков\r\n16 сухих адресных контактов\r\nдо 40 цифровых датчиков \r\n   - температуры\r\n   - влажности\r\n   - освещенности\r\n   - движения\r\n   - вентиляторов\r\n   -  и тп. \r\nили одного считывателя доступа на каждую шину (2 шины)\r\n4 розетки на 220 В*5 А\r\nUSB порт для подключения Web камеры.',1,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(176,7,518,'LS-5130S-52S-HI','S5130S-52S-HI','','S5130S-52S-HI\r\n48 Ethernet\r\n4 SFP\r\nConsole - mikro USB',0,'',NULL,'',0,0,'',0,0,'2023-09-05 14:10:28',NULL,NULL),(177,7,180,'Catalyst WS-C2960X-48TD-L','WS-C2960X-48TD','','Catalyst 2960X (WS-C2960X-48TD-L)',0,'Gi1/0/1\r\nGi1/0/2\r\nGi1/0/3\r\nGi1/0/4\r\nGi1/0/5\r\nGi1/0/6\r\nGi1/0/7\r\nGi1/0/8\r\nGi1/0/9\r\nGi1/0/10\r\nGi1/0/11\r\nGi1/0/12\r\nGi1/0/13\r\nGi1/0/14\r\nGi1/0/15\r\nGi1/0/16\r\nGi1/0/17\r\nGi1/0/18\r\nGi1/0/19\r\nGi1/0/20\r\nGi1/0/21\r\nGi1/0/22\r\nGi1/0/23\r\nGi1/0/24\r\nGi1/0/25\r\nGi1/0/26\r\nGi1/0/27\r\nGi1/0/28\r\nGi1/0/29\r\nGi1/0/30\r\nGi1/0/31\r\nGi1/0/32\r\nGi1/0/33\r\nGi1/0/34\r\nGi1/0/35\r\nGi1/0/36\r\nGi1/0/37\r\nGi1/0/38\r\nGi1/0/39\r\nGi1/0/40\r\nGi1/0/41\r\nGi1/0/42\r\nGi1/0/43\r\nGi1/0/44\r\nGi1/0/45\r\nGi1/0/46\r\nGi1/0/47\r\nGi1/0/48\r\nGi1/0/49\r\nGi1/0/50',NULL,'',0,0,'',0,0,'2023-09-10 06:23:07','admin',NULL),(178,12,35,'StorageWorks D2D4106i - G02 (EH996A или EH996B)','D2D4106i','HPE https://support.hpe.com/hpesc/public/km/product/5111295/hpe-d2d4106i-backup-system?ismnp=0&l5oid=5196525#t=Documents&sort=relevancy&layout=table&numberOfResults=25&f:@kmdoclanguagecode=[cv1871440,cv1871463]&hpe=1\r\nТех. характеристики https://h10057.www1.hp.com/ecomcat/hpcatalog/specs/provisioner/05/EH996A.htm\r\nСписок запчастей https://support.hpe.com/hpesc/public/docDisplay?docLocale=en_US&docId=c05047787#N109E2','1 контроллер (Smart Array P212 controller board)\r\n2 блока питания по 750 Вт\r\n12x 500 Gb HDD (6Tb RAW /4.5Tb Usable space)\r\nподключение одной полки, в 2 раза увеличивающей ёмкость\r\nДедупликация и Репликация есть',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(179,12,35,'StorageWorks P6300','EVA P6300','https://support.hpe.com/hpesc/public/km/search#q=p6300&t=All&sort=relevancy&numberOfResults=25','2 контроллера HSV340\r\nРежим работы контроллеров Active-Active\r\n2 Hot Spare блока питания\r\nКорзина на 24 2.5\" диска\r\nне умеет сжатие/дедупликацию\r\n2 сетевых модуля \r\n',1,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(180,8,500,'Smart-UPS 2200 (SMT2200I)','','https://www.apc.com/shop/ru/ru/products/APC-Smart-UPS-2200-230-/P-SMT2200I\r\nhttps://www.dns-shop.ru/product/b9b03047a2ea8a5a/ibp-apc-smart-ups-2200va-smt2200i/characteristics/\r\nБатарея - https://www.apc.com/ru/ru/product/RBC55/%D1%81%D0%BC%D0%B5%D0%BD%D0%BD%D1%8B%D0%B9-%D0%B1%D0%B0%D1%82%D0%B0%D1%80%D0%B5%D0%B9%D0%BD%D1%8B%D0%B9-%D0%BA%D0%B0%D1%80%D1%82%D1%80%D0%B8%D0%B4%D0%B6-apc-55-%D1%81%D0%BE-%D1%81%D1%80%D0%BE%D0%BA%D0%BE%D0%BC-%D0%B3%D0%B0%D1%80%D0%B0%D0%BD%D1%82%D0%B8%D0%B8-2-%D0%B3%D0%BE%D0%B4%D0%B0/','Батареи RBC55 \r\nУправляемый с ЖК-экраном. Линейно-интерактивный.\r\nМаксимальная мощность выходного тока 1,98 кВт  (2,2 кВА)\r\nНельзя подключить дополнительный блок батарей\r\nРозетки с питанием от батарей: IEC Jumpers (2 шт), IEC 320 C13 (8 шт), IEC 320 C19 (1 шт)\r\nСерийный номер (находится в самом меню на ИБП в разделе Information)\r\n',0,'1 разъём EPO, 1 RJ-45 (UPS Monitoring Port), 1 USB type B, 1 SmartSlot',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(181,6,35,'Color LaserJet Pro MFP M476dn','M476dn','https://support.hp.com/ru-ru/product/hp-color-laserjet-pro-mfp-m476-series/5245583/model/5245590/product-info?jumpid=reg_r1002_ruru_s-001_title_r0001\r\nРасходники https://hp-rus.com/by-device/mfu-hp-color-laserjet-pro-m476dn-mfp/','МФУ, А4, Цветной\r\nСетевой: Да\r\nПечать: 2-сторонняя\r\nСканер: Податчик с 1-сторонним сканированием\r\nДопустимая нагрузка по печати: до 40 000 стр/мес\r\nМодель картриджа: набор из 3 цветных картриджей CF440AM, черный  HP312A (CF380A) на 2400 стр, HP312X (CF380X) на 4400 стр, голубой HP312A (CF381A), желтый HP312A (CF382A), пурпурный HP312A (CF383A)',0,'1 Ethernet (RJ-45),1 USB тип A, 1 USB  тип B, 2 телефонных 4P4C',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(182,6,99,'Ecosys P3050dn','Kyocera P3050dn','','Устройство принтер\r\nЦветность печати черно-белая\r\nТехнология печати лазерная\r\nРазмещение настольный\r\nОбласть применения большой офис\r\nКоличество страниц в месяц 200000\r\nМаксимальный формат A4\r\nМаксимальный размер отпечатка 297 × 356 мм\r\nАвтоматическая двусторонняя печать есть\r\nМаксимальное разрешение для ч/б печати 1200x1200 dpi\r\nСкорость печати 50 стр/мин (ч/б А4)\r\nВремя разогрева 20 с\r\nВремя выхода первого отпечатка 6.20 c (ч/б)\r\nПодача бумаги 600 лист. (стандартная), 2600 лист. (максимальная)\r\nВывод бумаги 500 лист. (стандартный)\r\nЕмкость лотка ручной подачи 100 лист.\r\nПлотность бумаги 60-220 г/м2\r\nПечать на:карточках, пленках, этикетках, глянцевой бумаге, конвертах, матовой бумаге\r\nРесурс фотобарабана 500000 страниц\r\nРесурс ч/б картриджа/тонера 12500 страниц\r\nКоличество картриджей 1\r\nТип картриджа/тонера TK-3160 (на 12500 страниц при 5%), TK-3170\r\nОбъем памяти 512 МБ, максимальный 2560 МБ\r\nПроцессор Coretex-A9 Частота процессора 1200 МГц\r\nИнтерфейсы Ethernet (RJ-45), USB\r\nВерсия USB 2.0\r\nПоддержка AirPrint есть\r\nУстройство для чтения карт памяти есть\r\nПоддержка карт памяти SD\r\nЧисло слотов расширения 1, свободных 1\r\nПрямая печать есть\r\nВеб-интерфейс есть\r\nПоддержка PostScript есть\r\nПоддержка PostScript 3, PCL 5c, PCL 6, PDF\r\nКоличество установленных шрифтов PostScript 93\r\nКоличество установленных шрифтов PCL 93\r\nПоддержка ОС Windows, Linux, Mac OS, iOS\r\nОтображение информации ЖК-панель\r\nПотребляемая мощность (при работе) 636 Вт\r\nПотребляемая мощность (в режиме ожидания) 10 Вт\r\nУровень шума при работе 53.2 дБ\r\nУровень шума в режиме ожидания 26.9 дБ',0,'',NULL,'',0,0,'',0,0,'2023-09-05 14:10:28','reviakin.a',NULL),(183,15,519,'ADS-2800W','','Страница продукта https://www.brother.ru/scanners/ads-2800w','Сетевой сканер \r\nEthernet / WiFi (WPA2-PSK)\r\nПротяжный, двусторонний 40 стр/минуту\r\nСканирование в FTP/SFTP/SMB/SMTP/USB\r\n',0,'',233,NULL,0,0,NULL,0,0,'2023-09-08 03:20:16',NULL,NULL),(184,12,496,'OceanStor 2200 v3','OceanStor 2200','Product page https://e.huawei.com/ru/products/storage/massive-storage/oceanstor-2200-v3\r\nDocs https://e.huawei.com/ru/material/MaterialList?id={F235F010-E33C-42C0-915E-7B604D555105}','2 контроллера (макс)\r\n2 блока питания (макс)\r\n16Gb cache RAM\r\nдо 24 дисков в корпус с контроллерами\r\nдо 300 дисков с учетом доп. полок\r\nСетевые порты \r\n  1/10Gbit ethernet\r\n  10Gbit FcOE\r\n  8/16 Gbit FC  \r\nКорпус контроллеров - 2U\r\nДисковые полки 2 или 4U',1,'',235,NULL,0,0,NULL,0,0,'2023-09-08 03:20:16',NULL,NULL),(185,8,500,'Smart-UPS SRT 5000 ВА (SRT5KRMXLI)','','https://www.apc.com/shop/ru/ru/products/-APC-Smart-UPS-SRT-5000-230-/P-SRT5KRMXLI\r\nБатарея - https://www.apc.com/ru/ru/product/APCRBC140/%D1%81%D0%BC%D0%B5%D0%BD%D0%BD%D1%8B%D0%B9-%D0%B1%D0%B0%D1%82%D0%B0%D1%80%D0%B5%D0%B9%D0%BD%D1%8B%D0%B9-%D0%BA%D0%B0%D1%80%D1%82%D1%80%D0%B8%D0%B4%D0%B6-apc-140-%D1%81%D0%BE-%D1%81%D1%80%D0%BE%D0%BA%D0%BE%D0%BC-%D0%B3%D0%B0%D1%80%D0%B0%D0%BD%D1%82%D0%B8%D0%B8-2-%D0%B3%D0%BE%D0%B4%D0%B0/','Управляемый\r\nБатарея APC RBC140\r\nМаксимальная мощность выходного тока 4,5 кВт (5,0 кВА)\r\nВозможно подключение дополнительного батарейного модуля (уже подключен. S/N: 7S1831L00820)\r\nРозетки с питанием от батареи: IEC 320 C13 (6 шт), IEC 60320 C13 (6 шт), IEC Jumpers (2 шт), IEC 320 C19 (4 шт), IEC 60320 C19 (4 шт)\r\nСерийный номер находится в разделе Информация на ЖК-дисплее ИБП\r\n\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(186,4,497,'RBSXTG-2HnD','RBSXTG-2HnD','','Mikrotik RBSXT G-2Hn - D - это беспроводной маршрутизатор с интегрированной секторной антенной 10d - Bi MIMO 2x2, работающей на частоте 2,4ГГц в режиме 802.11 b/g/n MIMO 2x2. Ширина луча составляет 60, что позволяет использовать RBSXT G-2Hn - D для создания секторной базовой станций начального уровня. Также устройство отлично подходит для создания соединения точка-точка и использования в качестве CPE. RBSXT G-2Hn - Dпоставляется с предустановленной Router - OS L4.\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(187,1,504,'Neos DF226 (APM Конструктора)','Neos DF226','','i5-6400\r\n16Gb DDR4\r\nNVIDIA Quadro M2000 1MiB\r\n\r\n',0,'',236,NULL,0,0,NULL,0,0,'2023-09-08 03:20:17',NULL,NULL),(188,16,520,'273V7','273V7QJAB','https://www.philips.ru/c-p/273V7QJAB_00/full-hd-lcd-monitor#see-all-benefits','Технические характеристики\r\nИзображение/дисплей\r\n\r\nТип ЖК-панели\r\nТехнология IPS\r\nТип подсветки\r\nСистема W-LED\r\nРазмер панели\r\n27 дюймов / 68,6 см\r\nПокрытие экрана дисплея\r\nАнтиблик, жесткость 3H, матовость 25 %\r\nРабочая область просмотра\r\n597,89 (Г) x 336,31 (В)\r\nФормат изображения\r\n16:9\r\nПлотность пикселей\r\n82 PPI\r\nВремя отклика (типич.)\r\n4 мс (серый к серому)*\r\nЯркость\r\n250  кд/м²\r\nSmartContrast\r\n10 000 000:1\r\nКоэфф. контрастности (типич.)\r\n1000:1\r\nМаксимальное разрешение\r\n1920 x 1080 с частотой 75 Гц*\r\nШаг пикселей\r\n0,311 x 0,311 мм\r\nУгол просмотра\r\n178º (Г) / 178º (В)\r\n@ C/R > 10\r\nБез мерцания\r\nДа\r\nЦвета дисплея\r\n16,7 M\r\nЧастота сканирования\r\n30–83 кГц (Г)/56–76 Гц (В)\r\nРежим LowBlue\r\nДа\r\nsRGB\r\nДа\r\nПодключения\r\n\r\nВход сигнала\r\nVGA (аналоговый)\r\nHDMI (цифровой, HDCP)\r\nDisplayPort 1.2\r\nСинхронизация входного сигнала\r\nРаздельная синхронизация\r\nСинхронизация по зеленому\r\nАудиовход/аудиовыход\r\nАудиовход ПК\r\nВыход для наушников',0,'VGA \r\nHDMI \r\nDP',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(189,13,35,'ProBook 430 G8 (27J08EA)','','https://hp-rus.com/catalog/noutbuki/uma-i7-1165g7-430-g8-13-3-fhd-ag-uwva-250-hd-16gb-/','Артикул	27J08EA\r\nТип	Ноутбук\r\nВид	Бизнес\r\nФорм-фактор	Классический\r\nЦвет корпуса	Серебристый\r\nСерия	HP ProBook\r\nЭкран, Диагональ, дюймов	13.3\r\nЭкран, Разрешение	1920 x 1080\r\nЭкран, Тип матрицы	IPS\r\nЭкран, Яркость, кд/м2	250\r\nЭкран, Частота обновления, ГЦ	60\r\nРазмер устройства (Ш x Г x В), мм	307 x 208 x 16\r\nРазмер упаковки (Ш x Г x В), мм	305 x 445 x 70\r\nПроцессор, Производитель	Intel\r\nПроцессор, Количество ядер	4\r\nПроцессор, Тактовая частота, GHZ	2,8 GHz\r\nПроцессор, Серия	Core™ i7\r\nПроцессор, Модель	1165G7\r\nОперативная память, Объем памяти, Гб	16\r\nВидеокарта, Тип	Интегрированная\r\nВидеокарта, Производитель	Intel\r\nВидеокарта, Модель встроенной видеокарты	Iris® Xe™\r\nЖесткий диск, Тип	SSD\r\nЖесткий диск, Объём, Гб	512\r\nОперационная система	Windows 10 Pro 64\r\nСканер отпечатка пальца	Есть\r\nПодсветка клавиатуры	Нет\r\nЦифровой блок клавиатуры	Нет\r\nТехнология MAX-Q	Нет\r\nСлот для карт памяти	Есть\r\nLTE модем	Нет\r\nВес устройства, кг	1.28\r\nВес в упаковке, кг	2',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(190,16,521,'U2412M','U2412M','nix https://www.nix.ru/autocatalog/lcd_dell/24-ZHK-monitor-DELL-U2412M-039203-200476-s-povorotom-ekrana-LCD-1920x1200-D-Sub-DVI-DP-USB20Hub_123991.html','Диагональ 24\" @1920x1200 (16:10)\r\nМатрица IPS Матовая\r\nПоворотный экран\r\nПорты DVI, VGA, DP\r\nUSB концентратор на 4x USB 2.0',0,'',237,NULL,0,0,NULL,0,0,'2023-09-08 03:20:18',NULL,NULL),(191,16,520,'243V7QDSB','','https://www.philips.ru/c-p/243V7QDSB_00/full-hd-lcd-monitor','Серия	V line\r\nМодель	243V7найти похожий монитор\r\nДиагональ	23.8\" (60.5 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	IPS - данные из неофициальных источников\r\nЭкран\r\nЧастота обновления кадров	60 Гц\r\nFlicker free	Да\r\nФормат матрицы	16:9\r\nГлубина цвета матрицы	16,78 миллионов цветов\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	На основе белых светодиодов (WLED)\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 10M:1 - динамическая (SmartContrast)\r\nВремя отклика	5 мс GtG\r\nУгол обзора LCD-матрицы	178° по горизонтали, 178° по вертикали при CR выше 10\r\nТочка LCD-матрицы	0.275 мм\r\nПлотность пикселей (ppi)	93 ppi\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-5° ~ 20°\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельнокрепеж к стене\r\nИнтерфейс, разъемы и выходы\r\nИнтерфейс монитора	DVI, HDMI, VGA (15-пиновый коннектор D-sub), Аудиовыход миниджек 3.5 ммКупить кабель\r\nПоддержка HDCP	Есть\r\nВозможности управления\r\nЯзыки меню монитора/телевизора	Русский\r\nLow Blue Light	LowBlue (снижение интенсивности синего цвета уменьшает усталость глаз)\r\nПитание\r\nБлок питания монитора или телевизора	Встроенный\r\nПотребление энергии	13.8 Вт - типичное; 0.5 Вт - в режиме ожидания\r\nКомплект поставки и опции\r\nКомплект поставки	Диск с документацией и ПО, кабель VGA, кабель питаниякомплект №1комплект №2\r\nСовместимость\r\nРабочая температура	0 ~ 40°C\r\nПрочие характеристики\r\nБезопасность	Слот для Kensington Lockкупить замок Kensington Lock\r\nЛогистика\r\nРазмеры (ширина x высота x глубина)	540 x 415 x 209 мм - с подставкой; 540 x 325 x 45 мм - без подставки\r\nВес	3.5 кг - с подставкой; 3.1 кг - без подставки\r\nРазмеры упаковки (измерено в НИКСе)	58.63 x 45.88 x 11.44 см\r\nВес брутто (измерено в НИКСе)	4.65 кг',0,'DVI\r\nHDMI\r\nVGA (15-пиновый коннектор D-sub)\r\nАудиовыход миниджек 3.5 мм',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(192,16,520,'273V5','273V5LHSB','https://www.philips.ru/c-p/273V5LHSB_00/lcd-monitor-with-smartcontrol-lite','Серия	V line\r\nМодель	273V5LHSBнайти похожий монитор\r\nДиагональ	27\" (68.6 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	TN\r\nЭкран\r\nЧастота обновления кадров	60 Гц\r\nФормат матрицы	16:9\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	На основе белых светодиодов (WLED)\r\nЯркость матрицы	300 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 10M:1 - динамическая (SmartContrast)\r\nВремя отклика	5 мс\r\nУгол обзора LCD-матрицы	170° по горизонтали, 160° по вертикали при CR выше 10\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-5° ~ 20°\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельнокрепеж к стене\r\nИнтерфейс, разъемы и выходы\r\nИнтерфейс монитора	HDMI, VGA (15-пиновый коннектор D-sub), разъем 3.5 мм для подключения наушников (только для HDMI)Купить кабель\r\nПоддержка HDCP	Есть\r\nВозможности управления\r\nЯзыки меню монитора/телевизора	Русский\r\nПитание\r\nБлок питания монитора или телевизора	Встроенный\r\nПотребление энергии	23.14 Вт - типичное; 0.5 Вт - в режиме ожидания\r\nКомплект поставки и опции\r\nКомплект поставки	Диск с документацией и ПО, кабель питания, кабель VGAкомплект №1комплект №2\r\nСовместимость\r\nРабочая температура	0 ~ 40°C\r\nПрочие характеристики\r\nБезопасность	Слот для Kensington Lockкупить замок Kensington Lock\r\nЛогистика\r\nРазмеры (ширина x высота x глубина)	646 x 471 x 240 мм - с подставкой; 646 x 398 x 57 мм - без подставки\r\nВес	4.53 кг - с подставкой; 4 кг - без подставки\r\nРазмеры упаковки (измерено в НИКСе)	69.42 x 47.05 x 13.43 см\r\nВес брутто (измерено в НИКСе)	6.8 кг',0,'HDMI,\r\nVGA (15-пиновый коннектор D-sub)\r\nразъем 3.5 мм для подключения наушников (только для HDMI)',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:28',NULL,NULL),(193,16,521,'U2415','','https://dell-partner.ru/u2415/\r\nhttps://www.ixbt.com/monitor/dell-u2415.shtml','Диагональ: 24.1\" @1920 x 1200 (16:10)\r\nТип матрицы: AH-IPS Матовая\r\nПоворотный экран, регулировка по высоте\r\n2x HDMI, DP, miniDP, \r\nВыход DP, аудиовыход джек 3.5 мм\r\nUSB-концентратор на 5 портов USB 3.0\r\nКабель Mini DP -> DP',0,'',240,'',0,0,'',0,0,'2025-05-14 09:19:21',NULL,NULL),(194,7,180,'Catalyst WS-C2960X-48TS-L','WS-C2960X-48TS-L','','Управляемый L2+ коммутатор\r\n48x 1GE, 4x SFP\r\n10,1 MPPS\r\n108 Gbit/s\r\nPoE отсутствует\r\nПотребление 49Ватт',0,'Gi1/0/1\r\nGi1/0/2\r\nGi1/0/3\r\nGi1/0/4\r\nGi1/0/5\r\nGi1/0/6\r\nGi1/0/7\r\nGi1/0/8\r\nGi1/0/9\r\nGi1/0/10\r\nGi1/0/11\r\nGi1/0/12\r\nGi1/0/13\r\nGi1/0/14\r\nGi1/0/15\r\nGi1/0/16\r\nGi1/0/17\r\nGi1/0/18\r\nGi1/0/19\r\nGi1/0/20\r\nGi1/0/21\r\nGi1/0/22\r\nGi1/0/23\r\nGi1/0/24\r\nGi1/0/25\r\nGi1/0/26\r\nGi1/0/27\r\nGi1/0/28\r\nGi1/0/29\r\nGi1/0/30\r\nGi1/0/31\r\nGi1/0/32\r\nGi1/0/33\r\nGi1/0/34\r\nGi1/0/35\r\nGi1/0/36\r\nGi1/0/37\r\nGi1/0/38\r\nGi1/0/39\r\nGi1/0/40\r\nGi1/0/41\r\nGi1/0/42\r\nGi1/0/43\r\nGi1/0/44\r\nGi1/0/45\r\nGi1/0/46\r\nGi1/0/47\r\nGi1/0/48',286,'',0,0,'',0,0,'2023-09-13 16:26:46','admin',NULL),(195,7,180,'Catalyst WS-C2960RX-48TS-L','C2960RX-48TS','cisco https://www.cisco.com/c/en/us/products/collateral/switches/catalyst-2960-x-series-switches/datasheet_c78-728232.html','Управляемый\r\n48x GE ports, 4x SFP ports\r\nPoE отсутствует',0,'eth1\r\neth2\r\neth3\r\neth4\r\neth5\r\neth6\r\neth7\r\neth8\r\neth9\r\neth10\r\neth11\r\neth12\r\neth13\r\neth14\r\neth15\r\neth16\r\neth17\r\neth18\r\neth19\r\neth20\r\neth21\r\neth22\r\neth23\r\neth24\r\neth25\r\neth26\r\neth27\r\neth28\r\neth29\r\neth30\r\neth31\r\neth32\r\neth33\r\neth34\r\neth35\r\neth36\r\neth37\r\neth38\r\neth39\r\neth40\r\neth41\r\neth42\r\neth43\r\neth44\r\neth45\r\neth46\r\neth47\r\neth48',244,NULL,0,0,NULL,0,0,'2023-09-08 03:20:19',NULL,NULL),(196,7,180,'Catalyst WS-C2960X-24TD-L','WS-C2960X-24TD','','Catalyst 2960X (WS-C2960X-24TD-L)',0,'',NULL,'',0,0,'',0,0,'2023-09-10 06:22:18','admin',NULL),(197,7,180,'Catalyst WS-C2960-24TC-L','WS-C2960-24TC','','Catalyst WS-C2960-24TC-L',0,'',NULL,'',0,0,'',0,0,'2023-09-10 06:16:42','admin',NULL),(198,7,180,'SG350-28SFP','SG350-28SFP','','SG350-28SFP 28-Port Gigabit SFP Managed Switch',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(199,13,35,'255 G8 (27K36EA)','HP 255 G8','https://hp-rus.com/catalog/noutbuki/uma-ryzen5-3500u-255-g8-15-6-fhd-sva-250-nwbz-8gb-/','HP 255 G8 (27K36EA)\r\n',0,'',245,NULL,0,0,NULL,0,0,'2023-09-08 03:20:20',NULL,NULL),(200,3,522,'X10SRG-F','','','ВНИМАНИЕ!!! Сервер НЕ ДОЛЖЕН иметь соединение с Интернетом! Иначе установленная на нём программа достучится до автора и на Азимут подадут в суд.\r\nИспользуется Курбаковым из НТЦ-3 для проведения сложных ресурсоёмких расчетов',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(201,16,116,'S22A100N','S22A100N','','Тип монитора ЖК\r\nДиагональ 21.5 \"\r\nМакс. разрешение 1920x1080\r\nСоотношение сторон 16:9\r\nТип LED-подсветки WLED\r\nТип матрицы экрана TN\r\nЯркость 200 кд/м2\r\nКонтрастность 600:1\r\nДинамическая контрастность 5000000:1\r\nВремя отклика 5 мс',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(202,16,520,'276E9','PHL 276E9Q','','Производитель	Philips\r\nСерия	E Line\r\nМодель	276E9найти похожий монитор\r\nДиагональ	27\" (68.6 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	IPS\r\nЭкран\r\nЧастота обновления кадров	75 Гц\r\nТехнологии FreeSync и G-Sync	AMD FreeSync\r\nFlicker free	Да\r\nФормат матрицы	16:9\r\nГлубина цвета матрицы	16.7M Color\r\nПоверхность экрана	Матовая\r\nЦветовой охват	124% sRGB\r\nПодсветка LCD-матрицы	На основе белых светодиодов (WLED)\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 20M:1 - динамическая (SmartContrast)\r\nВремя отклика	5 мс GtG - типичное\r\nУгол обзора LCD-матрицы	178° по горизонтали, 178° по вертикали при CR выше 10\r\nТочка LCD-матрицы	0.311 мм\r\nПлотность пикселей (ppi)	82 ppi\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nАудио\r\nКолонки	Встроенные; 2 x 3 Вт\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный, серебристый\r\nУправление	Джойстик\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-5° ~ 20°\r\nАбсолютно плоская передняя панель (рамка не выступает)	Узкая рамка\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельно',0,'DisplayPort\r\nHDMI\r\nVGA (15-пиновый коннектор D-sub)\r\nаудиовход миниджек 3.5 мм, разъем 3.5 мм для подключения наушников',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(203,16,523,'2460X','2460X','','AOC\r\nМодель	e2460Sh\r\nДиагональ	24\" (61 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	TN\r\nЭкран\r\nЧастота обновления кадров	75 Гц\r\nФормат матрицы	16:9\r\nГлубина цвета матрицы	16,78 миллионов цветов\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	На основе белых светодиодов (WLED)\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 20M:1 - динамическая (DCR)\r\nВремя отклика	1 мс\r\nУгол обзора LCD-матрицы	170° по горизонтали, 160° по вертикали при CR выше 10\r\nТочка LCD-матрицы	0.276 мм\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nАудио\r\nКолонки	Встроенные\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-5° ~ 20°\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельно',0,'DVI\r\nHDMI\r\nVGA (15-пиновый коннектор D-sub)\r\nаудиовход миниджек 3.5 мм, аудиовыход миниджек 3.5 мм',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(204,16,520,'240V5','240V5','','Производитель	Philips\r\nСерия	V line\r\nМодель	240V5найти похожий монитор\r\nДиагональ	23.8\" (60.5 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	ADS-IPS\r\nЭкран\r\nЧастота обновления кадров	60 Гц\r\nФормат матрицы	16:9\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	На основе белых светодиодов (WLED)\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 10M:1 - динамическая (SmartContrast)\r\nВремя отклика	5 мс - GtG SmartResponse; 14 мс - GtG\r\nУгол обзора LCD-матрицы	178° по горизонтали, 178° по вертикали при CR выше 10\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-5° ~ 20°\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельно',0,'DVI\r\nHDMI\r\nVGA (15-пиновый коннектор D-sub)',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(205,16,520,'274E5','274E5','','Производитель	Philips\r\nСерия	E Line\r\nМодель	274E5найти похожий монитор\r\nДиагональ	27\" (68.6 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	AH-IPS\r\nЭкран\r\nЧастота обновления кадров	75 Гц\r\nФормат матрицы	16:9\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	На основе белых светодиодов (WLED)\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 20M:1 - динамическая (SmartContrast)\r\nВремя отклика	14 мс\r\nУгол обзора LCD-матрицы	178° по горизонтали, 178° по вертикали при CR выше 10\r\nТочка LCD-матрицы	0.311 мм\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-5° ~ 20°\r\nАбсолютно плоская передняя панель (рамка не выступает)	Узкая рамка; при выключенном мониторе видна рамка толщиной около 1 мм\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельно',0,'DVI\r\nVGA (15-пиновый коннектор D-sub)',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(206,16,523,'2757Fm','Y2757','','Производитель	AOC\r\nМодель	i2757Fmнайти похожий монитор\r\nДиагональ	27\" (68.6 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	AH-IPS - данные из неофициальных источников\r\nЭкран\r\nЧастота обновления кадров	76 Гц\r\nФормат матрицы	16:9\r\nГлубина цвета матрицы	6 бит/цвет + A-FRC (16.7 млн. цветов) - данные из неофициальных источников\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	На основе белых светодиодов (WLED)\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 20M:1 - динамическая (DCR)\r\nВремя отклика	5 мс GtG\r\nТехнологии компенсации времени отклика	Overdrive\r\nУгол обзора LCD-матрицы	178° по горизонтали, 178° по вертикали при CR выше 10\r\nТочка LCD-матрицы	0.311 мм\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Серебристый, черный\r\nУправление	Сенсорные кнопки\r\nРегулировка положения экрана	Наклон\r\nАбсолютно плоская передняя панель (рамка не выступает)	Узкая рамка; при выключенном мониторе видна рамка толщиной около 1 мм\r\nКрепление монитора или телевизора к стене	Нет',0,'2 x HDMI\r\nVGA (15-пиновый коннектор D-sub)\r\nразъем 3.5 мм для подключения наушников (только для HDMI)',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(207,16,116,' Samsung S22B350T','S22B350T','','Производитель	Samsung\r\nМодель	S22B350B\r\nДиагональ	21.5\" (54.6 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	TN\r\nЭкран\r\nЧастота обновления кадров	75 Гц\r\nФормат матрицы	16:9\r\nГлубина цвета матрицы	6 бит/цвет + Hi-FRC (16.7 млн. цветов)\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	Светодиодная (LED) подсветка\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая\r\nВремя отклика	5 мс\r\nУгол обзора LCD-матрицы	170° по горизонтали, 160° по вертикали\r\nТочка LCD-матрицы	0.2482 мм\r\nПрофили коррекции изображения	MagicBright 3 (режим динамической контрастности, «Текст», «Интернет», «Игры», «Спорт», «Кино», «Пользовательский режим»)\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный глянцевый, бордовый\r\nУправление	Сенсорные кнопки\r\nРегулировка положения экрана	Наклон\r\nКрепление монитора или телевизора к стене	Нет',0,'DVI\r\nVGA (15-пиновый коннектор D-sub)',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(208,16,524,'VG2436','VG2436','','Производитель	ViewSonic\r\nМодель	VG2436Wm-LEDнайти похожий монитор\r\nДиагональ	23.6\" (59.9 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	TN\r\nЭкран\r\nЧастота обновления кадров	75 Гц\r\nФормат матрицы	16:9\r\nГлубина цвета матрицы	6 бит/цвет + Hi-FRC (16.7 млн. цветов)\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	Светодиодная (LED) подсветка\r\nЯркость матрицы	300 кд/м2 - типичная\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 20M:1 - динамическая\r\nВремя отклика	5 мс\r\nУгол обзора LCD-матрицы	170° по горизонтали, 160° по вертикали\r\nТочка LCD-матрицы	0.272 мм\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nАудио\r\nКолонки	Встроенные\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Поворот экрана на 90° (Pivot)/Высота/Наклон/Поворот влево-вправо (swivel)\r\nПоворот экрана на 90°	С поворотом экрана на 90° (портретный режим)\r\nИзменение высоты экрана	135 мм\r\nУглы наклона монитора	-5° ~ 20°\r\nУглы поворота относительно подставки	±180°\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельно',0,'DVI\r\nVGA (15-пиновый коннектор D-sub)\r\nаудиовход миниджек 3.5 мм',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(209,8,500,'Back-UPS BK500-RS','BK500-RS','https://www.apc.com/shop/ru/ru/products/APC-Back-UPS-CS-500-230-/P-BK500-RS','Максимальная задаваемая мощность(Вт) 300 Ватт / 500ВА\r\nВремя переключения 4 ms typical : 8 ms maximum\r\nВходная частота 50/60 Гц +/- 5 Гц Ручное переключение\r\nДиапазон входного напряжения при работе от сети 160 - 282 Регулируем., 180 - 260В\r\nКоличество кабелей питания 1\r\nТип батарей Свинцово-кислотная батарея\r\nТиповое время перезарядки 6 часов\r\nМощность зарядного устройства (Вт) 14 Ватт\r\nСменная батарея RBC2\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(210,8,502,'Back Comfo Pro 800','BCP800','https://ippon.ru/catalog/item/backcomfopronew','Полная мощность	800 ВА\r\nАктивная мощность	480 Вт\r\nФорма напряжения	Модифицированная синусоида\r\nВремя переключения	Обычно 2-6 мс, максимально 10 мс\r\nДиапазон напряжения	162-268 В\r\nБатареи Тип	Необслуживаемые герметичные свинцово-кислотные\r\n12В/9Ач х 1 шт\r\nВремя автономной работы при 30% нагрузке	16,7 мин\r\nВремя автономной работы при 70% нагрузке	1 мин\r\nВремя заряда из состояния полного разряда	6 часов до 90% заряда\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(211,8,500,'Back-UPS BK500EI','BK500EI','https://www.apc.com/shop/ru/ru/products/APC-Back-UPS-500-230-/P-BK500EI','Максимальная задаваемая мощность(Вт) 300 Ватт / 500ВА\r\nВремя переключения 6 ms typical : 10 ms maximum\r\nВходная частота 50/60 Гц +/- 5 Гц Ручное переключение\r\nДиапазон входного напряжения при работе от сети 160 - 300 Регулируем., 180–266В\r\nКоличество кабелей питания 1\r\nТип батарей Свинцово-кислотная батарея\r\nТиповое время перезарядки 8 часов\r\nМощность зарядного устройства (Вт) 10 Ватт\r\nСменная батарея RBC2',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(212,17,1,'C310 HD720p','C310 HD720p','https://www.logitech.com/ru-ru/products/webcams/c310-hd-webcam.960-001065.html#specs','Максимальное разрешение 720p\r\nКоличество мегапикселей у камеры: 1.2\r\nТип фокусировки: постоянный фокус\r\nВстроенный микрофон: Монофонический\r\nДиапазон микрофона: До 1 м\r\nПоле обзора по диагонали: 60°\r\nУниверсальное крепление для ноутбука, ЖК-экрана или монитора\r\nПодключение кабелем длиной 1,5 м',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(213,1,504,'Race VT552','Race VT552','','Системный блок:\r\ni9-9900K\r\n32gb DDR4\r\nSSD 512gb\r\nHDD 2Tb\r\nNVIDIA GeForce GTX 1050 Ti\r\nWindows Pro',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(214,8,500,'Power-Saving BR900GI','BR900GI','https://www.apc.com/shop/ru/ru/products/APC-Back-UPS-Pro-900-230-/P-BR900GI','Мощность 540 Ватт / 900ВА\r\n2 аккумулятора 12В, 7 Ач\r\n4х розетки C13 от батарей\r\n4х розетки C13 только фильтрация\r\nСменная батарея APCRBC123\r\nпри нагрузке 100 Вт: 59 мин.\r\nпри нагрузке 200 Вт: 25 мин.\r\nпри нагрузке 300 Вт: 14 мин.',0,'',248,NULL,0,0,NULL,0,0,'2023-09-08 03:20:21',NULL,NULL),(215,16,116,'E2220NW','SME2220NW','https://www.samsung.com/ru/support/model/LS22CLNSB/EN/','22 дюйма',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(216,16,116,'T240','SyncMaster T240','https://market.yandex.ru/product--24-monitor-samsung-syncmaster-t240-1920x1200-75-gts-tn/2415365?cpa=1','24 дюйма',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(217,16,116,'214T','SyncMaster 214T','https://market.yandex.ru/product--21-3-monitor-samsung-syncmaster-214t-1600x1200-75-gts-pva/925412?cpa=1','21.3 дюйма',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(218,16,116,'S24D590','S24D590','https://market.yandex.ru/product--23-6-monitor-samsung-s24d590pl-1920x1080-75-gts-ad-pls/10789626?cpa=1','23.6 дюйма',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(219,16,203,'90GX2 Pro','90GX2 Pro','https://market.yandex.ru/product--monitor-nec-multisync-90gx2-pro-19/4974420/spec','19 дюймов',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(220,16,521,'P2412H','P2412H','https://market.yandex.ru/product--24-monitor-dell-p2412h-1920x1080-tn/7763189?cpa=1&nid=26910011','24 дюйма',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(221,16,116,'S22C200B','S22C200B','','Производитель	Samsung\r\nМодель	S22C200B\r\nДиагональ	21.5\" (54.6 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	TN\r\nЭкран\r\nФормат матрицы	16:9\r\nГлубина цвета матрицы	6 бит/цвет + Hi-FRC (16.7 млн. цветов)\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	Светодиодная (LED) подсветка\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая\r\nВремя отклика	5 мс\r\nУгол обзора LCD-матрицы	170° по горизонтали, 160° по вертикали при CR выше 10\r\nПрофили коррекции изображения	MagicBright 3 (режим динамической контрастности, «Текст», «Интернет», «Игры», «Спорт», «Кино», «Пользовательский режим»)\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-1° ~ 20°\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельно',0,'DVI\r\nVGA (15-пиновый коннектор D-sub)',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(222,8,500,'Back-UPS BE550G-RS','','','Тип	Off-Line/Stand-By/back-up\r\nВыходная мощность (полная), VA	550\r\nВыходная мощность (активная), Вт	330\r\nНоминальное напряжение / частота	230 В\r\nВходное напряжение	180 - 266 В\r\nВыходное напряжение	Максимум 230 В\r\nВремя работы на батареях	1 час 20 минут при 10% нагрузке, 3 минуты при 100% нагрузке\r\nСреднее время подзарядки	16 часов\r\nУровень шума	40 дБ\r\nКоличество розеток	8\r\nТип розеток	Евророзетки\r\nИнтерфейс	USB\r\nПорты и разъемы	3 x RJ-45,\r\n\r\n4 x \"евророзетки\" (Батарейное резервное питание),\r\n\r\n4 x IEC 320 C13 (Защита от всплесков напряжения)\r\nЗащита устройства	Есть,\r\n\r\nЗащита от всплесков напряжения\r\nИндикация	Звуковая индикация,\r\n\r\nСветодиодная индикация\r\nДополнительно	Защита от утечек\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(223,13,235,'Portege R930-KMK','Portege R930-KMK','','Старый ноут i53340 6G QM77 500 13W с модемом 3G.\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(224,16,525,'FP202W','FP202W','','Старый старый моник',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(225,8,500,'Smart-UPS 750 SUA750I','SUA750I','https://www.apc.com/shop/uz/ru/products/APC-Smart-UPS-750-USB-230-/P-SUA750I','Максимальная задаваемая мощность(Вт) 500 Ватт / 750ВА\r\nДиапазон входного напряжения при работе от сети 151 - 302 Регулируем., 160–286В\r\nТип формы напряжения Синусоидальный сигнал\r\nТип батарей Свинцово-кислотная батарея\r\nТиповое время перезарядки 3часов\r\nНоминальное напряжение батареи 24 В\r\nСменная батарея RBC48\r\nМощность зарядного устройства (Вт) 50 Ватт\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(227,16,55,'VS228H','VS228H','https://market.yandex.ru/product--21-5-monitor-asus-vs228h-1920x1080-75-gts-tn/7302664/spec','диагональ: 21.50 \", тип матрицы экрана: TN, макс. разрешение: 1920x1080, время отклика: 5 мс, соотношение сторон: 16:9, яркость: 250 кд/м2, интерфейсы видео: вход VGA, вход DVI-D, вход HDMI',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(228,16,526,'IPS236V','IPS236V','https://www.lg.com/ru/monitors/lg-IPS236V-ips-monitors','23 дюйма',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(229,13,35,'ProBook 450 G6','ProBook 450 G6','https://support.hp.com/ru-ru/document/c06195735#AbT2','Ноутбук 14 дюймов',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(230,7,515,'Baseline Switch 2250 Plus','3Com 2250','https://market.yandex.ru/product--kommutator-3com-baseline-switch-2250-plus/922860?cpa=1','48 port 100M\r\n2 port 1000M',0,'1\r\n2\r\n3\r\n4\r\n5\r\n6\r\n7\r\n8\r\n9\r\n10\r\n11\r\n12\r\n13\r\n14\r\n15\r\n16\r\n17\r\n18\r\n19\r\n20\r\n21\r\n22\r\n23\r\n24\r\n25\r\n26\r\n27\r\n28\r\n29\r\n30\r\n31\r\n32\r\n33\r\n34\r\n35\r\n36\r\n37\r\n38\r\n39\r\n40\r\n41\r\n42\r\n43\r\n44\r\n45\r\n46\r\n47\r\n48\r\n49\r\n50',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(231,7,515,'Baseline Switch 2226 Plus','Baseline 2226','https://market.yandex.ru/product--kommutator-3com-baseline-switch-2226-plus/923214?cpa=1','24 port 100M\r\n2 port 1000M',0,'1\r\n2\r\n3\r\n4\r\n5\r\n6\r\n7\r\n8\r\n9\r\n10\r\n11\r\n12\r\n13\r\n14\r\n15\r\n16\r\n17\r\n18\r\n19\r\n20\r\n21\r\n22\r\n23\r\n24\r\n25\r\n26',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(232,7,180,'SF200-48P','SF200-48P','https://www.cisco.com/c/ru_ru/support/switches/sf200-48p-48-port-10-100-poe-smart-switch/model.html','Cisco Small Business\r\n48 port 100M\r\n2 port 1000M\r\n24 port (из первых 48) POE',0,'FE1\r\nFE2\r\nFE3\r\nFE4\r\nFE5\r\nFE6\r\nFE7\r\nFE8\r\nFE9\r\nFE10\r\nFE11\r\nFE12\r\nFE13\r\nFE14\r\nFE15\r\nFE16\r\nFE17\r\nFE18\r\nFE19\r\nFE20\r\nFE21\r\nFE22\r\nFE23\r\nFE24\r\nFE25\r\nFE26\r\nFE27\r\nFE28\r\nFE29\r\nFE30\r\nFE31\r\nFE32\r\nFE33\r\nFE34\r\nFE35\r\nFE36\r\nFE37\r\nFE38\r\nFE39\r\nFE40\r\nFE41\r\nFE42\r\nFE43\r\nFE44\r\nFE45\r\nFE46\r\nFE47\r\nFE48\r\nGE1\r\nGE2',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:29',NULL,NULL),(233,4,180,'ASA 5515-K9','ASA 5515','https://www.router-switch.com/cisco-asa5515-k9-datasheet-pdf.html','6 port 1000M\r\n',0,'GE0/0\r\nGE0/1\r\nGE0/2\r\nGE0/3\r\nGE0/4\r\nGE0/5',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(234,7,499,'DES-3552','DES-3552','https://www.dlink.ru/ru/products/1/1240.html','48 портов 10/100 BASE-TX\r\n2 порта 10/100/1000 BASE-T',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(235,18,516,'IP Phone MeetingEye 600','MeetingEye 600','На сайте Ipmatika https://ipmatika.ru/products/terminal/yealink-meetingeye-600/','Терминал видеоконференцсвязи \"все-в-одном\" для переговорных комнат среднего размера',0,'1',250,'',0,0,'',0,0,'2023-09-08 03:20:22',NULL,NULL),(236,12,510,'TS-459 Pro II','TS-459 Pro II','https://qnap.ru/ts-459-pro-ii','Софтверный NAS (1 контроллер)\r\nCPU: Intel Atom D525 1(2x 1,8 ГГц )\r\nRAM: 1 ГБ (DDR3) * Может быть расширена до 3 ГБ\r\nHDD: 4x 3,5\"/2,5\" HDD/SDD SATA III Hot swap bay (Up to 4TB)\r\nNET: 2x RJ45 1000Mbit/s\r\nUSB 3.2 Gen1 x2, USB 2.0 x4, ESATA x2',1,'1\r\n2',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(237,7,527,'TL-SG1008D','TP-LINK TL-1008','','8 портовый неуправляемый.\r\nЦена меньше 2 рублей.',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(238,8,500,'Smart-UPS 3000 SUA3000RMI2U',' SUA3000RMI2U','https://www.apc.com/shop/ua/ru/products/APC-Smart-UPS-3000-USB-2U-230-/P-SUA3000RMI2U','Максимальная задаваемая мощность(Вт) 2.7кВт / 3.0кВА\r\nВремя переключения 2 ms typical\r\nТип формы напряжения Синусоидальный сигнал\r\nДиапазон входного напряжения при работе от сети 151 - 302 Регулируем., 160–286В\r\nТиповое время перезарядки 3часов\r\nСменная батарея RBC43\r\nОжидаемый срок службы батареи (лет) - 5\r\nМощность зарядного устройства (Вт) 175 Ватт\r\n\r\n',0,'',251,NULL,0,0,NULL,0,0,'2023-09-08 03:20:22',NULL,NULL),(239,8,500,'Smart-UPS 1000 SUA1000RMI2U','SUA1000RMI2U','https://www.apc.com/shop/ua/ru/products/APC-Smart-UPS-1000-USB-2U-230-/P-SUA1000RMI2U','Максимальная задаваемая мощность(Вт) 670 Ватт / 1.0кВА\r\nТип формы напряжения Синусоидальный сигнал\r\nВремя переключения 2 ms typical\r\nДиапазон входного напряжения при работе от сети 151 - 302 Регулируем., 160–286В\r\nТиповое время перезарядки 3часов\r\nСменная батарея RBC23\r\nОжидаемый срок службы батареи (лет) 4 - 6\r\nМощность зарядного устройства (Вт) 112 Ватт\r\n',0,'',253,NULL,0,0,NULL,0,0,'2023-09-08 03:20:22',NULL,NULL),(240,8,502,'Smart Winner 3000','Smart 3000','https://market.yandex.ru/product--interaktivnyi-ibp-ippon-smart-winner-3000/985354/spec','Без дисплея. \r\nОтображение информации светодиодные индикаторы\r\nВыходная мощность (полная) 3000 ВА\r\nВыходная мощность (активная) 2100 Вт\r\nВремя работы при полной нагрузке 5 мин\r\nФорма выходного сигнала синусоида\r\nВходное напряжение 168 В - 288 В\r\nВремя зарядки 3 ч\r\n\r\n\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(241,13,193,'Yoga C940-14IIL (81Q9007LRU)','Yoga C940','Lenovo shop https://shop.lenovo.ru/product/81Q9007LRU/','Экран: сенс. глянец 14\" IPS@FullHD\r\nПроцессор: i5 1035G4 (4x1.1GHz up to 3.7GHz)\r\nПамять: 16 Gb LPDDR4X\r\nSSD: m.2 nVME 1024Gb\r\nВидео: Iris Plus Graphics\r\nОС: Windows 10 Home\r\nТрансформер\r\nПодсветка клавиатуры\r\nСканер отпечатка пальцев',0,'',255,NULL,0,0,NULL,0,0,'2023-09-08 03:20:23',NULL,NULL),(242,6,35,'Color LaserJet 5550','HP 5550','https://www.nix.ru/autocatalog/printers_hp/temporary_product_page_46174.html','Принтер А3, цветной, лазерный.\r\n120 000 страниц в месяц - максимальная нагрузка\r\nHP PCL 5c, HP PCL 6, PDF\r\nРесурс цветного картриджа или контейнера с цветными чернилами	12000 страниц (каждый)\r\nРесурс черного картриджа или контейнера с черными чернилами	13000 страниц\r\nФьюзер (печка)	Q3985A; ресурс - 150000 страниц\r\nКартридж черный	C9730A (№645A)купить черный картридж\r\nTransfer Unit	C9734B; ресурс - 120000 страниц\r\nКартридж цветной	C9731A (№645A) (голубой), C9732A (№645A) (желтый), C9733A (№645A) (пурпурный)',0,'',258,NULL,0,0,NULL,0,0,'2023-09-08 03:20:24',NULL,NULL),(243,6,35,'LaserJet Pro P1606dn','HP 1606','https://www.nix.ru/autocatalog/printers_hp/HP-LaserJet-Professional-P1606dn-CE749A-A4-25str-min-32Mb-USB20-setevoj-dvustoronnyaya-pechat_98039.html','Принтер А4, ч/б\r\n8000 страниц в месяц - максимальная нагрузка\r\nКартридж черный	CE278A (№78A), CE278L (№78L) - 2100 страниц\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(244,6,35,'LaserJet 1022n','HP LaserJet 1022n','https://support.hp.com/ru-ru/drivers/selfservice/hp-laserjet-1022-printer-series/439424/model/439432',' черно-белая,  лазерная,  A4,  Ethernet (RJ-45), USB,  до 20 стр/мин, количество страниц в месяц: 8000\r\nкартридж: Q2612A (№12A)\r\n\r\n\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(245,13,521,'Latitude 3510','3510','','Процессор: Intel Core i3 10110u 2.1 ГГц\r\nКоличество ядер: 2\r\nКэш-память: 4 Мб\r\nПроизводитель видеопроцессора: Intel\r\nГрафический контроллер: UHD Graphics 620\r\nТип видеокарты: интегрированная\r\nДиагональ экрана: 15.6 \"\r\nРазрешение экрана: 1920x1080 px\r\nПокрытие экрана: антибликовое\r\nОбъем SSD: 256 Гб\r\nОперативная память (RAM): 8 ГБ\r\nМаксимальная оперативная память: 32 ГБ\r\nЧастота памяти: 2666 МГц\r\nТип оперативной памяти: DDR4\r\nПодсветка клавиш: Да\r\nВстроенный модуль Bluetooth: Да\r\nПоддержка Wi-Fi: Да\r\nВстроенные динамики: Да\r\nВстроенный микрофон: Да\r\nКартридер: Да\r\nМатериал корпуса: пластик\r\nВстроенная веб-камера: Да\r\nМаксимальное время работы: 6 ч\r\nТип аккумулятора: Li-Ion\r\nЦвет: серый\r\nГабаритные размеры (В*Ш*Г): 24.8*36.1*1.8 см\r\nВысота: 248 мм\r\nШирина: 361 мм\r\nГлубина: 18 мм\r\nВес: 1.9 кг',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(246,6,112,'LBP-1120','Canon LBP-1120','https://www.nix.ru/autocatalog/printers_canon/Canon-LBP-1120-A4-10-str-min-2400-600dpi-USB-lazernyj_14573.html','A4 (210 x 297 мм) ; 10 стр. / мин. ; Принтер лазерный монохромный; USB; Картридж-EP-22\\C4092A',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(247,12,510,'TS-831XU','TS-831XU','https://qnap.ru/ts-831xu','Сетевой RAID-накопитель, \r\n8 отсеков для HDD 3,5\"/2,5\", \r\n2 x RJ-45 Гигабитный Ethernet\r\n2 x SFP+ Ethernet 10 Гбит/с\r\n4 x USB 3.2 Gen 1 (Сзади)\r\nстоечное исполнение, \r\n1 блок питания\r\nМодификации: TS-831XU-4G',1,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(248,6,528,'BP-PR600 PLUS','BP-PR600 PLUS','Официальный сайт компании Brady:\r\nhttps://brady.su/THT-BP-PLUS-Precision-200-300-600.html','Маркировочный принтер\r\nРазрешение: 600 dpi\r\nСкорость:	до 254 мм/сек\r\nМетод печати:	термотрансферная\r\nМакс.кол-во этикеток в день:	свыше 7000\r\nВысота знака:	от 2 мм\r\nМакс.ширина печати:	 152 мм\r\nМакс.длина печати:	до 2024 мм\r\nЦветность:	Одноцветная печать (Разные цвета)\r\nПечать в несколько строк:	Да\r\nПоворот текста:	Да\r\nСериализация:	Да\r\nШтрих-кодирование:	Да (все коды с CodeSoft, LabelMark, Markware)\r\nПериферийное устройство:	Да\r\nМин. высота этикетки:	5 мм\r\nМакс. ширина этикетки: 116 мм\r\nШирина непрерывных лент:	от 4 до 116 мм\r\nДиам. втулки рулона внутри пр-ра:	25,4 мм (1\") до 76,2 мм (3\")\r\nМакс. диаметр рулона при внутр. размещение: до 210 мм\r\nМакс. длина рулона:	90 м\r\nМатериалы:	свыше 60 видов и более 1500 типоразмеров (ленты, вырубные этикетки, термоусадочная трубка, кабельные бирки, гарантийные наклейки и др.)\r\nДиаметр втулки риббона:	25,40 (1\")\r\nТип намотки: внутренний\r\nШирина риббона:	до 114 мм\r\nЦвет риббона: цветные\r\nДиаметр рулона риббона: до 80 мм\r\nКлавиатура:	да\r\nДисплей:	да\r\nПодсветка экрана:	да\r\nПодключение к ПК:	да\r\nПеремещение файлов:	нет\r\nСовместимость ПО:	CodeSoft, LabelMark, Markware\r\nАвтономный режим:	да\r\nПорт:	USB 2.0 (режим full speed), Internal Ethernet, RS-232C (последовательный), USB для сканера и клавиатуры\r\nПортативность:	нет\r\nПамять:	RAM 64MB / флэш 8MB\r\nСимволы:	любые\r\nИсточник питания:	220 В\r\nТемпература эксплуатации:	10°...35°C\r\nРазмеры:	446 мм (Д) x 274 мм (В) х  190 мм (200dpi), 242 мм (300dpi и 600dpi),\r\nВес:	около 9 кг\r\nГарантия:	12 мес.',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(249,6,529,'110Xi4','110Xi4','Официальный сайт:\r\nhttps://zebraprinters.ru/ru/product/zebra-110xi4/#characters','Ширина печати: 0-102 мм\r\nМаксимальная скорость печати: 355 мм/с\r\nКачество печати: 203, 300, 600 dpi\r\nДиаметр рулона этикеток: 203 мм\r\nНамотка красящей ленты: OUT\r\nДиаметр втулки красящей ленты: 1 дюйм\r\nДоступные опции: Отделитель, Роторный нож\r\nДлина печати: 20-991 мм',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(250,8,508,'EX2200',' EX2200','','Тип: с двойным преобразованием\r\nВыходная мощность (полная): 2200 ВА\r\nВыходная мощность (активная): 1980 Вт\r\nВремя работы при половинной нагрузке: 17 мин\r\nФорма выходного сигнала: синусоида\r\nВходное напряжение: 1-фазное\r\nВыходное напряжение: 1-фазное\r\nКоличество выходных разъемов питания (общее): 9\r\nКоличество выходных разъемов питания (UPS): 9\r\nТип выходных разъемов питания: IEC 320 C13 (компьютерный)\r\nВыходных разъемов:	9\r\nРазъемов с питанием от батареи:	9\r\nТип выходных разъемов питания:	IEC 320 C13 (компьютерный)\r\nИнтерфейсы:	USB, RS-232, слот\r\nВремя при половинной нагрузке:	17 м\r\nОсобенности:	возможность установки в стойку, возможность замены батарей, звуковая сигнализация, подключение дополнительных батарей, ручной By-pass, холодный старт\r\nВысота:	2U',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(251,13,530,'S400','S400','','Экран:	14\" (1366x768)\r\nПроцессор:	Intel Core i5 4210M (2x2.60 ГГц)\r\nПамять:	RAM 4 ГБ, HDD 500 ГБ\r\nВидеокарта:	встроенная, Intel HD Graphics 4600\r\nЯдро процессора: Haswell\r\nКоличество ядер процессора:2\r\nОбъем кэша L2: 512 КБ\r\nОбъем кэша L3: 3 МБ\r\nПамять: 4 ГБ DDR3\r\nМаксимальный размер: 16 ГБ\r\nЭкран: 14 дюймов, 1366x768, широкоформатный',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(252,2,20,'2410','2410','Описание https://deltat.ru/catalog/telefony/avaya-24hh-cifrovoy-telefon/','Тип:	проводной VoIP телефон\r\nГромкая связь (спикерфон):	есть\r\nДисплей:	есть, монохромный\r\nКоличество строк:	5\r\nКоличество мелодий звонка: 8\r\n',0,'',289,'',0,0,'',0,0,'2023-09-15 05:18:11','admin',NULL),(253,6,35,'LaserJet 5000','C4110A','','Процессор:	RISC 100 МГц\r\nИнтерфейс:	LPT, COM\r\nФормат печатных носителей:	A3 (297 x 420 мм), A4 (210 x 297 мм), A5 (210 x 148 мм), 13 x 18 см, A6+ (100 x 165 мм), A6 (4\"x6\", 10 x 15 см)\r\nТипы печатных носителей:	Бумага, Конверт, Бумага самоклеящаяся, Пленка непрозрачная, Пленка прозрачная (Пленки, наклейки, конверты)\r\nDuplex unit (модуль двусторонней печати):	C4113A, приобретается отдельно\r\nКол-во цветов:	1\r\nРазрешение ч/б печати:	1200 dpi\r\nМаксимальная скорость монохромной печати:	16 стр./мин.\r\nРесурс принтера или МФУ:	65 000 страниц в месяц\r\nТип установки картриджа:	Только черный\r\nТип расходных материалов:	Картридж\r\nКартридж черный:	C4129X (№29X) экономичный\r\nРесурс черного картриджа или контейнера с черными чернилами: 10000 страниц A4\r\nПрочее:	Технология \"Transmit Once\"\r\nПотребление энергии:	365 Вт / 40 Вт / 27 ватт\r\nРазмеры (ширина x высота x глубина):	37.5 х 47.5 х 58.5 см\r\nВес:	23 кг\r\nВес брутто:	30 кг',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(254,6,529,'GK420t','GK420t','Описание: \r\nhttps://www.shtrih-m.ru/catalog/zebra/zebra-gk420t/','Эмуляция: EPL II, ZPL II\r\nРабота с одномерными (1D) штрих-кодами: EAN-8, EAN-13, Code 39, Code 93, Logmars, Plessey, Code 128, Standard 2-of-5, Postnet, Code 11, MSI, Codabar, UPC-A, UPC-E\r\nРабота с двумерными (2D) штрих-кодами: PDF417, QR Code, Maxi Code, Data Matrix, Сodablock , Aztec, Code49, Micro PDF417\r\nРазмер памяти (ПЗУ), Mb: 4\r\nМетод печати: Термотрансферная печать\r\nРазрешение печати, dpi: 203\r\nМакс. ширина печати, мм: 104\r\nМакс. ширина бумаги, мм: 108\r\nМин. ширина бумаги, мм: 19\r\nМакс. длина печати, мм: 991\r\nВнешний диаметр рулона бумаги, мм: 127\r\nВнутренний диаметр рулона бумаги (мин.), мм: 12,7\r\nСкорость печати, мм/сек: 127\r\nИнтерфейс: RS-232 - 1 шт, LPT (Parallel) - 1 шт , Ethernet-10/100 (опция), USB - 1 шт\r\nПоддерживаемые шрифты: 16 встроенных масштабируемых ZPL II растровых шрифтов   Один встроенный масштабируемый шрифт ZPL    • Пять встроенных масштабируемых EPL2 растровых шрифтов    • Поддержка заданных пользователем шрифтов и графики - в том числе логотипов заказчика\r\nДатчики и индикация: Наличия этикетки, Расстояния между этикетками\r\nПитание: Сетевой адаптер\r\nДиапазон рабочих температур, °C: +10°C..+40°C\r\nГабариты оборудования (ШхГхВ), мм: 254 х 194 х 191\r\nВес нетто, кг: 2,1',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(255,19,531,'B-SERIES','B-SERIES','Описание:\r\nhttps://www.pos-kkm.ru/russkaya-versiya-sajta/katalog/sensornye-pos-terminaly/article/15-sensornyj-monoblok-elo-touch-b','Размер экрана, \" 15\"\r\nКоличество касаний One touch, Multi touch (мультитач)\r\nКонфигурация системного блока #2:\r\nIntel 1.86GHz Atom Dual-Core N2800 1MB L2 Cache, 2.5 GT/s Direct Media Interface (DMI), Intel NM10 Express, Intel GMA 3600, 2GB 1333MHz DDR3 SO-DIMM on 1 of 2 slots (Expandable to 4GB maximum1 on 2 slots)\r\nКонфигурация системного блока #3:\r\nIntel 3rd Generation Core i3 3.3GHz i3-3220 (Ivy Bridge), 3MB Intel® Smart Cache, 5 GT/s Direct Media Interface (DMI), Intel H61 Express, Intel HD Graphics 2500, 2GB 1333MHz DDR3 SO-DIMM on 1 of 2 slots (Expandable to 8GB maximum2 on 2 slots)\r\nПорты USB 6 x USB 2.0 (4 on I/O, 1 x USB Side Access, 1 x USB Hidden/Protected) Additional internal ports available for optional Elo peripherals.\r\nПоследовательные порты 2 x NATIVE RS-232 Serial (standard)\r\nЭкранное меню управления (OSD) Яркость, контрастность; Фаза; Автоматическая настрой-ка; Вертикальное положение, горизонтальное положе-ние; Синхронизатор; Резкость; Время; Язык; Восстанов-ление; Кнопка питания\r\nБеспроводной порт Optional USB wireless kit available (802.11b/g/n)\r\nКолонки Колонки 2 шт.\r\nДисплей Активная матрица TFT\r\nКоличество цветов, млн 16.2\r\nСоотношение сторон 4:3\r\nРабочая область экрана (Ш x В), мм 304,1х228,1\r\nРазрешение 1280х1024\r\nЯркость, Кд/м² AccuTouch – 200; Intelli Touch - 225\r\nВремя отклика, мс 8\r\nУгол обзора, ° Горизонтальный: +/-70 или 140 общих. Вертикальный: 60/65 или 125 общих\r\nКонтрастность 700:1\r\nИсточник питания AC-DC power adapter\r\nВходное напряжение 100-240 B 50/60 Гц\r\nПотребляемая мощность (номинальная), Вт 36 W\r\nРазмеры (Ш x В x Г), мм 354,4х358,4х363,5\r\nРазмер в коробке (Ш x В x Г), мм 443х258х555\r\nВес, кг 7.1\r\nТемпература, °С Эксплуатации: от 0 до 35° С. При хранении: от - 20 до 60° С.\r\nВлажность, % При эксплуатации: от 20 до 80%. При хранении: от 5 до 95%\r\nСреднее время безотказной работы 50 000 часов\r\nВарианты монтажа 100 mm VESA',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(256,8,532,'Raptor RPT-1500AP','','','Общие характеристики\r\nТип\r\nline-interactive\r\nАктивная мощность \r\n900 Вт\r\nПолная мощность \r\n1500 ВA\r\nВходные параметры\r\nВходное напряжение\r\n165 - 300 В\r\nЧастота входного напряжения\r\n45 - 65 Гц\r\nЧастота входного напряжения\r\nавтоопределение\r\nВходной разъем\r\nEURO\r\nВыходные параметры\r\nВыходные розетки типа IEC320, с батарейной поддержкой\r\n6\r\nНапряжение при питании от батареи\r\n220-240 +/- 5% В\r\nЧастота при питании от батареи\r\n50/60 +/- 1% Гц\r\nВремя переключения на батареи\r\n4 мс\r\nАвтоматический регулятор напряжения\r\nесть\r\nФорма выходного сигнала\r\nступенчатая аппроксимированная синусоида\r\nСтабилизатор выходного напряжения\r\nесть\r\nЗащита\r\nЗащита от короткого замыкания\r\nесть\r\nЗащита от перегрузки\r\nесть\r\nЗащита от глубокого разряда батареи\r\nесть\r\nЗащита телефонной линии\r\nесть\r\nЗащита сети интернет\r\nесть\r\nИнтерфейсы\r\nИнтерфейс USB\r\nесть\r\nОсобенности\r\nИндикация состояния\r\nПитание от аккумулятора, разрядка аккумулятора, перегрузка.\r\nУровень шума\r\n40 дБ\r\nАккумулятор\r\nТип аккумулятора\r\nНеобслуживаемый кислотно-свинцовый\r\nКоличество аккумуляторов\r\n2\r\nНапряжение\r\n12 В\r\nЕмкость\r\n7.2 Ач\r\nВремя заряда, около\r\n4 ч\r\nАвтоматическое тестирование батарей\r\nесть',0,'',259,NULL,0,0,NULL,0,0,'2023-09-08 03:20:24',NULL,NULL),(257,8,508,'9PX 1500i','9PX 1500i','https://www.ups-mag.ru/catalog/ups/eaton/9px/eaton-9px1500irt2u','Топология::On-Line\r\nНоминальная мощность: кВА/кВт:1.5/1.5\r\nФазы на входе:1 фаза\r\nФазы на выходе:1 фаза\r\nВыходной коэффициент мощности (PF): 1\r\nФорма выходного напряжения: Чистый синус\r\nНаличие встроенных АКБ: Да\r\nФорм-фактор: Универсальный\r\nГабариты (ВхШхГ), мм.: 86.5x440x450\r\nВес, кг.: 18.9',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(258,16,116,'C24F390FHI','C24F390','','Производитель	Samsung\r\nСерия	CF390\r\nМодель	C24F390FHIнайти похожий монитор\r\nТип оборудования	ЖК-монитор с изогнутым экраном\r\nДиагональ	23.5\" (59.7 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	VA\r\nИзогнутый экран	Да\r\nЭкран\r\nЧастота обновления кадров	75 Гц\r\nТехнологии FreeSync и G-Sync	AMD FreeSync\r\nФормат матрицы	16:9\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	Светодиодная (LED) подсветка\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	3000:1 - статическая\r\nВремя отклика	4 мс\r\nУгол обзора LCD-матрицы	178° по горизонтали, 178° по вертикали\r\nПрофили коррекции изображения	MagicBright 3 (режим динамической контрастности, «Текст», «Интернет», «Игры», «Спорт», «Кино», «Пользовательский режим»)\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный глянцевый\r\nУправление	Джойстик\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-2° ~ 22°\r\nКрепление монитора или телевизора к стене	VESA 75 x 75 мм; кронштейн для крепления приобретается отдельнокрепеж к стене\r\nИнтерфейс, разъемы и выходы\r\nИнтерфейс монитора	HDMI, VGA (15-пиновый коннектор D-sub), разъем 3.5 мм для подключения наушников (только для HDMI)Купить кабель\r\nПитание\r\nБлок питания монитора или телевизора	Внешний; входит в комплект поставки\r\nПотребление энергии	25 Вт; в режиме ожидания 0.3 Вт\r\nКомплект поставки и опции\r\nКомплект поставки	Диск с документацией и ПО, кабель VGAкомплект №1комплект №2\r\nПрочие характеристики\r\nБезопасность	Слот для Kensington lockкупить замок Kensington Lock\r\nВнешние источники информации\r\nГорячая линия производителя	8-800-555-55-55; ежедневно с 7:00 до 22:00 по московскому времени\r\nЛогистика\r\nРазмеры (ширина x высота x глубина)	548 х 418 x 207 мм\r\nВес	3.3 кг\r\nРазмеры упаковки (измерено в НИКСе)	61.61 x 39.35 x 16.28 см\r\nВес брутто (измерено в НИКСе)	4.85 кг',0,'HDMI, \r\nVGA (15-пиновый коннектор D-sub), \r\nразъем 3.5 мм для подключения наушников (только для HDMI)',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(259,16,525,'19\" BenQ BL902TM','BL902TM','','MPN (код модели производителя)	9H.L5FLA.SBE\r\nПроизводитель	BenQ\r\nМодель	BL902TMнайти похожий монитор\r\nДиагональ	19\" (48.3 см)\r\nРазрешение экрана	1280 x 1024\r\nТип LCD-матрицы	TN\r\nЭкран\r\nФормат матрицы	5:4\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	Светодиодная (LED) подсветка\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 12M:1 - динамическая\r\nВремя отклика	5 мс\r\nУгол обзора LCD-матрицы	170° по горизонтали, 160° по вертикали при CR выше 10\r\nТочка LCD-матрицы	0.294 мм\r\nПрофили коррекции изображения	Режим динамической контрастности, Senseye 3 (Стандартный, Кино, Игра, Фото, sRGB, Эко)\r\nАудио\r\nКолонки	Встроенные; 2 x 1 Вт\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Поворот экрана на 90° (Pivot)/Высота/Наклон/Поворот влево-вправо (swivel)\r\nПоворот экрана на 90°	С поворотом экрана на 90° (портретный режим)\r\nИзменение высоты экрана	130 мм\r\nУглы наклона монитора	-5° ~ 20°\r\nУглы поворота относительно подставки	±45°\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельнокрепеж к стене\r\nИнтерфейс, разъемы и выходы\r\nИнтерфейс монитора	DVI, VGA (15-пиновый коннектор D-sub), аудиовход миниджек 3.5 мм, аудиовыход миниджек 3.5 ммКупить кабель\r\nПоддержка HDCP	Есть\r\nПитание\r\nБлок питания монитора или телевизора	Встроенный\r\nПотребление энергии	25.8 Вт; в режиме ожидания - 0.3 Вт\r\nКомплект поставки и опции\r\nКомплект поставки	Кабель питания, кабель VGA, кабель DVI, аудиокабель, CD-дисккомплект №1комплект №2комплект №3\r\nПрочие характеристики\r\nБезопасность	Слот для Kensington Lockкупить замок Kensington Lock\r\nВнешние источники информации\r\nГорячая линия производителя	(495) 788-72-97\r\nЛогистика\r\nРазмеры (ширина x высота x глубина)	419 x 432 x 223 мм - с подставкой; 419 x 361 x 56 мм - без подставки\r\nВес	5.1 кг\r\nРазмеры упаковки (измерено в НИКСе)	53.83 x 22.49 x 44.12 см\r\nВес брутто (измерено в НИКСе)	8.15 кг',0,'DVI, \r\nVGA (15-пиновый коннектор D-sub), \r\nаудиовход миниджек 3.5 мм, аудиовыход миниджек 3.5 мм',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(260,16,521,'P2414H ','P2414H ','','Производитель	DELL\r\nМодель	P2414Hнайти похожий монитор\r\nДиагональ	23.8\" (60.5 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	AH-IPS - данные из неофициальных источников\r\nЭкран\r\nЧастота обновления кадров	75 Гц\r\nФормат матрицы	16:9\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	Светодиодная (LED) подсветка\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 2M:1 - динамическая\r\nВремя отклика	8 мс GtG\r\nУгол обзора LCD-матрицы	178° по горизонтали, 178° по вертикали\r\nТочка LCD-матрицы	0.2745 мм\r\nПрофили коррекции изображения	Режим динамической контрастности\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный, серебристый\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Поворот экрана на 90° (Pivot)/Высота/Наклон/Поворот влево-вправо (swivel)\r\nПоворот экрана на 90°	С поворотом экрана на 90° (портретный режим)\r\nИзменение высоты экрана	130 мм\r\nУглы поворота относительно подставки	±45°\r\nКрепление монитора или телевизора к стене	VESA 100 x 100 мм; кронштейн для крепления приобретается отдельнокрепеж к стене\r\nИнтерфейс, разъемы и выходы\r\nИнтерфейс монитора	DisplayPort, DVI, VGA (15-пиновый коннектор D-sub)Купить кабель\r\nПоддержка HDCP	Есть\r\nUSB-концентратор монитора	4 порта USB 2.0\r\nПитание\r\nБлок питания монитора или телевизора	Встроенный\r\nПотребление энергии	45 Вт - максимальное; 28 Вт - номинальное; 0.3 Вт - в режиме ожидания\r\nКомплект поставки и опции\r\nКомплект поставки	Кабель питания, кабель DisplayPort, кабель USB, кабель VGA, диск с документацией и ПОкомплект №1комплект №2комплект №3\r\nОпции (можно приобрести дополнительно)	AC511M\r\nСовместимость\r\nРабочая температура	0 ~ 40°C\r\nПрочие характеристики\r\nБезопасность	Слот для Kensington Lockкупить замок Kensington Lock\r\nЛогистика\r\nРазмеры (ширина x высота x глубина)	566 x 369 x 180 мм - 566 x 499 x 180 мм с подставкой; 566 x 335 x 47 мм без подставки\r\nВес	3.51 кг - без подставки\r\nРазмеры упаковки (измерено в НИКСе)	65.55 x 42.94 x 22.5 см\r\nВес брутто (измерено в НИКСе)	7.75 кг',0,'DisplayPort, \r\nDVI, \r\nVGA (15-пиновый коннектор D-sub)',NULL,'',0,0,'',0,0,'2023-09-05 14:10:30',NULL,NULL),(261,16,525,'21.5\" BenQ V2220 ','V2220 ','','Производитель	BenQ\r\nМодель	V2220найти похожий монитор\r\nДиагональ	21.5\" (54.6 см)\r\nРазрешение экрана	1920 x 1080\r\nТип LCD-матрицы	TN\r\nЭкран\r\nФормат матрицы	16:9\r\nПоверхность экрана	Матовая\r\nПодсветка LCD-матрицы	Светодиодная (LED) подсветка\r\nЯркость матрицы	250 кд/м2\r\nКонтрастность LCD-матрицы	1000:1 - статическая, 10M:1 - динамическая\r\nВремя отклика	5 мс\r\nУгол обзора LCD-матрицы	170° по горизонтали, 160° по вертикали при CR выше 10\r\nТочка LCD-матрицы	0.248 мм\r\nПрофили коррекции изображения	Режим динамической контрастности, Senseye 3 (Стандартный, Кино, Игра, Фото, sRGB, Эко)\r\nКорпус и подставка\r\nЦвета, использованные в оформлении	Черный\r\nУправление	Механические кнопки\r\nРегулировка положения экрана	Наклон\r\nУглы наклона монитора	-5° ~ 15°\r\nКрепление монитора или телевизора к стене	Нет\r\nИнтерфейс, разъемы и выходы\r\nИнтерфейс монитора	DVI, VGA (15-пиновый коннектор D-sub)Купить кабель\r\nПоддержка HDCP	Есть\r\nПитание\r\nБлок питания монитора или телевизора	Внешний; входит в комплект поставки\r\nПотребление энергии	25 Вт - максимальное; 1 Вт - в режиме ожидания\r\nКомплект поставки и опции\r\nКомплект поставки	Кабель VGA, CD-дисккомплект №1комплект №2\r\nВнешние источники информации\r\nГорячая линия производителя	(495) 788-72-97\r\nЛогистика\r\nРазмеры (ширина x высота x глубина)	523 x 394 x 171 мм\r\nВес	3.3 кг\r\nРазмеры упаковки (измерено в НИКСе)	59.5 x 45.5 x 11.5 см\r\nВес брутто (измерено в НИКСе)	4.62 кг',0,'DVI, \r\nVGA (15-пиновый коннектор D-sub)',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(263,12,496,'OceanStor 5500 v3','','','8 контроллеров (макс)\r\n2 блока питания (макс)\r\n48Gb cache RAM на контроллер\r\nдо 24 дисков в корпус с контроллерами\r\nдо 750 дисков с учетом доп. полок при 2х контроллерах\r\nСетевые порты \r\n  1 Гбит/с Ethernet,\r\n  10 Гбит/с FCoE,\r\n  10 Гбит/с TOE,\r\n  16 Гбит/с FC,\r\n  56 Гбит/с InfiniBand,\r\n  SAS 3.0 (внутренний, 4 x 12 Гбит/с на порт)\r\nКорпус контроллеров - 2U\r\nДисковые полки 2 или 4U',1,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(264,8,508,'5SC750i','5SC750i','https://eaton-powerware.ru/5SC750i.html','Диапазон входного напряжения без перехода на батареи при 100% нагрузке:	184-276В\r\nДиапазон частоты:	45-55 Гц\r\nНоминальное напряжение:	230V (+6/–10 %)  (регулируется 220 В / 230 В / 240 В)\r\nНоминальная выходная частота:	50/60 Гц +/- 0,1 % (автоопределение)\r\nРозетки на выходе	(6): IEC-320-C13	 \r\nПорт RS-232:	Есть\r\nПорт USB:	Есть (HID)\r\nСлот для дополнительных карт:	нет\r\nАвтоматическое тестирование батарей:	есть\r\nЗащита от полного разряда:	есть\r\nШум:	< 40 dB\r\nРабочая температура:	от 0 до 35°C\r\nБезопасность:	МЭК/EN 62040-1, UL 1778\r\nЭлектромагнитная совместимость:	МЭК//EN 62040-2, МЭК//EN 62040-3 (характеристики)\r\nПодтверждения:	CE, отчёт CB, TÜV',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(265,8,502,'Smart Winner 750','SW750','https://mcgrp.ru/manual/ippon/smart-winner-750','Тип:	интерактивный\r\nВыходная мощность:	750 ВА / 500 Вт\r\nВремя работы при полной нагрузке:	5 мин\r\nФорма выходного сигнала:	синусоида\r\nВремя переключения на батарею:	4 мс\r\nМакс. поглощаемая энергия импульса:	230 Дж\r\nКоличество выходных разъемов питания:	4\r\nТип выходных разъемов питания:	IEC 320 C13 (компьютерный)\r\nВозможность установки в стойку:	есть\r\nНа входе:	1-фазное напряжение\r\nНа выходе:	1-фазное напряжение\r\nВходное напряжение:	154 - 264 В\r\nИнтерфейсы:	USB, RS-232\r\nОтображение информации:	светодиодные индикаторы\r\nЗвуковая сигнализация:	есть\r\nХолодный старт:	есть\r\nВремя зарядки:	3 час\r\nВозможность замены батарей:	есть\r\nЗащита от перегрузки:	есть\r\nЗащита от высоковольтных импульсов:	есть\r\nФильтрация помех:	есть\r\nЗащита от короткого замыкания:	есть\r\nЗащита телефонной линии:	есть\r\nЗащита локальной сети:	есть\r\nЦвет:	черный\r\nГабариты (ШxВxГ):	235x86x383 мм\r\nВес:	8.6 кг',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(266,1,509,'Veriton M2631','VM2631','https://www.xcom-shop.ru/acer_veriton_m2631_421147.html','Процессор: Intel Core i5 4460, 3,2ГГц (4 ядра)\r\nПамять: 4 Гб (DIMM DDR3)\r\nНМЖД: HDD 1 Тб\r\nВидео: Intel HD 4600\r\nОС: Windows 8 Pro\r\nКоличество PS/2: 2 шт\r\nКоличество USB 2.0: 4 шт\r\nCOM (serial, DB9 RS232): 1 шт\r\nКоличество сетевых карт: 1 шт\r\nТип сетевых интерфейсов: LAN 1000 Мбит/с (RJ-45)\r\nЛинейные аудио разъемы: Line-in, Line-out, Mic-in\r\nDVI-D: 1 шт\r\nHDMI-Out: 1 шт\r\nРасположение серийного номера сбоку на корпусе.\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(267,8,508,'EX 1000 RT2U','EX 1000 RT2U','https://eaton-powerware.ru/eaton-ex-1000-rt2u-68182.html','Топология:	Двойное преобразование on - line, с автоматическим байпасом и системой корректировки коэффициента мощности\r\nКонфигурация:	стойка/башня\r\nМощность (ВА/Вт):	1000/900\r\nМаксимальный номинал с аккумуляторными блоками EXB: 800 W\r\nЦвет:	серый металлик\r\nВходная розетка:	(1шт) IEC-320-C14\r\nДиапазон входного напряжения без перехода на батареи:	от 100 /120 /140 /160 В до 284 В Нижние пределы при=66% номинальной мощности (ВА)\r\nДиапазон входной частоты без перехода на батареи:	40 до 70 Гц\r\nНоминальное напряжение:	230 В (с возможностью регулировки до 200 /208 /220 /240 /250 В)\r\nРозетки на выходе:	(6шт) IEC-320-C13\r\nРозетки с возможностью удаленного управления:	2 независимые группы: 2 + 1 IEC C13 (10A) розетки\r\nНоминальная выходная частота:	50 /60 Гц, автоматический выбор или режим преобразователя частоты',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(268,13,521,'G3 15','G3 15','https://www.notebookcheck-ru.com/Noutbuk-Dell-G3-15-3579-i5-8300H-GTX-1050-FHD-Obzor-ot-Notebookcheck.321046.0.html','Процессор: Intel Core i5-8300H 4 x 2.3 - 4 GHz, Coffee Lake-H\r\nГрафический адаптер: NVIDIA GeForce GTX 1050 Mobile - 4096 Мбайт, \r\nВидеопр-р: 1354 МГц, \r\nПамять: 7008 МГц, GDDR5, 389.01\r\nОЗУ: 8192 Мбайт, 1333.3 MHz, 19-19-19-43, Single-Channel\r\nДисплей: 15.60 дюйм. 16:9, 1920 x 1080 пикс. 141 точек/дюйм, LG Philips LP156WF6, IPS, LGD053F, Dell P/N: 4XK13,\r\nГлянцевое покрытие: Нет\r\nМатеринская плата: Intel HM370\r\nХранение данных: Seagate Mobile HDD 1TB ST1000LX015-1U7172, 1027 Гбайт, 5400 об/мин\r\nВес: 2.53 Кг,\r\nАдаптер питания: 604 г',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(269,8,500,'Back-UPS 850VA BE850G2-GR','BE850G2-GR','https://www.dns-shop.ru/product/8ac3bc19b1053332/ibp-apc-back-ups-850va-be850g2-rs/characteristics/','Полная выходная мощность: 850 ВА\r\nЭффективная выходная мощность: 520 Вт\r\nМин. входное напряжение: 180 В\r\nМакс. входное напряжение: 266 В\r\nМин. входная частота: 47 Гц\r\nМакс. входная частота: 63 Гц\r\nСтабильность выходного напряжения: ± 8 %\r\nМин. выходная частота: 47 Гц\r\nМакс. выходная частота: 63 Гц\r\nТип формы напряжения: модифицированная синусоида\r\nВремя работы: 2.28 мин (520Вт)\r\nВремя переключения на батарею: 10 мс\r\nМакс. поглощаемая энергия импульса: 310 Дж\r\nВиды защиты: защита от импульсных помех\r\nКоличество и тип выходных разъемов питания: 8 х CEE 7 (евророзетка)\r\nКоличество выходных разъемов питания (UPS): 6\r\nИнтерфейсы: USB\r\nРасположение разъемов на корпусе: горизонтальное\r\nТип батареи: свинцово-кислотная\r\nВремя зарядки: 16 ч\r\nВозможность замены батарей: есть\r\nГорячая замена батарей: есть\r\nПодключение внешних батарей: нет\r\nХолодный старт: есть\r\nОтображение информации: нет\r\nСветодиодные индикаторы: питание от батареи, питание от сети, перегрузка, замена батареи\r\nЗвуковые сигналы: низкий заряд батареи, работа от батареи, перегрузка\r\nУровень шума: 45 дБ',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(270,1,509,'Veriton N2110G','N2110G','https://www.nix.ru/autocatalog/computers_acer/Acer-Veriton-N2110G-Thin-Client-DTVFTER012-G-T56N-4-16-WES7_184780.html','Тип оборудования: тонкий клиент\r\nЧипсет: AMD A55E\r\nМодель процессора: AMD G-Series G-T56N\r\nТактовая частота CPU: 1.6 ГГц\r\nL2 Кэш: 1 Мб\r\nОбъем ОЗУ: 4 Гб\r\nТип памяти: DDR3\r\nЧастота шины: 1333 МГц\r\nОбъем диска: 16 Гб (SSD)\r\nВидеокарта: AMD Radeon HD\r\nОС: Windows Embedded Standard 7\r\nUSB 2.0: 4 шт.\r\nUSB 3.0: 2 шт.\r\nIEEE1394 (FireWire): нет\r\nRJ45 (LAN): есть\r\nS/PDIF: нет\r\nHDMI: нет\r\nTV-out (S-Video): нет\r\nMonitor port (VGA): через адаптер\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:30',NULL,NULL),(271,1,35,'t620 Thin Client','t620','https://www.citilink.ru/product/tonkii-klient-hp-t620-amd-gx-217ga-ddr3l-4gb-16gb-ssd-amd-radeon-hd-82-888680/','Процессор: AMD GX-217GA, 1.65 ГГц;\r\nОперативная память: 4 ГБ, DDR3L, SO-DIMM, 1600 МГц;\r\nДиски: SSD 16ГБ;\r\nГрафика: AMD Radeon HD 8280E;\r\nОптические приводы: отсутствует;\r\nСвязь: Gigabit Ethernet,\r\nОперационная система: HP ThinPro;',0,'',260,NULL,0,0,NULL,0,0,'2023-09-08 03:20:24',NULL,NULL),(272,3,35,'ProLiant DL360 Gen10','DL360 G10','hp overview https://support.hpe.com/hpesc/public/docDisplay?docId=a00018801en_us&page=GUID-AC31EE15-00F3-4A64-BAC1-420564AFCCDC.html','Intel Xeon Scalable Processor up to 28 cores\r\nRAM up to 3.0TB, HPE persistent memory\r\niLO 5',1,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(273,12,35,'StoreEver LTO-5 Ultrium 3000','Ultrium 3000','https://www.hp-pro.net/netcat_files/174/1062/Features_HPE_StoreEver_LTO_Ultrium_Tape_Drives.PDF\r\nhttps://srv-trade.ru/catalog/lentochnye_ustroystva/lentochnye_ustroystva_ibm/lentochnye_avtozagruzchiki_i_biblioteki_ibm/lentochnyy_privod_lto_ultrium_5_fibre_channel_drive_lto_ultrium_5_full_height_8_gbps_fibre_channel.html?','ЛЕНТОЧНЫЙ ПРИВОД LTO ULTRIUM 5 FIBRE CHANNEL DRIVE LTO ULTRIUM 5 FULL-HEIGHT: 8 GBPS FIBRE CHANNEL\r\nВозможность передачи данных со скоростью до 1 ТБ /час\r\nКартриджи до 3 ТБ,\r\nПоддержка обратного чтения и записи с носителем LTO-4 и\r\nвозможностью считывания картриджа LTO-3.\r\n',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(274,3,522,'SYS-6025B-3RB','6025B-3RB','','Up to 2x 64-bit Xeon® Quad-Core\r\nUp to 32GB DDR2\r\nUp to 8 x 3.5\" SAS HDD',1,'',262,NULL,0,0,NULL,0,0,'2023-09-08 03:20:25',NULL,NULL),(275,12,494,'FAS2554','','Netapp site https://mysupport.netapp.com/documentation/docweb/index.html?productID=61620&language=en-US\r\nhttp://meliusgroup.ru/files/techinfo/technicheskie-kharakteristiki-netapp-fas2554.pdf','4U SAN система на 1 контроллер,\r\nHDD 2/3/4TB, SSD 200/400GB\r\nМакс 144диска, память 18Gb на контроллер\r\n\r\n',1,'',263,NULL,0,0,NULL,0,0,'2023-09-08 03:20:25',NULL,NULL),(277,9,501,'UniFi AP AC Pro','','','Рабочие частоты 2,4 ГГц и 5 ГГц\r\nУсиления антенн 3 дБи и 6 дБи соответственно\r\nСкорости передачи данных 450 Мбит/с и 1300 Мбит/с\r\nMIMO 3х3\r\nМощность передатчика 22 дБм\r\nРадиус действия 122+ м',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(278,12,35,'EVA P6350 FC','EVA P6350','https://vse-servera.ru/shop/UID_5220.html\r\nhttps://support.hpe.com/connect/s/product?language=ru&tab=manualsAndGuides&kmpmoid=5268263','2U без разъемов для дисков\r\n2 контроллера HSV340 в режиме active-active\r\n2 блока питания\r\nСжатие и дедупликация не поддерживаются\r\n8 FC портов со скоростью 8Gbit\r\nДиски идут в отдельных корзинах (до 10 корзин):\r\nM6612s - 12 LFF дисков\r\nM6625s - 25 SFF дисков\r\nДля управления нужен браузер с Flash!',1,'',265,NULL,0,0,NULL,0,0,'2023-09-08 03:20:26',NULL,NULL),(279,8,500,'Back-UPS Pro BR 650VA','APC UPS Pro BR650MI','В никсе https://www.nix.ru/autocatalog/apc/UPS-650VA-Back-UPS-APC-BR650MI-zashhita-telefonnoj-linii-RJ-45-USB-LCD_438726.html','Управляемый \r\n(интерфейсный кабель не входит в комплект)\r\nМощность 390Вт, емкость батареи 12Вх7Ач\r\n10 минут при нагрузке 50%\r\nДоп. модулей нет\r\n6x розеток С13 (компьютерные)\r\nБатарейная сборка RBC110',0,'',266,'',0,0,'',0,0,'2023-09-08 03:20:26','reviakin.a',NULL),(280,8,500,'Smart-UPS 2200XL','','','Батареи CSB GP12170',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(281,9,518,'WA6330','','h3c.com http://www.h3c.com/ru/Product_Technology/Enterprise_Products/Wireless/Access_Points/WA6330/','802.11ax/ac wave2/ac/n\r\nс тремя радиомодулями, 6 потоков\r\nсо встроенными антеннами\r\nPort1: 2.5GE: 802.3at/802.3af\r\nPort2: GE:PSE 802.3af\r\nВстроенный разъем питания 54V',0,'Port1\r\nPort2',268,NULL,0,0,NULL,0,0,'2023-09-08 03:20:26',NULL,NULL),(282,8,500,'Back-UPS BE850G2-RS','BE850G2-RS','','Управляемость: нет\r\nВыходная мощность: 850 ВА, 520 Вт\r\nВходное напряжение: 230 В (номинальное), 50/60 Гц\r\nВыходное напряжение (на аккумулятор): 230 В +- 8%, 50/60 Гц\r\nВозможность установки доп. бат. модулей: нет\r\nКол-во розеток питания: 8 (для аварийного питания от аккумулятора с защитой от перенапряжения - 6; только с защитой от перенапряжения - 2)\r\nТип батарей: свинцово-кислотный\r\nРасположение серийного номера: очевидное',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(283,3,55,'ESC4000/FDR G2','ESC4000-F G2','ASUS https://www.asus.com/ru/Commercial-Servers-Workstations/ESC4000FDR_G2/specifications/','CPU: 2x Socket 2011 E5-2600 (135W)\r\nMB: Intel® C602-A PCH Mellanox ConnectX-3 FDR\r\nRAM: Up to 512GB RDIMM/ 128GB UDIMM\r\nHDD: 8x 3.5\" bays\r\n\r\n',1,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(284,3,518,'UniServer R4700 G5','','H3C https://www.h3c.com/ru/Product_Technology/Enterprise_Products/Servers/Rack_servers/R4700_G5/','2x Intel® Xeon® 3-го поколения\r\nЧипсет Intel® C621A\r\n32x DIMM DDR4 (up to 12TB)\r\nУдаленное управление: HDM\r\n',1,'',269,NULL,0,0,NULL,0,0,'2023-09-08 03:20:26','reviakin.a',NULL),(285,20,534,'H5NVR-P-8','H5NVR-P-8','','H5NVR-P-8',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(286,10,534,'hisseu 5MP','hisseu 5MP','','5MP; power: poe 48v',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(287,8,507,'VP1000EILCD','','','выходная мощность 1000 ВА / 550 Вт	\r\nразъемов с питанием от батареи 4\r\nинтерфейсы USB, Ethernet\r\nвходное напряжение 1-фазное\r\nВремя при полной нагрузке 1 м\r\nВремя при половинной нагрузке 9 м\r\nОсобенности возможность замены батарей, \r\nзвуковая сигнализация, время зарядки 8 ч',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-05 14:10:31',NULL,NULL),(288,7,518,'LS-5130S-28S-HPWR-EI-GL','5130S/28SEIGL','',' L2 Ethernet Switch with 24*10/100/1000BASE-T PoE+ Ports(AC 370W,DC 740W), 4*100/1000BASE-X SFP Combo Ports, and 4*1G/10G BASE-X SFP Plus Ports,(AC/DC)\r\nIEEE 802.1d (Spanning Tree), IEEE 802.1p (Priority tags), IEEE 802.1q (VLAN), IEEE 802.1s (Multiple Spanning Tree), IEEE 802.3ab, IEEE 802.3ae, IEEE 802.3af, IEEE 802.3at, IEEE 802.3u, IEEE 802.3x, IEEE 802.3z, IPv6',0,'',NULL,'',0,0,'',0,0,'2023-09-05 14:10:31',NULL,NULL),(291,1,162,'Unknown','','','Автоматически созданная модель оборудования назначена всем АРМ, где модель не была указана при обновлении БД. Нужно проставить правильные модели оборудования и удалить эту.',0,'',NULL,NULL,0,0,NULL,0,0,'2023-09-07 19:12:41',NULL,NULL),(292,13,35,'ProBook 430 G3','','','Оперативная память: 4 ГБ\r\nЛинейка процессора: Intel Core i3\r\nВидеокарта: Intel HD Graphics 520\r\nОбъем видеопамяти: SMA\r\nВерсия ОС: Windows 7 Pro \r\nЦвет: черный\r\nТип: ноутбук\r\nРазрешение экрана: 1366x768\r\nСенсорный экран: Нет\r\nКоличество ядер процессора: 2\r\nТип видеокарты: встроенная\r\nОбщий объем накопителей HDD: 500 ГБ\r\nПроизводитель: HP',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:41',NULL,NULL),(293,13,35,'ProBook 470 G7 (9HP78EA)','','','Ноутбук HP 470 G7 Core i5-10210U 1.6GHz,17.3\" FHD\r\n(1920x1080) AG,AMD Radeon 530 2Gb DDR5,8Gb\r\nDDR4(1),256Gb SSD,1Tb 5400,No ODD,41Wh\r\nLL,2.4kg,1y,Silver (9HP78EA)\r\n',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:41',NULL,NULL),(294,13,35,'ProBook 430 G6 (5PP48EA)','','','Ноутбук HP ProBook 430 G6, 13.3\", Intel Core i5\r\n8265U 1.6ГГц, 8Гб, 1000Гб, 256Гб SSD, Intel UHD\r\nGraphics 620, Windows 10 Professional, 5PP48EA,\r\nсеребристый',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:41',NULL,NULL),(295,13,35,'ProBook 430 G3 (W4N77EA)','','','Легкий ноутбук, Тонкий ноутбук, Ноутбук для работы ✔Черный ✔13.3\" (33.8 см) ✔TN ✔1366 x 768 ✔Core i7 6500U (2.5 - 3.1 ГГц, 2 ядра, 15 Вт) ✔8 Гб ✔500 Гб ✔без ODD ✔Wi-Fi 5 (433 Мбит/сек) ✔Сканер отпечатков пальцев: Есть ✔Веб-камера: Есть ✔Клавиатура: С влагозащитой, Островного типа ✔Win7Pro ✔40 Вт•ч ✔Колонки: Есть, DTS Studio Sound, 2 динамика ✔1.6 кг',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:41',NULL,NULL),(296,13,35,'ProBook 430 G7 (8VT46EA)','','',' Intel Core i5 10210U, DDR4 16 ГБ, SSD 512 ГБ, Intel UHD Graphics, 13.3\", UWVA, Wi-Fi, BT, Cam, Windows 10 Профессиональная, Серебристый',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:41',NULL,NULL),(297,13,35,'ProBook 440 G5 (3BZ53ES)','','','HP ProBook 440 G5 3BZ53ES, 14\", Intel Core i7 8550U 1.8ГГц, 8Гб, 1000Гб, 256Гб SSD, nVidia GeForce 930MX - 2048 Мб, Windows 10 Professional',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:41',NULL,NULL),(298,13,35,'ProBook 430 G7','','','HP ProBook 430 G7 13.3\"(1920x1080)/Intel Core i7 10510u(1.8Ghz)/16384Mb/512SSDGb/noDVD/Int:Intel UHD Graphics/48WHr/war 1y/1.49kg/Silver/W10Pr',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(299,7,518,'LS-5130S-52S-EI-GL','','','Коммутатор Управляемый , Layer 2, 48-1GbE, 4-SFP+, ROM-256MB, RAM-512MB, SNMP, telnet, CLI',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(300,7,518,'LS-6520X-30HF-HI','','',' 24 × 1/10G SFP+ ports, 6 × QSFP28 ports, 3 × fan tray slots, and 2 × power module slots',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(301,7,518,'LS-5130S-28P-EI-GL','','','L2 Ethernet Switch with 24*10/100/1000BASE-T Ports and 4*1000BASE-X Ports,(AC)',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(302,7,518,'LS-6520X-30QC-EI-GL','','','L3 Ethernet Switch(24SFP Plus+2QSFP Plus+2Slot),Without Power SuppliesH3C S6520X-30QC-EI L3 Ethernet Switch(24SFP Plus+2QSFP Plus+2Slot),Without Power SuppliesH3C S6520X-30QC-EI L3 Ethernet Switch(24SFP Plus+2QSFP Plus+2Slot),Without Power Supplies',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(303,7,518,'LS-5560X-54C-EI-GL','','',' L3 Ethernet Switch with 48*10/100/1000BASE-T Ports,4*10G/1G BASE-X SFP+ Ports and 1*Slot,No Power',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(304,7,518,'LS-5130S-52P-EI-GL','','','L2 Ethernet Switch with 48*10/100/1000BASE-T Ports and 4*1000BASE-X Ports,(AC)',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(305,20,535,'Network Video Recorder N2016K','NVR N2016K','','Видеорегистратор',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(306,10,535,'NVR','','','IP- камера',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(307,10,186,'P5635-E MKII','','','ИК-фильтр, поддержка WDR, компенсация задней засветки, слот для карт памяти',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(308,10,186,'M3037-PVE','','','Панорамный обзор в угловом диапазоне 360°/270°/180° с разрешением до 5 Мп.\r\nВстроенный микрофон и громкоговоритель в погодозащитном исполнении.\r\nДвухсторонняя передача звука с подавлением эха.\r\nПротокол SIP для интеграции с системами IP-телефонии.\r\nЦифровое PTZ-управление и многопоточный просмотр неискаженных изображений.\r\nРасширенные возможности для анализа изображений',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:42',NULL,NULL),(309,21,536,'ND-05C-47U80/80','','','Шкаф 19\" 47U\r\n800x800x2277мм\r\nПерфорированные двери\r\nЦвет серый (RAL 7035)',0,'',270,'{\"cols\":[{\"type\":\"void\",\"size\":\"110\"},{\"type\":\"units\",\"size\":580,\"count\":\"1\"},{\"type\":\"void\",\"size\":\"110\"}],\"rows\":[{\"type\":\"title\",\"size\":\"130\"},{\"type\":\"units\",\"size\":2097,\"count\":\"47\"},{\"type\":\"void\",\"size\":\"50\"}],\"hEnumeration\":\"1\",\"vEnumeration\":\"-1\",\"evenEnumeration\":\"1\",\"priorEnumeration\":\"h\",\"labelPre\":1,\"labelPost\":1,\"labelMode\":\"h\",\"labelWidth\":\"50\"}',1,1,'',0,0,'2023-09-13 15:59:27','admin',NULL),(310,8,502,'Innova RT II 6000','','Оф. сайт (ИБП) https://ippon.ru/catalog/item/innova-rt-ll-6000-10000\r\nОф. сайт (Бат. модуль) https://ippon.ru/catalog/item/innova-rt-II\r\nОф. сайт (SNMP модуль) https://ippon.ru/catalog/item/nmc-snmp-ii','Управляемый\r\nМощность: 6 кВТ, емкость: 12В/7Ач х 16 шт\r\nВремя работы:\r\n при 30% нагрузке: 22 мин\r\n при 50% нагрузке: 11 мин\r\nРозетки: IEC C13 - 6шт; IEC C19 - 2шт; Клеммный блок\r\nДоп. бат блоки: до 6 шт (id 1075711)\r\n\r\n',0,'',272,'',0,0,'',0,0,'2023-09-08 03:20:28',NULL,NULL),(311,1,537,'mini S2-B560','','','i3-10100Mhz\r\nDDR4 8Gb\r\n240 SSD Sata\r\nWin10Pro\r\nKB+Mouse',0,'',276,'',0,0,'',0,0,'2023-09-08 03:20:29',NULL,NULL),(312,8,507,'UT1200EG','','','Выходная мощность (полная) - 2200 ВА\r\nВыходная мощность (активная) - 1320 Вт\r\nВремя работы при полной нагрузке - 70 мин\r\nВремя работы при половинной нагрузке - 95 мин\r\nФорма выходного сигнала - ступенчатая аппроксимация синусоиды\r\nВремя переключения на батарею 4 мс\r\nВыходное напряжение - 1-фазное\r\nТип выходных разъемов питания - IEC 320 C13 (компьютерный)\r\nТип предохранителя - автоматический',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(313,13,521,'Vostro 14-5480','','','Экран: 14 \"; \r\nПроцессор: Intel Core i3 4050U 1.7 ГГц \r\nГрафический процессор: Intel UHD Graphics 4400;\r\nОперативная память: 4 ГБ, DDR4,\r\nДиск: HDD 250 ГБ;',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(314,22,538,' Модуль мониторинга VT8101 ','Модуль VT8101 ','http://n-sistem.net/modul-monitoringa-vt8101/','Устройство используется для контроля\r\nтемпературы, влажности,  утечки воды,\r\nпоявления дыма, в серверных комнатах и на\r\nдругих объектах.',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(315,22,538,'VT460 Датчик влажности, температуры и дыма','Датчик VT460 ','https://tnvst.ru/catalog/sistema-kontrolya-vutlan/datchik-vlazhnosti-temperatury-i-dyma-vt460-/','Датчик влажности, температуры и дыма Vutlan VT460 контролирует появление дыма, измеряет температуру и относительную влажность при установке в помещении, в шкафу и других местах.',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(316,22,538,'VT490 Датчик влажности и температуры','Датчик VT490','https://tnvst.ru/catalog/sistema-kontrolya-vutlan/datchik-vlazhnosti-i-temperatury-vt490/','Датчик Vutlan VT490 измеряет температуру и относительную влажность.',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(317,22,538,'VT590 Датчик протечки воды ','Датчик VT590','https://tnvst.ru/catalog/sistema-kontrolya-vutlan/datchik-protechki-vody-vt590/','При касании пленки воды никелированных стержней, датчик показывает появление влаги.\r\nВозможно срабатывание датчика при большой влажности на поверхности датчика!\r\nМаксимально допустимое расстояние первого датчика от системы мониторинга ~ 100 метров.',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(318,22,538,'VT920 GSM модем','GSM модем VT920','https://ssa101.by/catalog/setevoe-oborudovanie/vutlan/moduli-rasshireniya-1/gsm-modem-vt920-detail','Может быть встроен во все мастер модули системы. Необходим при отсутствии сети для отсылки уведомлений и приема команд по SMS.\r\n\r\nВнимание! Не отсылаются СМС с SIM карт с совместимостью с micro-SIM! Их можно узнать по каемке по которой можно выломать микро-сим из обычной карты.\r\n\r\nSMS уведомления\r\n\r\nНезависимый резервный канал получения уведомлений о работе системы\r\nНезависимое удаленное управление\r\n\r\nПозволяет удаленно отсылать команды на систему по СМС',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(319,23,539,'DistKontrolUSB-32','','','Назначение пользователям прав на использование определенного устройсnва. Так же возможно добавления прав на управление питанием USB порта.\r\n USB Client работает под управлением любой версии Linux и Windows, OSX. Клиент позволяет подключать и отключать удаленные устройства USB. Клиент может запускаться в качестве сервиса',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(320,8,496,'UPS2000-G-15kVA, Load 5kW, BackupTime 30min','UPS2000-G-15kVA','','Управляемый',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(321,8,500,'Symmetra LX 16000','Symmetra LX 16000','','Управляемый',0,'',NULL,'{\"cols\":[{\"type\":\"void\",\"size\":\"25\"},{\"type\":\"units\",\"size\":450,\"count\":\"2\"},{\"type\":\"void\",\"size\":\"25\"}],\"rows\":[{\"type\":\"title\",\"size\":\"100\"},{\"type\":\"units\",\"size\":865,\"count\":\"4\"},{\"type\":\"void\",\"size\":\"35\"}],\"hEnumeration\":\"1\",\"vEnumeration\":\"1\",\"evenEnumeration\":\"1\",\"priorEnumeration\":\"h\",\"labelPre\":1,\"labelPost\":1,\"labelMode\":\"h\",\"labelWidth\":\"50\"}',1,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(322,8,508,'9SX 1500i Rack2U 1350Вт 1500ВА','','https://eaton-systems.ru/product/9sx1500ir/','Eaton 9SX 1500i Rack2U 1350Вт 1500ВА',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(323,13,35,'ProBook 430 G7 (8VU50EA)','','','Ноутбук HP ProBook 430 G7 Core i7-10510U 1.8GHz,\r\n13.3 FHD (1920x1080) AG 16GB DDR4 (1),512GB\r\nSSD,45Wh LL,FPR,1.5kg,1y,Silver,Win10Pro',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(324,13,35,'ProBook 450 G5 (2XZ73ES)','','','Ноутбук HP ProBook 450 G5 2XZ73ES, 15.6\", Intel Core i7 8550U 1.8ГГц, 16Гб, 1000Гб, 512Гб SSD, nVidia GeForce 930MX - 2048 Мб, Windows 10 Professional',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(325,13,55,'ROG G712LU-EV001T','','','Ноутбук ASUS ROG G712LU-EV001T, 17.3\", IPS, Intel Core i7 10750H 2.6ГГц, 8ГБ, 512ГБ SSD, nVidia GeForce GTX 1660 Ti - 6144 Мб, Windows 10, черный',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(326,16,520,'271E1SD','','','Монитор 27\" PHILIPS 271E1SD/00 Black (IPS, 1920x1080, 75Hz, 1 ms, 178°/178°, 300 cd/m, +DVI, +HDMI, AMD FreeSync™)',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(327,1,21,'NUC7JY kit','NUC7JY','Описание NUC7JY kit https://ark.intel.com/content/www/ru/ru/ark/products/126135/intel-nuc-kit-nuc7cjyh.html\r\nОписание Celeron J4005 https://ark.intel.com/content/www/ru/ru/ark/products/128992/intel-celeron-j4005-processor-4m-cache-up-to-2-70-ghz.html','Платформа (без RAM, HDD)\r\nCPU: Celeron J4005 (2x cores @2,0 GHz up to 2.7GHz)\r\nRAM: Up to 8GB DDR4-2400 1.2V SO-DIMM\r\nHDD: 2.5\"\r\nVideo: Intel® UHD 600. 2x HDMI 2,0a ports\r\n4x USB3.0',1,'eth',284,'',0,0,'',0,0,'2023-09-09 20:16:03','admin',NULL),(328,6,540,'L805','Epson L805','','Струйный принтер, печатает на СD дисках',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(329,13,193,'E31-70','','','Ноутбук Lenovo E31-70 13,3 HD(1366x768), i3-5005U (2,0GHz), 4Gb, 500Gb@5400, HD Graphics5500, WiFi, BT, FPR, 2cell, camera,Win 7 PRO-in-Win 8.1 PRO, Black, 1,6kg 1y. Warr',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:44',NULL,NULL),(330,24,529,'TC520K','','Zebra https://www.zebra.com/ru/ru/products/spec-sheets/mobile-computers/handheld/tc52-tc57.html','Смартфон-сканер для шкафов ESSEGI\r\nAndroid 8 -> 11\r\n5\"@1280x720 Corning Gorilla Glass\r\nRAM: 3GB\r\nCPU: Qualcomm Snapdragon 660, 2.26 GHz, 8 ядер\r\nWi-Fi, Bluetooth, NFC\r\nАккумулятор 4150 мАч',0,'',277,'',0,0,'',0,0,'2023-09-08 03:20:29',NULL,NULL),(331,16,169,'SDM-S205F/K','SDM-S205F/K','','Размеры, вес:442x411x278 мм, 9.60 кг\r\nВходы:DVI-D, VGA (D-Sub)\r\nПотребляемая мощность: при работе: 55 Вт, в режиме ожидания: 1 Вт\r\nТип: ЖК-монитор\r\nДиагональ:20.1\"\r\nРазрешение:1600x1200 (4:3)\r\nКонтрастность:700:1\r\nЯркость:300 кд/м2\r\nШаг точки по горизонтали:0.255 мм\r\nШаг точки по вертикали:0.255 мм\r\nВремя отклика:16 мс\r\nОбласть обзора:по горизонтали: 178°; по вертикали: 178°\r\nМаксимальное количество цветов :16.7 млн.\r\nЧастота обновления: строк: 28-92 кГц; кадров: 48-85 Гц\r\n',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(332,16,116,'943N','943N','','Размеры, вес:406x414x200 мм, 3.80 кг\r\nВходы:VGA (D-Sub)\r\nПотребляемая мощность:при работе: 35 Вт, в спящем режиме: 1 Вт\r\nБлок питания:встроенный\r\nОбщие характеристики\r\nТип:ЖК-монитор\r\nДиагональ:19\"\r\nРазрешение:1280x1024 (5:4)\r\nТип ЖК-матрицы:TFT TN\r\nФункциональность:меню на русском языке, калибровка цвета\r\nКонтрастность:1000:1\r\nЯркость:300 кд/м2\r\nДинамическая контрастность:50000:1\r\nШаг точки по горизонтали:0.294 мм\r\nШаг точки по вертикали:0.294 мм\r\nВремя отклика:5 мс\r\nОбласть обзора:по горизонтали: 170°; по вертикали: 160°\r\nМаксимальное количество цветов :16.7 млн.\r\nПокрытие экрана:антибликовое\r\nЧастота обновления:строк: 30-81 кГц; кадров: 56-75 Гц',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(333,16,116,'940N','940N','','Диагональ	19 \"\r\nРазрешение (макс.)	1280 x 1024\r\nФормат экрана	4:3\r\nЯркость	300 кд/м²\r\nКонтрастность динамическая	700:1\r\nВремя отклика	8 мс\r\nУгол обзора горизонтальный	160°\r\nУгол обзора вертикальный	160°',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(334,16,541,'Futura 192','192','','Диагональ	19 \"\r\nРазрешение (макс.)	1280 x 1024\r\nЯркость	300 кд/м²\r\nКонтрастность динамическая	700:1\r\nВремя отклика	25 мс\r\nУгол обзора горизонтальный	170°\r\nУгол обзора вертикальный	170°',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(335,16,541,'RX190C','RX190C','','Размер матрицы:	18,1\"\r\nОбласть экрана:	359 х 287.2 мм\r\nРазрешение:	1280х1024@75Hz\r\nПоддерживает	16.7М цветов (24-bit)\r\nРазмер зерна	0.2805 мм\r\nЯркость подсветки:	200 кд/м2\r\nКонтрастность:	350:1\r\nУгол обзора:	160° по горизонтали и вертикали\r\nВремя отклика:	20 мс\r\nЧастота горизонтальной развертки	30 - 82 kHz\r\nЧастота вертикальной развёртки	50 - 75 Hz\r\nВходы:	DVI,15 pin D-Sub(обычный), S-VHS\r\nОтвечает требованиям:	TCO\'95, MPR1990:10, Energy 2000\r\nПотребляемая мощность	65Вт\r\nРазмер	450 x 455 x 245 (мм)\r\nМасса	10.4 Кг',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(336,16,509,'H236HL','H236HL','','Диагональ экрана 23\"\r\nМаксимальное разрешение 1920x1080\r\nТехнология изготовления матрицы IPS\r\nТип ЖК-матрицы (подробно) E-IPS\r\nСоотношение сторон 16:9\r\n3D Ready нет\r\nПокрытие экрана глянцевое\r\nРазмер видимой области экрана 509x286 мм\r\nЯркость 250 кд/м2\r\nДинамическая контрастность 100М:1\r\nВремя отклика пикселя, мс 5 мс\r\nУгол обзора по вертикали 178°\r\nУгол обзора по горизонтали 178°\r\nРазмер пикселя 265 мкм\r\nМаксимальная частота обновления экрана 75 Гц\r\nВидеоразъемы HDMI, VGA (D-Sub)',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(337,16,55,'VX238H','VX238H','','Диагональ экрана 23\"\r\nМаксимальное разрешение 1920x1080\r\nТип подсветки матрицы LED\r\nТехнология изготовления матрицы TN\r\nТип ЖК-матрицы (подробно) TN+film\r\nСоотношение сторон 16:9\r\nПокрытие экрана матовое\r\nРазмер видимой области экрана 509x286 мм\r\nЯркость 250 кд/м2\r\nКонтрастность 1000:1\r\nДинамическая контрастность 80M:1\r\nВремя отклика пикселя, мс 1 мс\r\nУгол обзора по вертикали 160°\r\nУгол обзора по горизонтали 170°\r\nРазмер пикселя 265 мкм\r\nПлотность пикселей (ppi) 96 ppi\r\nЧастота при максимальном разрешении 60 Гц\r\nМаксимальная частота обновления экрана 76 Гц\r\nВидеоразъемы HDMI (2 шт), VGA (D-Sub)',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(338,16,525,'24.5\" XL2546','24.5','','Диагональ экрана 24.5\"\r\nМаксимальное разрешение 1920x1080 (FullHD)\r\nТип подсветки матрицы LED\r\nТехнология изготовления матрицы TN\r\nСоотношение сторон 16:9\r\nПокрытие экрана матовое\r\nВидео разъемы\r\nDisplayPort 1.2, DVI-D, HDMI 2.0 x2\r\nUSB-концентратор есть\r\nКоличество USB 3\r\nВыход на наушники есть\r\nРазъем HDMI есть\r\nРазъем DisplayPort есть\r\nРазъем DVI есть\r\nРазъем VGA нет',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(339,16,541,'Maxima','Maxima','','Технология	TFT\r\nДиагональ	19 \"\r\nРазрешение (макс.)	1280 x 1024\r\nЯркость	250 кд/м²\r\nКонтрастность динамическая	500:1\r\nВремя отклика	10 мс\r\nУгол обзора горизонтальный	170°\r\nУгол обзора вертикальный	170°',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(340,16,203,'MultiSync EA244WMi','EA244WMi','','Тип монитора ЖК\r\nДиагональ 24 \"\r\nМакс. разрешение 1920x1200\r\nСоотношение сторон 16:10\r\nТип LED-подсветки WLED\r\nТип матрицы экрана IPS\r\nМакс. частота обновления кадров 60 Гц\r\nИнтерфейсы видео вход DVI-D, вход DisplayPort, вход HDMI, вход VGA\r\nИнтерфейсы USB Type A x 4, USB Type B, USB-концентратор, вход аудио стерео, выход на наушники\r\nUSB-концентратор есть\r\nВерсия USB 2.0\r\nКолонки: есть (встроенные)',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(341,16,116,'SA300/SA350','','','https://www.samsung.com/us/business/support/owners/product/sa350-series-s23a350h/',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(342,16,116,'S22B350','','','https://www.samsung.com/ru/support/model/LS22B350TS/CI/',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(343,16,116,'2263uw','2263uw','','Особенности:вход для микрофона\r\nСтандарты:энергосбережения: VESA DPMS\r\nРазмеры, вес:514x429x219 мм, 5.30 кг\r\nВстроенная веб-камера:есть (3 Мп)\r\nПодключение\r\nВыходы:на наушники\r\nВходы:DVI-D (HDCP), HDMI, VGA (D-Sub)\r\nИнтерфейсы:USB Type A x2\r\nUSB-концентратор:есть, количество портов: 2\r\nВерсия USB:USB 2.0\r\nТип:ЖК-монитор, широкоформатный\r\nДиагональ:22\"\r\nРазрешение:1680x1050 (16:10)\r\nТип ЖК-матрицы:TFT TN\r\nМультимедиа:стереоколонки (2x1.50 Вт), микрофон',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(344,13,55,'ZenBook 14','ZenBook 14','https://www.dns-shop.ru/product/a5abdeefa0f52ff1/14-ultrabuk-asus-zenbook-14-ux425ja-bm070-seryj/characteristics/','Тип экрана IPS\r\nДиагональ экрана 14\"\r\nРазрешение экрана Full HD (1920x1080)\r\nПокрытие экрана матовое\r\nСенсорный экран нет\r\nМаксимальная частота обновления экрана 60 Гц\r\nЯркость 300 Кд/м²\r\nПлотность пикселей 157 ppi\r\nТехнология динамического обновления экрана нет\r\nЦветовой охват sRGB 100%\r\nМодель процессора Intel Core i5-1035G1\r\nОбщее количество ядер 4\r\nКоличество производительных ядер 4\r\nМаксимальное число потоков 8\r\nЧастота процессора 1 ГГц\r\nАвтоматическое увеличение частоты 3.6 ГГц\r\nТип оперативной памяти LPDDR4x\r\nОбъем оперативной памяти 8 ГБ\r\nКоличество слотов под модули памяти интегрированная\r\nЧастота оперативной памяти 3733 МГц\r\nМаксимальный объем памяти 8 ГБ\r\nВид графического ускорителя встроенный\r\nМодель встроенной видеокарты Intel UHD Graphics\r\nОбщий объем твердотельных накопителей (SSD) 256 Гб\r\nТип SSD диска M.2 PCIe\r\nВеб-камера 1 Мп (720p)\r\nБеспроводной интерфейс Bluetooth 5.0, WI-FI 6 (802.11ax)\r\nПорт Ethernet нет\r\nВидеоразъемы HDMI, USB Type-C x2\r\nВерсия видеоразъема HDMI 2.0b\r\nАудиоразъемы нет\r\nРазъемы USB Type-A USB 3.2 Gen1\r\nРазъемы USB Type-C USB 3.2 Gen1 x2\r\nThunderbolt Thunderbolt 3',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(345,13,116,'Galaxy Tab A 9.7 SM-T555','SM-T555','https://market.yandex.ru/product--planshet-samsung-galaxy-tab-a-9-7-sm-t555/12558960/spec?track=char&cpa=1&nid=26908970','Версия ОС Android 5.0\r\nПроцессор Qualcomm Snapdragon 410 APQ8016 1200 МГц\r\nКоличество ядер 4\r\nВидеопроцессор Adreno 306\r\nПоддержка карт памяти microSDXC\r\nЭкран 9.7\" (1024x768), PLS\r\nЧисло пикселей на дюйм (PPI) 132\r\nСтандарт Wi-Fi 802.11n\r\nВерсия Bluetooth 4.1',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(346,13,35,'Pavilion dv6--6c54er','Pavilion dv6','https://www.svyaznoy.ru/catalog/notebook/1738/1475905/specs','Диагональ дисплея (дюйм) 15.6\r\nРазрешение дисплея (пикс.) 1366x768\r\nПроцессор Intel® Core™ i7 2670QM \r\nПроцессор серияIntel Core i7\r\nЧастота процессора (МГц) 2200\r\nКол-во ядер процессора 4\r\nКэш L2 (Кб) 1024\r\nКэш L3 (Кб) 6144\r\nТип видеоадаптера дискретный \r\nГрафический процессор AMD Radeon HD 7470\r\nВидеопамять (Мб) 1024\r\nТип оперативной памяти DDR3\r\nОперативная память (Мб)6144\r\nЕмкость HDD (Гб) 640\r\nВеб-камера есть\r\nПривод CD/DVD: CD/DVD-RW\r\nОбщее количество портов USB 4\r\nHDMI-порт да\r\nBluetooth да\r\nWi-Fi (802.11) да\r\nВстроенный кардридер да',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(347,13,35,'Probook 6550b','6550b','https://zoom.cnews.ru/goods_card/item/195903/hp-probook-6550b','Тип видеокарты встроенная\r\nРазрешение экрана: 1366x768\r\nПроцессор: Intel Core i5 \r\nТактовая частота процессора: 2.4 ГГц \r\nОбъем ОЗУ: 2 ГБ \r\nТип памяти: DDR3L\r\nОбъем памяти жесткого диска: HDD 250 ГБ\r\nОптический привод DVD-RW\r\nWi-Fi: есть\r\nBluetooth: есть',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(348,16,116,'2243NWX','','','Экран\r\n22\"/1680x1050 Пикс\r\nЯркость\r\n300 кд/кв.м\r\nЧастота обновления\r\n60 Гц\r\nДинамическая контрастность\r\n8000:1\r\nМаксимальный угол обзора по горизонтали\r\n170 *\r\nМаксимальный угол обзора по вертикали\r\n160 *\r\nВремя отклика пикселя\r\n5 мсек\r\nИнтерфейс связи с ПК\r\nD-Sub',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(349,13,193,'ThinkPad T510i','T510i','','Диагональ дисплея (дюйм) 15.6\r\nРазрешение дисплея (пикс.) 1600x900\r\nПроцессор Intel® Core™ i3 380M \r\nЧастота процессора (МГц) 2533\r\nКол-во ядер процессора 2\r\nКэш L2 (Кб) 512\r\nКэш L3 (Кб) 3072\r\nВидеопамять (Мб) 512\r\nТип оперативной памяти DDR3\r\nОперативная память (Мб) 3072\r\nЕмкость HDD (Гб) 320\r\nВеб-камера (Мп) 2.0\r\nПривод: CD/DVD-RW\r\nОбщее количество портов USB 4\r\nBluetooth да\r\nWi-Fi (802.11) да\r\nВстроенный кардридер да',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(350,12,510,'TS-419U+','TS-419U+','https://www.qnap.ru/catalog/ts-419u-plus/','Процессор	\r\nMarvell 6282 1,6 ГГц\r\nПамять	512 МБ (DDR3)\r\nДисковая подсистема\r\nЧисло слотов	4\r\nСлоты для жёстких дисков тип 1	4\r\nГорячая замена дисков	Да\r\nЗапираемые слоты	Да\r\nПоддержка 3,5\" жестких дисков	Да\r\nПоддержка 2,5\" жестких дисков	Да\r\nИнтерфейс	SATA 3 Гбит/с\r\nПоддержка жестких дисков	до 4 ТБ\r\nМаксимальная емкость хранилища	16 ТБ\r\nИнтерфейсы\r\nПорты LAN 1 Гбит\\с	2\r\nПоддержка Jumbo-кадров	Да\r\nПорты USB 2.0 (спереди)	1\r\nПорты USB 2.0 (сзади)	3\r\nОписание USB интерфейса	Поддерживаются USB-принтеры, USB-накопители, USB-хабы, USB-ИБП и т.д.\r\nПорты ESATA (сзади)	2\r\nИнформация о системе\r\nИндикатор STATUS	Да\r\nИндикаторы жестких дисков	Да\r\nИндикатор LAN	Да\r\nИндикатор USB	Да\r\nКнопка питания	Да\r\nКнопка сброса	Да\r\nКнопка резервного копирования	Да\r\nСистемный динамик	Да\r\nFlash-память	16 МБ\r\nФизические характеристики\r\nФорм-фактор	\r\nCтоечный\r\nГабариты, мм	\r\n44 x 439 x 483\r\nМасса (нетто), кг	\r\n6,7\r\nМасса (брутто), кг	\r\n9,2\r\nУсловия эксплуатации\r\nРабочий диапазон температур	\r\n0~40˚C\r\nОтносительная влажность	\r\n0~95% без конденсации\r\nЭлектропитание\r\nБлок питания	\r\nВстроенный\r\nВходное напряжение	\r\n100 – 240 В\r\nМощность	\r\n250 Вт\r\nЭнергопотребление\r\nЭнергопотребление в спящем режиме	\r\n15 Вт\r\nЭнергопотребление в работе	\r\n29 Вт\r\nКомментарий	\r\nс 4 установленными дисками объемом 0.5 Тбайт\r\nОхлаждение\r\nТип охлаждения	\r\nАктивное\r\nВентиляторы корпуса	\r\n3',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(351,3,522,'X9DB3-TPF','','','Cокет: LGA1356, название чипсета: Intel C606, форм-фактор: нестандартный, производитель процессора: Intel, тип памяти: DDR3 DIMM, поддержка ECC/non-ECC, поддержка буферизованной (RDIMM) памяти, количество слотов памяти: 12, Количество слотов PCI-E x16: 1, беспроводные интерфейсы: без Wi-Fi, максимальная частота памяти: 1600 МГц, Поддержка SLI/CrossFire: нет',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(352,3,21,'S5500HCV','','','Сокет: LGA1366, \r\nназвание чипсета: Intel 5500, \r\nформ-фактор: SSI EEB, \r\nтип памяти: DDR3 DIMM, ECC/non-ECC',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45','reviakin.a',NULL),(353,3,21,'S2600CP4','','','Сокет: LGA2011, \r\nназвание чипсета: Intel C600,\r\nформ-фактор: SSI EEB, \r\nтип памяти: DDR3 DIMM, поддержка буферизованной (RDIMM) памяти, \r\nколичество слотов памяти: 16, ',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45','reviakin.a',NULL),(354,3,21,'S1200V3RPS','','','Сокет: LGA1150, \r\nназвание чипсета: Intel C222, \r\nформ-фактор: microATX, \r\nтип памяти: DDR3 DIMM, поддержка ECC',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45','reviakin.a',NULL),(355,13,509,'Aspire 5750 P5WEO','','https://www.acer.com/ru-ru/support?search=Aspire%205750G&filter=global_download','Диагональ экрана 15.6\"\r\n- Процессор Intel i3-2310\r\n- Оперативная память 4 Gb\r\n- Жёсткий диск 500 Gb\r\n- Видеокарта Nvidia GT 520 1Gb\r\n- Wi-Fi, Bluetooth, HDMI, VGA',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(356,13,530,'S410','','https://www.getac.com/ru/products/laptops/s410/','роцессор	\r\nIntel® Core™ i3-1115G4, 2 ядра\r\nдо 4.1 ГГц в режиме авторазгона\r\nКэш - 6 Мб\r\n\r\nОпционально:\r\nIntel® Core™ i5-1135G7, 4 ядра\r\nдо 4.2 ГГц в режиме авторазгона\r\nКэш - 8 Мб\r\n\r\nIntel® Core™ i5-1145G7 vPro® 4 ядра\r\nдо 4.4 ГГц в режиме авторазгона\r\nКэш - 8 Мб\r\n\r\nIntel® Core™ i7-1165G7, 4 ядра\r\nдо 4.7 ГГц в режиме авторазгона\r\nКэш - 12 Мб\r\n\r\nIntel® Core™ i7-1185G7 vPro® 4 ядра\r\nдо 4.8 ГГц в режиме авторазгона\r\nКэш - 12 Мб\r\n\r\nВстроенная графика	\r\nIntel® Iris® Xe Graphics (i5 / i7)ii\r\n\r\nIntel® UHD Graphics (Core i3)\r\nОпционально: дискретная видеокарта NVIDIA® GeForce® GTX 1650 (4 Гб видеопамяти) iii, iv, v\r\n\r\n \r\n\r\nДисплей	\r\n14\", LCD TFT, 1366×768 точек (HD)\r\nЗащитное покрытие\r\nТехнология LumiBond обеспечивает яркость до 1000 нит для комфортного чтения при ярком освещении\r\nОпционально: 14\" TFT LCD FHD (1920 x 1080) с широким углом обзора\r\nТехнология, яркость до 1000 нит\r\nОпционально: 14\" TFT LCD FHD (1920×1080)\r\nТехнология, яркость до 1000 нит, емкостный мультитач экран и распознавания степени нажатия\r\n\r\nОперативная / встроенная память	\r\n8 Гб DDR4\r\nОпционально: 16/32/64 Гб DDR4\r\n256 Гб SSD (PCIe NVMe)\r\nОпционально:512/1000/2000 Гб SSD (PCIe NVMe)\r\nОпционально:дополнительный SATA SSD (256/512/1000 Гб) в мультимедийный отсек iv\r\n\r\nУправление	\r\nМембранная клавиатура со светодиодной\r\n\r\nСенсорный ввод	\r\nСенсорный экран\r\nЕмкостный мультитач экран\r\nТачпад\r\nСкользящая сенсорная панель с функцией мультитач\r\n\r\nМодули расширения	\r\nОпционально: Cчитыватель смарт-карт x 1\r\n\r\nМультимедийный отсек	\r\nПустой слот с заглушкой (для снижения веса)\r\nОпционально: сканер 1D/2D штрих-кодов iv\r\nОпционально: модуль PCMCIA Type II с поддержкой устройств ввода-вывода ii\r\nОпционально: модуль ExpressCard (на 34 мм / на 54 мм) iv, vi, vii\r\nОпционально: Super Multi DVD-привод iv, viii\r\nОпционально: дополнительная батарея для мультимедийного отсека iv, viii\r\nОпционально: Super Multi Blu-Ray-привод iv, viii\r\nОпционально: дополнительный накопитель iv, viii\r\n\r\nИнтерфейсы	\r\nКомбинированный разъем 3.5mm (для микрофона и наушников) x 1\r\nРазъем питания (DC-in/Jack) x 1\r\nUSB 2.0 x 1\r\nUSB 3.2 Gen.2 Type-A x 2\r\nОпционально: Thunderbolt™ 4 Type-C x 1\r\nEnternet (RJ45) x 1\r\nHDMI 2.0 x 1\r\nКоннектор док-станции x1\r\nОпционально: FHD веб-камера x1\r\nОпционально: слот SIM карты x 1 (Mini-SIM, 2FF) xi\r\nОпционально: фронтальная камера с возможностью распознавания лиц через приложение Windows Hello xxi\r\nОпционально: антенна для приема сигналов GPS, Wi-Fi и WWAN (городских сетей сотовой связи)\r\nНастраиваемые опции ввода/вывода vii:\r\n1. COM-порт - RS232 (D-sub 9-pin) + VGA (D-sub 15-pin) + дополнительный Ethernet (RJ45)\r\n2. COM-порт - RS232 (D-sub 9-pin) + VGA (D-sub 15-pin) + USB 3.2 Gen.1 Type-A (с поддержкой технологии PowerShare)\r\n3. COM-порт - RS232 (D-sub 9-pin) + DisplayPort + дополнительный Ethernet (RJ45)\r\n4. COM-порт - RS232 (D-sub 9-pin) + DisplayPort + USB 3.2 Gen.1 Type-A (с поддержкой технологии PowerShare)\r\n\r\nБеспроводная связь	\r\n10/100/1000 base-T Ethernet\r\nIntel® Wi-Fi 6 AX201, 802.11 «ax»\r\nBluetooth (v5.2) ix\r\nОпционально: модуль GPS x\r\nОпционально: 4G LTE с интегрированным GPS x, xi\r\nДополнительно: 5G Sub-6 со встроенным L1/L5 GPS xxi\r\n\r\nОсобенности	\r\nTPM 2.0\r\nЗамок Кенсингтон\r\nОпционально: поддержка Intel® vPro®\r\nОпционально: дактилоскопический датчик (сканер отпечатка пальцев)\r\nОпционально: сканер RFID-меток в HF-диапазоне vii, xi\r\nОпционально: считыватель смарт-карт\r\nОпционально: камера для проверки подлинности лица Windows Hello (фронтальная) xxi\r\n\r\nПитание	\r\nБлок питания: 90 Вт, 100-240 В, 50/60 Гц\r\nАккумулятор: литий-ионный, 10.8 В, 6900 мА·ч (мин. 6600 мА·ч)\r\nОпционально: блок питания (120 Вт, 100-240 В, 50/60 Гц) iii\r\nОпционально: аккумулятор (11.1 В, 4200 мАч (мин. 3980 мА·ч) в мультимедийный отсек iii\r\nОпционально: дополнительный аккумулятор 10.8 В, 6900 мА·ч (мин. 6600 мА·ч)\r\n\r\nГабариты (ШхГхВ) / вес	\r\n350×293×38,5 мм\r\n2,38 кг xiii\r\n\r\nСтандарты защиты	\r\nСертификация по стандарту MIL-STD-810H\r\nСертификация по стандарту IP53\r\nКлавиатура с защитой от брызг\r\nЗащита от вибраций и падения с высоты 0,9 м xiv\r\nГерметичные интерфейсы и коннекторы\r\nСъемный, противоударный SSD-накопитель\r\n\r\nУсловия эксплуатации	\r\nДопустимая температура xv:\r\nво время работы: от -29°C до +63°C xvi\r\n- при хранении: -51°C до +71°C\r\nДопустимая влажность:\r\n- до 95% без образования конденсата\r\n\r\nПредустановленное ПО	\r\nФирменное программное обеспечение Getac\r\nПриложение Getac Geolocation\r\nПриложение Getac Barcode Manager xvii\r\nОпционально: Absolute Persistence®\r\n\r\nОпциональное ПО	\r\nПриложение Getac Driving Safety\r\nСистема мониторинга систем Getac (GDMS)\r\nПриложение Getac VGPS xvii\r\n\r\nАксессуары	\r\nАккумулятор: 10.8 В / 6900 мА·ч (мин. 6600 мА·ч)\r\nБлок питания: 90 Вт / 100-240 В\r\nСтилус для емкостного экрана с проводом\r\n\r\nОпционально:\r\nСумка для транспортировки\r\nАккумулятор (10.8 В / 6900 мА·ч; мин. 6600 мА·ч)\r\nДополнительный SSD-накопитель iv\r\nАккумулятор для установки в мультимедийный отсек (11.1В, 4200 мА·ч (мин. 3980 мА·ч) iv\r\nУниверсальное зарядное устройство (2 зарядных интерфейса) iii\r\nУниверсальное зарядное устройство (8 зарядных интерфейсов)\r\nБлок питания: 120 Вт / 100-240 В, 50/60 Ггц ii\r\nАвтомобильный адаптер питания: 120 Вт / 11-32 В\r\nСтилус для емкостного экрана с проводом\r\nЗащитная пленка\r\nАвтомобильный держатель для устройства xix\r\nДок-станция для автомобиля xix\r\nОфисная док-станция\r\nАдаптер с коннектором Type-C (100 Вт) xx\r\n\r\nГарантия производителя	\r\nНичто так не говорит о качестве, как производитель, стоящий за своим продуктом. Мы разработали ноутбук изнутри, чтобы он выдержал падения, удары, пролитую жидкость, вибрацию и многое другое. Мы уверены в своем качестве, поэтому на него распространяется лучшая в отрасли трехлетняя гарантия. Это защита душевного спокойствия, зная, что вы застрахованы.\r\n\r\nСрок ограниченного гарантийного обслуживания составляет 3 года.',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(357,13,521,'Latitude E6520','','','Диагональ экрана 15.6 \"\r\nРазрешение экрана 1600х900\r\nОперативная память 4 ГБ, DDR3, 1333 МГц\r\nМакс. объем оперативной памяти 8 ГБ\r\nОбъем HDD 500 ГБ\r\nDVD-RW есть\r\nГрафический процессор Intel HD Graphics 3000\r\nПроцессор Intel Core i5 2520M\r\nКард-ридер есть, поддержкаSD\r\nПоддержка технологии Bluetooth есть, v3.0\r\nRJ-45 10/100/1000 (Gigabit Ethernet) Мбит/с\r\nРазъемы и интерфейсы ноутбука\r\nПорты USB 2.0(Type-A)\r\n3\r\nРазъемD-Sub\r\n1\r\nРазъемHDMI\r\n1\r\nРазъемпорт-репликатора\r\nесть\r\nРазъем IEEE 1394(Firewire)\r\n1\r\nОперационная система ноутбука\r\nОперационная система\r\nWindows 7 Professional\r\nМультимедийные особенности\r\nВеб-камера\r\nвстроенная\r\nРазъемнаушники/микрофон\r\nкомбинированный разъем\r\nАкустическаясистема\r\nстереодинамики\r\nКлавиатура ноутбука\r\nЦвет клавиатурыноутбука\r\nчерный\r\nЦифровой блокклавиатуры\r\nесть\r\nСканер отпечаткапальца\r\nесть (Fingerprint)\r\nДополнительные характеристики\r\nГарантия\r\n12 мес.\r\nБатарея ноутбука\r\nТипбатареи\r\nLi-Ion\r\nКоличество ячеекбатареи\r\n6 cell\r\nЕмкостьбатареи\r\n5300 mAh\r\nЭнергоемкостьбатареи\r\n60 Wh\r\nНапряжениебатареи\r\n11.1 V\r\nКорпус ноутбука\r\nВнешняяповерхность\r\nматовая\r\nВнутренняяповерхность\r\nматовая\r\nЦветовоерешение\r\nтемно-серый\r\nРазмеры(ШхГхВ)\r\n384 х 258 х 34.2 мм\r\nВестовара\r\n2.5 кг',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(358,13,521,'Precision 7550','','https://dell-partner.ru/noutbuk-dell-precision-7550/','Диагональ экрана вдюймах\r\n15.6 \"\r\nРазрешениеэкрана\r\n3840х2160\r\nРазрешение матрицы экрана\r\nUHD\r\nТип матрицы\r\nVA\r\nТип матрицы маркетинговый\r\nWVA\r\nОперативная память\r\nОперативнаяпамять\r\n16 ГБ, DDR4, 3200 МГц\r\nУстройства хранения данных\r\nОбъемSSD\r\n1024 ГБ\r\nГрафические параметры\r\nТип графическогопроцессора\r\nдискретный\r\nГрафическийпроцессор\r\nNVIDIA Quadro RTX 4000 - 8 ГБ\r\nПоддержка трассировкилучей\r\nесть\r\nПоддержкаDLSS\r\nесть\r\nТехнологияNVIDIA\r\nReflex\r\nRTX Studio\r\nда\r\nПроцессор\r\nПроцессор\r\nIntel Core i7 10875H\r\nПроцессор,частота\r\n2.3 ГГц (5.1 ГГц, в режиме Turbo)\r\nКоличество ядерпроцессора\r\n8-ядерный\r\nКоммуникации ноутбука\r\nКард-ридер\r\nесть, поддержкаSD\r\nПоддержка технологииWi-Fi\r\nесть, 802.11 a/b/g/n/ac/ax\r\nПоддержка технологииBluetooth\r\nесть, v5.1\r\nКабельнаясеть(RJ-45)\r\n10/100/1000 (Gigabit Ethernet) Мбит/с\r\nРазъемы и интерфейсы ноутбука\r\nПорты USB 3.0(Type-A)\r\n2\r\nРазъемов Thunderbolt 3\r\n2\r\nРазъем miniDisplayPort\r\n1\r\nРазъемHDMI\r\n1\r\nОперационная система ноутбука\r\nРазрядность ОС\r\n64-bit\r\nОперационная система\r\nWindows 10 Professional\r\nМультимедийные особенности\r\nВеб-камера\r\nвстроенная\r\nРазъем длянаушников\r\nесть\r\nАкустическаясистема\r\nстереодинамики\r\nДополнительные характеристики\r\nГарантия\r\n12 мес.\r\nКлавиатура ноутбука\r\nЦвет клавиатурыноутбука\r\nчерный\r\nЦифровой блокклавиатуры\r\nесть\r\nПодсветка клавишклавиатуры\r\nесть\r\nСканер отпечаткапальца\r\nесть (Fingerprint)\r\nБатарея ноутбука\r\nТипбатареи\r\nLi-Ion\r\nКоличество ячеекбатареи\r\n6 cell\r\nЭнергоемкостьбатареи\r\n95 Wh\r\nКорпус ноутбука\r\nЦветовоерешение\r\nсерый\r\nРазмеры(ШхГхВ)\r\n360 х 242 х 27.36 мм\r\nВестовара\r\n2.45 кг',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(359,8,500,'SUA2200I','','https://www.apc.com/uk/en/product/SUA2200I/apc-smartups-2200va-usb-serial-230v/','Overview\r\nLead time Usually in Stock\r\nMain\r\nMain Input Voltage 230 V\r\nMain Output Voltage 230 V\r\nRated power in W 1980 W\r\nRated power in VA 2200 VA\r\nInput Connection Type IEC 320 C20\r\nSchuko CEE 7 / EU1-16P\r\nBS1363A British\r\nOutput connection type 8 IEC 320 C13\r\n2 IEC Jumpers\r\n1 IEC 320 C19\r\nNumber of rack unit 0U\r\nNumber of cables 1\r\nBattery type Lead-acid battery\r\nProvided equipment CD with software\r\nSmart UPS signalling RS-232 cable\r\nUser manual\r\nUSB cable\r\nBatteries & Runtime\r\nAdditional information Configurable for 220 : 230 or 240 nominal output voltage\r\nExtended runtime 0\r\nNumber of battery filled slots 0\r\nNumber of battery free slots 0\r\nBattery recharge time 3 h\r\nNumber of battery replacement\r\nquantity\r\n1\r\nBattery life 3…5 year(s)\r\nReplacement battery RBC55\r\nBattery power in VAH 816 VAh runtime\r\nBattery charger power 271 W rated\r\nDisclaimer: This documentation is not intended as a substitute for and is not to be used for determining suitability or reliability of these products for specific user applications\r\nJun 5, 2023 1\r\nGeneral\r\nNumber of power module filled\r\nslots\r\n0\r\nNumber of power module free\r\nslots\r\n0\r\nRedundant No\r\n',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(360,16,116,'2443NW','','https://www.samsung.com/ru/support/model/LS24MYNKBB/EDC/','Диагональ\r\n24 \"\r\nМакс. разрешение\r\n1920x1200\r\nСоотношение сторон\r\n16:10\r\nТип матрицы экрана\r\nTN\r\nМакс. частота обновления кадров\r\n61 Гц\r\nЭкран\r\nШаг точки по горизонтали\r\n0.27 мм\r\nШаг точки по вертикали\r\n0.27 мм\r\nЯркость\r\n300 кд/м2\r\nКонтрастность\r\n1000:1\r\nДинамическая контрастность\r\n50000:1\r\nВремя отклика\r\n5 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n170 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nВидимый размер экрана\r\n24 \"\r\nИзображение\r\nкалибровка цвета\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход VGA\r\nПитание\r\nБлок питания\r\nвстроенный\r\nПотребляемая мощность при работе\r\n50 Вт\r\nПотребляемая мощность в режиме ожидания\r\n1 Вт\r\nПотребляемая мощность в спящем режиме\r\n1 Вт\r\nПрочее\r\nЭкологический стандарт\r\nTCO\'\'03\r\nШирина\r\n556 мм\r\nВысота\r\n447 мм\r\nГлубина\r\n228 мм\r\nВес\r\n5.7 кг\r\nДополнительная информация\r\nдля мониторов выпущенных до сентября 2009 - динамическая контрастность 20000:1; мин. частота строк: 30 кГц; макс. частота строк: 75 кГц; мин. частота обновления кадров: 56 Гц; plug\'n\'Play: DDC 2B; энергопотребление: VESA DPMS\r\n',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(361,16,116,'S23A350H','','https://www.samsung.com/ru/support/model/LS23A350HS/CI/','Диагональ\r\n23 \"\r\nМакс. разрешение\r\n1920x1080\r\nСоотношение сторон\r\n16:9\r\nТип LED-подсветки\r\nWLED\r\nТип матрицы экрана\r\nTN\r\nЭкран\r\nЯркость\r\n250 кд/м2\r\nКонтрастность\r\n1000:1\r\nВремя отклика\r\n2 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n170 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nВидимый размер экрана\r\n23 \"\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход HDMI, вход VGA\r\nПитание\r\nБлок питания\r\nвнешний\r\nПотребляемая мощность при работе\r\n29 Вт\r\nПотребляемая мощность в спящем режиме\r\n0.3 Вт\r\nПрочее\r\nШирина\r\n556 мм\r\nВысота\r\n426 мм\r\nГлубина\r\n239 мм\r\nВес\r\n3.1 кг\r\nДополнительная информация\r\nplug\'n\'Play: DDC 2B',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(362,16,55,'VH222','','','Размерэкрана\r\n21.5 \"\r\nРазрешениеэкрана\r\n1920x1080\r\nСоотношение сторонэкрана\r\n16:9\r\nТипматрицы\r\nTN+film\r\nДинамическаяконтрастность\r\n20000:1\r\nЯркостьэкрана\r\n300 кд/м2\r\nВремя отклика(GTG)\r\n5 мс\r\nУглы обзора (приCR>10)\r\n170° по горизонтали, 160° по вертикали\r\nШагпикселя\r\n0.248 х 0.248 мм\r\nЭргономика монитора\r\nНаклонэкрана\r\nесть\r\nУгол наклонаэкрана\r\n-5°/+20°\r\nИнтерфейсы и разъемы\r\nКоличество разъемов VGA(D-SUB)\r\n1\r\nЭлектропитание монитора\r\nТип блокапитания\r\nвнутренний\r\nЭнергопотребление\r\n55 Вт\r\nЭнергопотребление в режимеожидания\r\nменее 2 Вт\r\nКорпус монитора\r\nРазмер крепленияVESA\r\n100х100\r\nЦвет\r\nчерный\r\nПокрытиекорпуса\r\nглянцевое\r\nРазмеры с подставкой(ШхВхГ)\r\n515х405х220 мм\r\nВестовара\r\n4.9 кг',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(363,16,116,'223BW','','https://www.samsung.com/ru/support/model/LS22MEVSFV/EDC/','Диагональ\r\n21.6 \"\r\nМакс. разрешение\r\n1680x1050\r\nСоотношение сторон\r\n16:10\r\nМакс. частота обновления кадров\r\n75 Гц\r\nОсобенности\r\nрегулировка по высоте\r\nЭкран\r\nШаг точки по горизонтали\r\n0.276 мм\r\nШаг точки по вертикали\r\n0.276 мм\r\nЯркость\r\n300 кд/м2\r\nКонтрастность\r\n1000:1\r\nДинамическая контрастность\r\n3000:1\r\nВремя отклика\r\n5 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n170 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nВидимый размер экрана\r\n21.6 \"\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход DVI-D, вход VGA\r\nПитание\r\nБлок питания\r\nвстроенный\r\nПотребляемая мощность при работе\r\n50 Вт\r\nПрочее\r\nЭкологический стандарт\r\nTCO\'\'03\r\nШирина\r\n515 мм\r\nВысота\r\n422 мм\r\nГлубина\r\n219 мм\r\nВес\r\n4.9 кг\r\nДополнительная информация\r\nмин. частота строк: 30 кГц; макс. частота строк: 81 кГц; мин. частота обновления кадров: 56 Гц; поддержка HDCP в DVI-интерфейсе; plug\'n\'Play: DDC 2B',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(364,16,116,'B2430L','','https://www.samsung.com/ru/support/model/LS24PULKF/EN/','Диагональ\r\n23.6 \"\r\nМакс. разрешение\r\n1920x1080\r\nСоотношение сторон\r\n16:9\r\nТип матрицы экрана\r\nTN\r\nМакс. частота обновления кадров\r\n75 Гц\r\nЭкран\r\nШаг точки по горизонтали\r\n0.2715 мм\r\nШаг точки по вертикали\r\n0.2715 мм\r\nЯркость\r\n300 кд/м2\r\nКонтрастность\r\n1000:1\r\nДинамическая контрастность\r\n70000:1\r\nВремя отклика\r\n5 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n170 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nВидимый размер экрана\r\n23.6 \"\r\nИзображение\r\nкалибровка цвета\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход DVI-D, вход VGA\r\nПитание\r\nБлок питания\r\nвстроенный\r\nПотребляемая мощность при работе\r\n45 Вт\r\nПотребляемая мощность в режиме ожидания\r\n0.3 Вт\r\nПотребляемая мощность в спящем режиме\r\n0.3 Вт\r\nПрочее\r\nКрепление для кронштейна\r\nесть\r\nСтандарт настенного крепления\r\n75x75 мм\r\nШирина\r\n582 мм\r\nВысота\r\n448 мм\r\nГлубина\r\n197 мм\r\nВес\r\n5.2 кг\r\nДополнительная информация\r\nпрограммируемая кнопка управления; таймер выключения; полоса пропускания: 164 МГц; мин. частота строк: 30 кГц; макс. частота строк: 81 кГц; мин. частота обновления кадров: 56 Гц; поддержка HDCP в DVI-интерфейсе; plug\'n\'Play: DDC 2B',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(365,16,116,'C49RG90SSI','','https://www.samsung.com/ru/monitors/gaming/qled-gaming-monitor-with-dual-qhd-resolution-49-inch-lc49rg90ssixci/','Тип \r\nмонитор\r\nМодель\r\nSamsung C49RG90SSI\r\nКод производителя\r\n[LC49RG90SSIXCI]\r\nОсновной цвет\r\nчерный\r\nЭкран\r\nИзогнутый экран \r\nда\r\nРадиус изогнутости \r\n1800R\r\nДиагональ экрана (дюйм) \r\n49\"\r\nМаксимальное разрешение \r\n5120x1440\r\nТип подсветки матрицы \r\nQLED\r\nТехнология изготовления матрицы \r\nVA\r\nТип ЖК-матрицы (подробно) \r\nSVA\r\nСоотношение сторон \r\n32:9\r\nСенсорный экран \r\nнет\r\nПокрытие экрана \r\nматовое\r\nПоддержка HDR \r\nDisplayHDR 1000\r\nТехнология защиты зрения \r\nесть\r\nТехнические характеристики экрана\r\nРазмер видимой области экрана \r\n1193.5 x 335.7 мм\r\nЯркость \r\n600 Кд/м²\r\nКонтрастность \r\n3000:1\r\nДинамическая контрастность \r\nMega DCR\r\nВремя отклика пикселя (GtG) \r\n4 мс\r\nУгол обзора по вертикали (градус) \r\n178°\r\nУгол обзора по горизонтали (градус) \r\n178°\r\nТехнология динамического обновления экрана \r\nAMD FreeSync\r\nРазмер пикселя \r\n234 мкм\r\nПлотность пикселей \r\n108 ppi\r\nЧастота при максимальном разрешении \r\n120 Гц\r\nМаксимальная частота обновления экрана \r\n120 Гц\r\nГлубина цвета\r\n10bit\r\nИнтерфейсы\r\nВидео разъемы \r\nDisplayPort 1.4 x2, HDMI 2.0\r\nUSB-концентратор \r\nесть\r\nКоличество USB\r\n4 шт\r\nВыход на наушники\r\nесть\r\nРазъем HDMI \r\nесть\r\nРазъем DisplayPort \r\nесть\r\nРазъем DVI \r\nнет\r\nРазъем VGA \r\nнет\r\nФункции\r\nКартинка в картинке \r\nесть\r\nЦветовой охват sRGB \r\n125%\r\nЦветовой охват Adobe RGB \r\n92%\r\nКонструкция\r\nБезрамочный дизайн \r\nтрехсторонний\r\nРазмер VESA \r\n100x100\r\nПоворотная подставка \r\nесть\r\nРегулировка по высоте\r\nесть\r\nРегулировка наклона\r\nесть\r\nПоворот на 90° (портретный режим) \r\nнет\r\nДополнительное оборудование\r\nВстроенная акустическая система \r\nнет\r\nВеб-камера\r\nнет\r\nПитание\r\nРасположение блока питания \r\nвстроенный\r\nПотребляемая мощность при работе \r\n100 Вт\r\nПотребляемая мощность в спящем режиме \r\n0.5 Вт\r\nМощность в выключенном режиме\r\n0.3 Вт\r\nНапряжение питания\r\n100-240 В / 50-60 Гц\r\nДополнительно\r\nКомплектация\r\nдиск с ПО, кабель DisplayPort, кабель HDMI, кабель USB, кабель питания\r\nПодсветка \r\nнет\r\nГабариты, вес\r\nШирина без подставки\r\n1199.5 мм\r\nВысота без подставки\r\n369.4 мм\r\nТолщина без подставки\r\n193.7 мм\r\nВес без подставки\r\n11.6 кг\r\nШирина с подставкой\r\n1199.5 мм\r\nМинимальная высота с подставкой\r\n523.1 мм\r\nМаксимальная высота с подставкой\r\n643.1 мм\r\nТолщина с подставкой\r\n349.7 мм\r\nВес с подставкой\r\n14.6 кг',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(366,16,116,'B2223NW','','','Диагональ\r\n19.1\"-22\"\r\nМакс. разрешение\r\n1680x1050\r\nМакс. частота обновления кадров\r\n61-99 Гц\r\nТип матрицы экрана\r\nTN\r\nСоотношение сторон\r\n16:10\r\nИнтерфейсы видео\r\nвход VGA\r\nОсновные характеристики\r\nТип монитора\r\nЖК\r\nДиагональ\r\n22 \"\r\nМакс. разрешение\r\n1680x1050\r\nСоотношение сторон\r\n16:10\r\nТип матрицы экрана\r\nTN\r\nМакс. частота обновления кадров\r\n75 Гц\r\nЭкран\r\nШаг точки по горизонтали\r\n0.282 мм\r\nШаг точки по вертикали\r\n0.282 мм\r\nЯркость\r\n300 кд/м2\r\nКонтрастность\r\n1000:1\r\nВремя отклика\r\n5 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n170 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nВидимый размер экрана\r\n22 \"\r\nИзображение\r\nкалибровка цвета\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход VGA\r\nПитание\r\nБлок питания\r\nвстроенный\r\nПотребляемая мощность при работе\r\n41 Вт\r\nПотребляемая мощность в спящем режиме\r\n1 Вт\r\nПрочее\r\nЭкологический стандарт\r\nTCO\'\'03\r\nШирина\r\n511 мм\r\nВысота\r\n418 мм\r\nГлубина\r\n218 мм\r\nВес\r\n4.9 кг\r\nДополнительная информация\r\nмин. частота строк: 30 кГц; макс. частота строк: 81 кГц; мин. частота обновления кадров: 56 Гц; plug\'n\'Play: DDC 2B',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(367,16,526,'24MB67PY','','https://www.lg.com/ru/monitors/lg-24MB67PY-B','Диагональ\r\n23.1\"-24\"\r\nМакс. разрешение\r\n1920x1200\r\nМакс. частота обновления кадров\r\n61-99 Гц\r\nТип матрицы экрана\r\nIPS\r\nСоотношение сторон\r\n16:10\r\nИнтерфейсы видео\r\nвход DVI-D, вход VGA, вход DisplayPort\r\nОсобенности\r\nПоддержка стандарта ISO 13406-2, встроенные колонки, регулировка по высоте, поворот на 90 градусов\r\nЦвет\r\nОсновные характеристики\r\nТип монитора\r\nЖК\r\nДиагональ\r\n24 \"\r\nМакс. разрешение\r\n1920x1200\r\nСоотношение сторон\r\n16:10\r\nТип LED-подсветки\r\nWLED\r\nТип матрицы экрана\r\nIPS\r\nМакс. частота обновления кадров\r\n75 Гц\r\nОсобенности\r\nПоддержка стандарта ISO 13406-2, встроенные колонки, поворот на 90 градусов, регулировка по высоте\r\nЭкран\r\nШаг точки по горизонтали\r\n0.27 мм\r\nШаг точки по вертикали\r\n0.27 мм\r\nЯркость\r\n250 кд/м2\r\nКонтрастность\r\n1000:1\r\nДинамическая контрастность\r\n5000000:1\r\nВремя отклика\r\n5 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n178 градусов\r\nВертикальный угол обзора\r\n178 градусов\r\nПокрытие экрана\r\nантибликовое, матовое\r\nВидимый размер экрана\r\n24 \"\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход DVI-D, вход DisplayPort, вход VGA\r\nИнтерфейсы\r\nUSB Type A x 2, USB Type B, USB-концентратор, вход аудио стерео, выход на наушники\r\nUSB-концентратор\r\nесть\r\nВерсия USB\r\n2.0\r\nПитание\r\nБлок питания\r\nвстроенный\r\nПотребляемая мощность при работе\r\n23 Вт\r\nПотребляемая мощность в режиме ожидания\r\n0.3 Вт\r\nПотребляемая мощность в спящем режиме\r\n0.3 Вт\r\nПрочее\r\nКоличество встроенных динамиков\r\n2\r\nМощность динамиков (на канал)\r\n1 Вт\r\nКрепление для кронштейна\r\nесть\r\nСтандарт настенного крепления\r\n100x100 мм\r\nПоддержка стандарта ISO 13406-2\r\nесть\r\nЭкологический стандарт\r\nTCO 6.0\r\nШирина\r\n558 мм\r\nВысота\r\n395 мм\r\nГлубина\r\n259 мм\r\nВес\r\n5.4 кг\r\nДополнительная информация\r\nмин. частота строк: 30 кГц; макс. частота строк: 83 кГц; мин. частота обновления кадров: 56 Гц; поддержка HDCP в DVI-интерфейсе; plug\'n\'Play: DDC/CI; энергопотребление: Energy Star 6.0',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(368,16,116,'913N','','https://www.samsung.com/ru/support/model/MJ19ESTSB/EDC/','Диагональ\r\n19 \"\r\nМакс. разрешение\r\n1280x1024\r\nСоотношение сторон\r\n5:4\r\nТип матрицы экрана\r\nTN\r\nМакс. частота обновления кадров\r\n75 Гц\r\nЭкран\r\nШаг точки по горизонтали\r\n0.294 мм\r\nШаг точки по вертикали\r\n0.294 мм\r\nЯркость\r\n300 кд/м2\r\nКонтрастность\r\n700:1\r\nВремя отклика\r\n8 мс\r\nМаксимальное количество цветов\r\n16.2 млн.\r\nГоризонтальный угол обзора\r\n160 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nПокрытие экрана\r\nматовое\r\nВидимый размер экрана\r\n19 \"\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход VGA\r\nПитание\r\nБлок питания\r\nвстроенный\r\nПотребляемая мощность при работе\r\n38 Вт\r\nПотребляемая мощность в режиме ожидания\r\n1 Вт\r\nПрочее\r\nЭкологический стандарт\r\nTCO\'\'03\r\nШирина\r\n416 мм\r\nВысота\r\n425 мм\r\nГлубина\r\n215 мм\r\nВес\r\n6 кг\r\nДополнительная информация\r\nполоса пропускания: 140 МГц; мин. частота строк: 30 кГц; макс. частота строк: 81 кГц; мин. частота обновления кадров: 56 Гц; plug\'n\'Play: DDC 2B',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(369,16,116,'S24A300BL','','https://www.samsung.com/ru/support/model/LS24A300BL/CI/','Диагональ\r\n23.1\"-24\"\r\nМакс. разрешение\r\n1920x1080\r\nТип матрицы экрана\r\nTN\r\nСоотношение сторон\r\n16:9\r\nИнтерфейсы видео\r\nвход DVI-D, вход VGA\r\nОсновные характеристики\r\nТип монитора\r\nЖК\r\nДиагональ\r\n23.6 \"\r\nМакс. разрешение\r\n1920x1080\r\nСоотношение сторон\r\n16:9\r\nТип LED-подсветки\r\nWLED\r\nТип матрицы экрана\r\nTN\r\nЭкран\r\nЯркость\r\n250 кд/м2\r\nКонтрастность\r\n1000:1\r\nВремя отклика\r\n5 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n170 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nВидимый размер экрана\r\n23.6 \"\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход DVI-D, вход VGA\r\nПитание\r\nБлок питания\r\nвнешний\r\nПотребляемая мощность при работе\r\n27 Вт\r\nПотребляемая мощность в спящем режиме\r\n0.3 Вт\r\nПрочее\r\nШирина\r\n569 мм\r\nВысота\r\n416 мм\r\nГлубина\r\n197 мм\r\nВес\r\n3.8 кг\r\nДополнительная информация\r\nподдержка HDCP в DVI-интерфейсе; plug\'n\'Play: DDC 2B',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(370,13,35,'Envy dv6-7300','Envy dv6','','Разрешение: 1366x768\r\nПроцессор: Intel Core i7 2.4 ГГц, \r\nОЗУ 8 ГБ DDR3, \r\nОбъем жесткого диска: 1000 ГБ\r\nВидеокарта: GeForce GT 635M\r\nСтандарт Wi-Fi: 802.11n\r\nОптический привод: DVD-RW',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(371,16,116,'S27A650D','','','Диагональ\r\n27 \"\r\nМакс. разрешение\r\n1920x1080\r\nСоотношение сторон\r\n16:9\r\nТип LED-подсветки\r\nWLED\r\nТип матрицы экрана\r\nMVA\r\nОсобенности\r\nповорот на 90 градусов, регулировка по высоте\r\nЭкран\r\nЯркость\r\n300 кд/м2\r\nКонтрастность\r\n3000:1\r\nВремя отклика\r\n8 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n178 градусов\r\nВертикальный угол обзора\r\n178 градусов\r\nВидимый размер экрана\r\n27 \"\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход DVI-D, вход DisplayPort, вход VGA\r\nПитание\r\nБлок питания\r\nвнешний\r\nПотребляемая мощность при работе\r\n42 Вт\r\nПотребляемая мощность в спящем режиме\r\n0.4 Вт\r\nПрочее\r\nКрепление для кронштейна\r\nесть\r\nСтандарт настенного крепления\r\n100x200 мм\r\nШирина\r\n643 мм\r\nВысота\r\n466 мм\r\nГлубина\r\n225 мм\r\nВес\r\n6.3 кг\r\nДополнительная информация\r\nVESA 100 x 100, 100 x 200; plug\'n\'Play: DDC 2B\r\n',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(372,16,116,'S27A550H','','https://www.samsung.com/ru/support/model/LS27A550HS/CI/','Диагональ\r\n24.1\"-27\"\r\nМакс. разрешение\r\n1920x1080\r\nТип матрицы экрана\r\nTN\r\nСоотношение сторон\r\n16:9\r\nИнтерфейсы видео\r\nвход HDMI, вход VGA\r\nОсновные характеристики\r\nТип монитора\r\nЖК\r\nДиагональ\r\n27 \"\r\nМакс. разрешение\r\n1920x1080\r\nСоотношение сторон\r\n16:9\r\nТип LED-подсветки\r\nWLED\r\nТип матрицы экрана\r\nTN\r\nЭкран\r\nЯркость\r\n300 кд/м2\r\nКонтрастность\r\n1000:1\r\nВремя отклика\r\n2 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n170 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nВидимый размер экрана\r\n27 \"\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход HDMI, вход VGA\r\nПитание\r\nБлок питания\r\nвнешний\r\nПотребляемая мощность при работе\r\n40 Вт\r\nПотребляемая мощность в спящем режиме\r\n0.5 Вт\r\nПрочее\r\nШирина\r\n648 мм\r\nВысота\r\n483 мм\r\nГлубина\r\n258 мм\r\nВес\r\n4.3 кг\r\nДополнительная информация\r\nplug\'n\'Play: DDC 2B',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(373,13,35,'Pavilion 17-ab024UR','','https://support.hp.com/kz-ru/document/c05384889','номер продукта\r\n1BX44EA\r\nНазвание продукта\r\nHP Pavilion 17-ab024ur\r\nМикропроцессор\r\nПроцессор Intel® Core™ i7-6700HQ (тактовая частота 2,6 ГГц с возможностью увеличения до 3,5 ГГц с помощью технологии Intel® Turbo Boost, 6 Мбайт кэш-памяти, 4 ядра)\r\nМикросхема\r\nIntel HM170\r\nПамять, стандартная\r\nПамять DDR4-2133 SDRAM, 12 Гбайт (1 x 4 Гбайт, 1 x 8 Гбайт)\r\nВидеокарта\r\nNVIDIA® GeForce® GTX 960M (4 Гбайт выделенной памяти GDDR5)\r\nЖесткий диск\r\nДиск 2 TБ, 5400 об./мин SATA\r\nОптический дисковод\r\nУстройство записи-DVD\r\nМонитор\r\nДисплей Full HD IPS UWVA диагональю 43,9 см (17,3\") с антибликовым покрытием и белой светодиодной подсветкой (1920 x 1080)\r\nКлавиатура\r\nПолноразмерная клавиатура островного типа с подсветкой и цифровой клавишной панелью\r\nУправление курсором или тачпад\r\nСенсорная панель HP с поддержкой технологии Multi-Touch\r\nБеспроводная связь\r\nКомбинированный модуль Intel® 802.11ac (2 x 2) с поддержкой Wi-Fi® и Bluetooth® 4.2 (поддержка Miracast)\r\nСетевой интерфейс\r\nВстроенный 10/100/1000 Gigabit Ethernet LAN\r\nСлоты расширения\r\n1 многоформатное устройство считывания карт памяти SD\r\nВнешние порты\r\n1 порт HDMI; 1 комбинированный разъем для наушников/микрофона; 1 порт USB 2.0; 2 порта USB 3.0; 1 разъем RJ-45\r\nМинимальный размер (Ш x Г x В)\r\n41,6 x 27,9 x 2,99 см\r\nВес\r\n2,85 кг\r\nТип блока питания\r\nБлок питания от сети переменного тока, 120 Вт\r\nТип аккумулятора\r\n6-элементный литий-ионный аккумулятор, 62 Вт-ч\r\nТип аккумулятора примечание\r\nСъемный аккумулятор\r\nвеб-камера\r\nКамера HP Wide Vision HD с двунаправленным цифровым микрофоном\r\nФункции обработки звука\r\nB&O PLAY; HP Audio Boost; два динамика',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(374,16,521,'2312HMt','','','Диагональ: 23\"\r\nРазрешение: 1920x1080 (16:9)\r\nТип матрицы экрана: TFT IPS\r\nПодсветка: WLED\r\nМакс. частота обновления кадров: 76 Гц\r\nЯркость: 300 кд/м2\r\nКонтрастность: 1000:1\r\nДинамическая контрастность: 2000000:1\r\nВремя отклика: 8 мс\r\nОбласть обзора: по горизонтали: 178°, по вертикали: 178°\r\nМаксимальное количество цветов: 16.7 млн.\r\nПокрытие экрана: антибликовое\r\nЧастота обновления: строк: 30-80 кГц; кадров: 56-76 Гц\r\nВходы: DVI-D (HDCP), DisplayPort, VGA (D-Sub)\r\nИнтерфейсы: USB Type A x4\r\nUSB-концентратор: есть, количество портов: 4\r\nВерсия USB: USB 2.0\r\nБлок питания: встроенный\r\nПотребляемая мощность: при работе: 30 Вт, в режиме ожидания: 0.50 Вт, в спящем режиме: 0.50 Вт\r\nРегулировка по высоте: есть\r\nПоворот на 90 градусов: есть\r\nНастенное крепление: есть, 100x100 мм\r\nРазмеры, вес: 546x365x185 мм, 5.18 кг',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(375,3,21,'S5520HC','','','Сокет: LGA1366\r\nназвание чипсета: Intel 5520\r\nформ-фактор: SSI EEB\r\nтип памяти: DDR3 DIMM ECC/non-ECC, количество слотов памяти: 12\r\n',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45','reviakin.a',NULL),(376,8,500,'Батарейный модуль SYBT5','SYBT5','','Для работы APC Symmetra',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(377,1,537,'B560Wi711700/2xDDR4 16GB/ GTX1650 4GB/SSD SATA 1Tb/600WlWin10Pro','','','Системный блок RDW B560Wi711700/2xDDR4 16GB/GTX1650 4GB/SSD SATA 1Tb/600WlWin10Pro',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(378,16,537,'RDW-2416I','','','Яркость экрана: 250кд/м2\r\nВремя отклика: 5мс\r\nРазъем D-SUB (VGA): ДА\r\nРазъем HDMI: ДА\r\nДиагональ экрана: 23.8\" (60.45см)\r\nРазрешение: 1920x1080\r\nУглы обзора : 178/178\r\nТип матрицы: IPS\r\nКоличество разъемов HDMI: 1 ',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(379,1,537,'mini S2-B560 (i5)','','','i5-11500 2.7GHz 2.71GHz\r\n8Gb\r\nSSD 450Gb\r\nWin10Pro',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(380,16,116,'2243SN','','https://www.samsung.com/ru/support/model/LS22MYYKBBA/EN/','Основные характеристики\r\nТип монитора\r\nЖК\r\nДиагональ\r\n21.5 \"\r\nМакс. разрешение\r\n1920x1080\r\nСоотношение сторон\r\n16:9\r\nТип матрицы экрана\r\nTN\r\nМакс. частота обновления кадров\r\n75 Гц\r\nЭкран\r\nШаг точки по горизонтали\r\n0.248 мм\r\nШаг точки по вертикали\r\n0.248 мм\r\nЯркость\r\n300 кд/м2\r\nКонтрастность\r\n1000:1\r\nДинамическая контрастность\r\n50000:1\r\nВремя отклика\r\n5 мс\r\nМаксимальное количество цветов\r\n16.7 млн.\r\nГоризонтальный угол обзора\r\n170 градусов\r\nВертикальный угол обзора\r\n160 градусов\r\nВидимый размер экрана\r\n21.5 \"\r\nИнтерфейсы\r\nИнтерфейсы видео\r\nвход VGA\r\nПитание\r\nБлок питания\r\nвстроенный\r\nПотребляемая мощность при работе\r\n45 Вт\r\nПотребляемая мощность в спящем режиме\r\n1 Вт\r\nПрочее\r\nКрепление для кронштейна\r\nесть\r\nСтандарт настенного крепления\r\n100x100 мм\r\nЭкологический стандарт\r\nTCO\'\'03\r\nШирина\r\n513 мм\r\nВысота\r\n402 мм\r\nГлубина\r\n218 мм\r\nВес\r\n4.6 кг\r\nДополнительная информация\r\nсовместимость с МАС; мин. частота строк: 31 кГц; макс. частота строк: 80 кГц; мин. частота обновления кадров: 56 Гц; plug\'n\'Play: DDC 2B',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:45',NULL,NULL),(381,7,180,'SG 200-08P','','https://cisco-russia.ru/cisco-sg200-08p-8-port-gigabit-poe-smart-switch','Управляемый L2\r\nИнтерфейсы: 8 x 1000Base-T - RJ-45 - PoE\r\nPoE 4 порта\r\nSwitching capacity : 13.6 Gbps /11.9 Mpps\r\nУдаленное управление: SNMP, RMON, HTTP, TFTP',0,'1\r\n2\r\n3\r\n4\r\n5\r\n6\r\n7\r\n8',279,'',0,0,'',0,0,'2023-09-08 03:20:30',NULL,NULL),(383,3,35,'ProLiant DL385 G7','','','https://www.proliant.ru/catalog/servers/DL/servery_snjatye_s_proizvodstva/Kartochki_6013.html\r\n',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:46',NULL,NULL),(384,16,520,'242E2FA/01','','','1920x1080 (FullHD)@75 Гц, IPS, LED, 1000:1, 300 Кд/м², 178°/178°, DisplayPort 1.2, HDMI 1.4, VGA (D-Sub), AMD',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:46',NULL,NULL),(385,13,169,'VAIO SVS13A1Z9RN','VAIO SVS13A1Z9RN','','https://www.sony.ru/electronics/support/laptop-pc-svs-series/svs13a1z9r/specifications',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:46',NULL,NULL),(386,13,35,'Probook 450 G8','','','Model ID: 32M40EA#ACB\r\n\r\nWindows 10 Pro (64-разрядная)\r\nIntel® Core™ i5 11-го поколения (i5-1135G7)\r\n8 Гбайт памяти; твердотельный накопитель, 512 Гбайт\r\nПамять DDR4-3200 SDRAM, 8 Гбайт (1 x 8 Гбайт).\r\nFull HD (1920 x 1080), диагональ 39,6 см (15,6\"), IPS, антибликовое покрытие, 250 cd/m², NTSC 45% [14,15,16,17,39]\r\nВстроенный Intel® UHD Graphics 620 Дискретный NVIDIA® GeForce® MX130 (2 Гбайт выделенной памяти DDR5)',0,'',NULL,'',0,0,'',0,0,'2023-09-07 19:12:47',NULL,NULL),(387,8,542,'МУЛЬТИПЛЕКС СТ150','МУЛЬТИПЛЕКС СТ150','Описание производителя https://impuls.energy/ibp/modulnye/silovoy-shkaf-multiplex-150-kva','Модульный ИБП\r\nУправляемый, трехфазный (вход и выход)\r\nСиловые модули	СМ25 (до 6 шт.)\r\nВес без батареи	140 кг\r\nГабариты ШхГхВ	482x916x931 мм\r\nВлажность	0-95% без конденсации\r\nРабочие температуры	0-40°C',1,'',280,'{\"cols\":[{\"type\":\"units\",\"size\":482,\"count\":\"1\"}],\"rows\":[{\"type\":\"title\",\"size\":\"440\"},{\"type\":\"units\",\"size\":466,\"count\":\"6\"},{\"type\":\"void\",\"size\":\"25\"}],\"hEnumeration\":\"1\",\"vEnumeration\":\"1\",\"evenEnumeration\":\"1\",\"priorEnumeration\":\"h\",\"labelPre\":1,\"labelPost\":1,\"labelMode\":\"h\",\"labelWidth\":\"25\"}',1,0,'',0,0,'2023-09-08 03:20:30','reviakin.a',NULL),(388,8,542,'МУЛЬТИПЛЕКС СМ 25','СМ 25','Описание производителя','Силовой модуль трехфазный\r\nСиловые шкафы	СТ150, СТ200\r\nНоминальная мощность	25 кВА / 25 кВт\r\nГабариты, ШхГхВ	436х677х85 мм',0,'',281,'',0,0,'',0,0,'2023-09-08 03:20:30','reviakin.a',NULL),(389,20,503,'BK1216-P8','','Спецификация https://www.beward.ru/katalog/ip-videoservery/ip-videoregistratory/ip-videoregistrator-bk1216-p8/','IP Видеорегистратор на 16 каналов, макс. количество подключаемых IP видеокамер - 8\r\nТип подключаемых видеокамер - IP\r\n1x Gbit Eth (uplink), \r\n8x 100Mbit PoE (switch) бюджет 80Вт\r\nНаличие накопителей в поставляемой конфигурации - нет\r\nHDD: 2x SATA/SAS 3,5\" (макс 12Тб в сумме)\r\nМаксимальное разрешение записи - 3072x2048 (6 МП)\r\nРежимы записи - Непрерывный, По детекции движения, По расписанию, По событию\r\n2x USB 2.0, VGA, HDMI выходы\r\nOS: Linux\r\n2 внешних блока питания \r\n12 В 3.3 А (Регистратор), 48 В 1.9 А (PoE)\r\nпо умолч:\r\nEth1: 192.168.0.199/24\r\nEth2(switch):192.168.2.88/24\r\nadmin:123456',0,'Eth1\r\nCam1\r\nCam2\r\nCam3\r\nCam4\r\nCam5\r\nCam6\r\nCam7\r\nCam8',292,'',0,0,'',0,0,'2023-09-15 14:33:33','admin',NULL),(390,12,35,'MSA 2052 SAN DC SFF','MSA2052','hpe https://www.hpe.com/ru/ru/product-catalog/storage/disk-storage/pip.hpe-msa-2052-san-storage.1009949625.html','iSCSI/FC (в зависимости от устанавливаемых GBiC модулей)\r\n2 контроллера (x4 SFP-ports)\r\n2 блока питания',0,'Head1\r\nHead2',295,'',0,0,'',0,0,'2023-09-15 16:08:22','admin',NULL);
/*!40000 ALTER TABLE `tech_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tech_models_history`
--

DROP TABLE IF EXISTS `tech_models_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tech_models_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `type_id` int DEFAULT NULL,
  `manufacturers_id` int DEFAULT NULL,
  `scans_id` int DEFAULT NULL,
  `individual_specs` tinyint(1) DEFAULT NULL,
  `contain_front_rack` tinyint(1) DEFAULT NULL,
  `contain_back_rack` tinyint(1) DEFAULT NULL,
  `front_rack_two_sided` tinyint(1) DEFAULT NULL,
  `back_rack_two_sided` tinyint(1) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `short` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ports` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `front_rack_layout` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `back_rack_layout` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `tech_models_history-master_id` (`master_id`),
  KEY `tech_models_history-updated_at` (`updated_at`),
  KEY `tech_models_history-updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tech_models_history`
--

LOCK TABLES `tech_models_history` WRITE;
/*!40000 ALTER TABLE `tech_models_history` DISABLE KEYS */;
INSERT INTO `tech_models_history` VALUES (1,193,'2025-05-14 09:19:21',NULL,NULL,'type_id,manufacturers_id,scans_id,individual_specs,contain_front_rack,contain_back_rack,front_rack_two_sided,back_rack_two_sided,name,links,comment',16,521,240,0,0,0,0,0,NULL,'U2415',NULL,'https://dell-partner.ru/u2415/\r\nhttps://www.ixbt.com/monitor/dell-u2415.shtml','Диагональ: 24.1\" @1920 x 1200 (16:10)\r\nТип матрицы: AH-IPS Матовая\r\nПоворотный экран, регулировка по высоте\r\n2x HDMI, DP, miniDP, \r\nВыход DP, аудиовыход джек 3.5 мм\r\nUSB-концентратор на 5 портов USB 3.0\r\nКабель Mini DP -> DP',NULL,NULL,NULL);
/*!40000 ALTER TABLE `tech_models_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tech_states`
--

DROP TABLE IF EXISTS `tech_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tech_states` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `archived` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `tech_states_archived_index` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tech_states`
--

LOCK TABLES `tech_states` WRITE;
/*!40000 ALTER TABLE `tech_states` DISABLE KEYS */;
INSERT INTO `tech_states` VALUES (1,'state_issued','На руках','Выдано пользователю на руки.',0),(2,'state_confirmed','Согл','Приобретение согласовано. Формируются заявки / счета / запросы.',0),(3,'state_in_supply_service','Снабж.','В службе снабжения: все документы переданы в службу снабжения, ожидаем приобретения и доставки к месту потребности оборудования / ПО.',0),(4,'state_in_warehouse','Склад','На складе. В настоящий момент не установлен, но имеется в наличии',0),(5,'state_operating','ОК','Находится в работе по месту установки.',0),(6,'state_malfunction','Замеч.','К работе оборудования имеются замечания, необходимо устранить.',0),(7,'state_broken','Сломан','Полностью не работоспособен. Требуется ремонт или списание.',0),(8,'state_decommisioned','Списано','Выведено из эксплуатации.',1);
/*!40000 ALTER TABLE `tech_states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tech_types`
--

DROP TABLE IF EXISTS `tech_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tech_types` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `code` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название',
  `prefix` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Комментарий',
  `comment_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment_hint` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_computer` tinyint(1) DEFAULT '0',
  `is_phone` tinyint(1) DEFAULT '0',
  `is_ups` tinyint(1) DEFAULT '0',
  `is_display` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `hide_menu` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `name` (`name`),
  KEY `idx-tech_types-archived` (`archived`),
  KEY `idx-tech_types-hide_menu` (`hide_menu`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tech_types`
--

LOCK TABLES `tech_types` WRITE;
/*!40000 ALTER TABLE `tech_types` DISABLE KEYS */;
INSERT INTO `tech_types` VALUES (1,'pc','ПК','ПК','Персональный компьютер (создано автоматически при обновлении БД)',NULL,NULL,1,0,0,0,'2023-09-04 04:16:59',NULL,NULL,NULL),(2,'voip_phone','IP Телефон','ТЕЛ','Количество линий?\r\nУмеет провижнинг?\r\nУмеет отказоустойчивость?\r\nПитание POE/ БП (идет ли в комплекте)?\r\nМожно включать бриджом?\r\nЛогин / пароль по умолч.\r\nнеочевидное расположение серийного номера?','Внутренний номер','Внутренний телефонный номер, назначенный этому аппарату',0,1,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(3,'srv','Серв. оборуд.','СРВ','Назначение оборудования\r\nПроцессор: модель @частота (количество ядер)\r\nПамять: объем (тип)\r\nНМЖД: тип объем @rpm, RAID контроллеры\r\nВидео: модель (объем VRAM)\r\nУдаленное управление\r\nOC: Версия операционной системы (Pro/не Pro)\r\nПорты: список имеющихся портов\r\nнеочевидное расположение серийного номера?','','',1,0,0,0,'2023-09-09 18:05:26','admin',NULL,NULL),(4,'net_router','Маршрутизатор','МРШ','Какое количествое каких типов портов?\r\nНаличие PoE (кол-во портов, макс бюджет)?\r\nСPU/RAM\r\nЛогин / пароль по умолч.\r\nнеочевидное расположение серийного номера?','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(5,'voip_gw','VoIP GW','ТЕЛ','Количество линий?\r\nУмеет провижнинг?\r\nУмеет отказоустойчивость?\r\nПитание POE/ БП (идет ли в комплекте)?\r\nМожно включать бриджом?\r\nЛогин / пароль по умолч.\r\nнеочевидное расположение серийного номера?','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(6,'mfu_bw_a3','Печатное оборуд.','ПРН','Принетер/МФУ, формат бумаги, цветной?\r\nСетевой?\r\nПоддержка двусторонней печати?\r\nСканирование с податчика?, Поддержка двустороннего сканирования?\r\nСканирование в SMB/FTP/Почту?\r\nДопустимая нагрузка по печати (стр/мес)?\r\nМодели картриджей (цвет, емкость)?\r\nЛогин/пароль по умолчанию?\r\nнеочевидное расположение серийного номера?','Сетевой путь','Полный сетевой путь к принтеру вместе с сервером печати, на котором опубликован. Например \\\\msk-fsrv-open\\HP_LJ2021_5',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(7,'net_switch','Коммутатор','КОМ','Управляемый?\r\nКакое количествое каких типов портов?\r\nНаличие PoE (кол-во портов, макс бюджет)?\r\nЛогин / пароль по умолч.\r\nнеочевидное расположение серийного номера?','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(8,'ups','ИБП','ИБП','Управляемый? \r\nЕмкость, макс мощность?\r\nПланируемое время при типовых нагрузках?\r\nВозможность установки доп бат. модулей?\r\nКакое количество каких типов розеток?\r\nМодели батарейных блоков / сборок / батарей?\r\nЛогин / пароль по умолч?\r\nнеочевидное расположение серийного номера?','','',0,0,1,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(9,'wifi_ap','WiFi Точка Доступа','ВФЙ','Поддерживаемые протоколы, частоты?\r\nРадиус действия (в помещ/ на улице)?\r\nУличной/внутренней установки?\r\nОбъединение в mesh?\r\nПитание (наличие БП / инжектора в комплекте)?\r\nЛогин / пароль по умолч.\r\nнеочевидное расположение серийного номера?','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(10,'dvr_cam','Камера видеонабл.','ВИД','Цифровая / аналоговая\r\nВнешняя / внутренняя / температурный режим\r\nРазрешение, угол обзора, запись аудио\r\nРежим день/ночь, ИК подсветка\r\nPTZ?\r\nСетевые настройки по умолч.','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(11,'wifi_router','WiFi router','ВФЙ','Поддерживаемые протоколы, частоты?\r\nРадиус действия (в помещ/ на улице)?\r\nУличной/внутренней установки?\r\nОбъединение в mesh?\r\nПитание (наличие БП / инжектора в комплекте)?\r\nЛогин / пароль по умолч.\r\nнеочевидное расположение серийного номера?','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(12,'nas','СХД','СХД','Количество контроллеров\r\nКоличество блоков питания\r\nРежим взаимодействия контроллеров\r\nКоличество и формат дисков в корзине\r\nПоддержка сжатия, поддержка дедупликации\r\nСетевые интерфейсы','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(13,'laptop','Ноутбук','БУК','Экран: матовый/глянец Диагональ IPS/TN @разрешение\r\nПроцессор: модель @частота (количество ядер)\r\nПамять: объем (тип)\r\nНМЖД: тип объем @rpm\r\nВидео: модель (объем VRAM)\r\nOC: Версия операционной системы (Pro/не Pro)\r\nПорты: список имеющихся портов\r\nнеочевидное расположение серийного номера?','','',1,0,0,0,'2023-09-13 16:11:42','admin',NULL,NULL),(14,'skud','СКУД','СКД','Тип оборудования: Турникет, Пилон, Считыватель\r\nНаличие сетевого интерфейса, тип','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(15,'scan','Сканер','СКАН','Сетевой/нет?\r\nПланшетный/протяжный?\r\nДвусторонний?\r\nСкнирование в FTP/SMB/SMTP?','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(16,'display','Монитор','МОН','Диагональ, разрешение\r\nПовортный/неповоротный экран\r\nМатрица, Покрытие\r\nПорты\r\nНаличие колонок\r\nВнешний БП?','','',0,0,0,1,'2023-08-28 13:41:17',NULL,NULL,NULL),(17,'web_camera','Веб-камера','ВЕБ','Максимальное разрешение 720p\r\nКоличество мегапикселей у камеры: 1.2\r\nТип фокусировки: постоянный фокус\r\nВстроенный микрофон: Монофонический\r\nДиапазон микрофона: До 1 м\r\nПоле обзора по диагонали: 60°\r\nУниверсальное крепление для ноутбука, ЖК-экрана или монитора\r\nПодключение кабелем длиной 1,5 м\r\n','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(18,'videoconf','Терминал ВКС','ВКС','Наличие, тип камеры\r\nНаличие, тип дисплея\r\n','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(19,'aio_pc','Моноблок',NULL,'Экран: матовый/глянец Диагональ IPS/TN @разрешение\r\nПроцессор: модель @частота (количество ядер)\r\nПамять: объем (тип)\r\nНМЖД: тип объем @rpm\r\nВидео: модель (объем VRAM)\r\nOC: Версия операционной системы (Pro/не Pro)\r\nПорты: список имеющихся портов\r\nнеочевидное расположение серийного номера?','','',1,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(20,'vid_reg','Видеорегистратор','РЕГ','Цифровая / аналоговая\r\nРазрешение, угол обзора, запись аудио\r\nРежим день/ночь, ИК подсветка\r\nPTZ?\r\nСетевые настройки по умолч.','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(21,'Racks','Шкафы и стойки','ШК','Высота (U)\r\nГабариты\r\nТип дверей (перф/стекло)','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(22,'Сист_контр.','Системы мониторинга','конт','За чем может следить\r\nКакие дополнительные датчики подключаются\r\nНаименование модулей расширения','Системы мониторинга серверной и ','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(23,'usb_k','USB концентратор','USB','Количество портов?','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL),(24,'tsd','Терминалы сбора данных','ТРМ','Операционная система\r\nТип, диагональ экрана\"@разрешение\r\nCPU\r\nRAM\r\nStorage\r\nWiFi\r\nАккумулятор\r\nКамеры','','',0,0,0,0,'2023-08-28 13:41:17',NULL,NULL,NULL);
/*!40000 ALTER TABLE `tech_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `techs`
--

DROP TABLE IF EXISTS `techs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `techs` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `num` varchar(16) DEFAULT NULL COMMENT 'Инвентарный номер',
  `inv_num` varchar(128) DEFAULT NULL COMMENT 'Бухгалтерский инвентарный номер',
  `model_id` int NOT NULL COMMENT 'Модель оборудования',
  `sn` varchar(128) DEFAULT NULL COMMENT 'Серийный номер',
  `arms_id` int DEFAULT NULL COMMENT 'Рабочее место',
  `places_id` int DEFAULT NULL COMMENT 'Помещение',
  `user_id` int DEFAULT NULL,
  `it_staff_id` int DEFAULT NULL,
  `ip` varchar(768) DEFAULT NULL,
  `mac` varchar(768) DEFAULT NULL,
  `state_id` int DEFAULT NULL COMMENT 'Состояние',
  `url` text COMMENT 'Ссылка',
  `comment` text COMMENT 'Комментарий',
  `history` text NOT NULL COMMENT 'Записная кинжка',
  `specs` text,
  `scans_id` int DEFAULT NULL,
  `departments_id` int DEFAULT NULL,
  `comp_id` int DEFAULT NULL,
  `installed_id` int DEFAULT NULL,
  `installed_pos` varchar(128) DEFAULT NULL,
  `head_id` int DEFAULT NULL,
  `responsible_id` int DEFAULT NULL,
  `hw` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `installed_pos_end` varchar(128) DEFAULT NULL,
  `installed_back` tinyint(1) DEFAULT '0',
  `full_length` tinyint(1) DEFAULT '0',
  `external_links` text,
  `partners_id` int DEFAULT NULL,
  `uid` varchar(16) DEFAULT NULL,
  `domain_id` int DEFAULT NULL,
  `hostname` varchar(128) DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  `management_service_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `num` (`num`),
  KEY `user_id` (`user_id`),
  KEY `it_staff_id` (`it_staff_id`),
  KEY `places_id` (`places_id`),
  KEY `arms_id` (`arms_id`),
  KEY `ip` (`ip`),
  KEY `state_id` (`state_id`),
  KEY `mac` (`mac`),
  KEY `idx-techs-departments_id` (`departments_id`),
  KEY `idx-techs-comp_id` (`comp_id`),
  KEY `idx-techs-installed_id` (`installed_id`),
  KEY `idx-techs-head_id` (`head_id`),
  KEY `idx-techs-responsible_id` (`responsible_id`),
  KEY `idx-techs-partners_id` (`partners_id`),
  KEY `idx-techs-uid` (`uid`),
  KEY `idx-techs-domain_id` (`domain_id`),
  KEY `idx-techs-hostname` (`hostname`),
  KEY `idx-techs-management_service_id` (`management_service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='Рабочие места';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `techs`
--

LOCK TABLES `techs` WRITE;
/*!40000 ALTER TABLE `techs` DISABLE KEYS */;
INSERT INTO `techs` VALUES (1,'МСК-СРВ-0001','',4,'F14F0E5B9AF009901',NULL,6,NULL,NULL,'','00155d3b793300155d3b793500155d3b793700155d3b7939',5,'','','','CPU: 2x E5260v4\r\nRAM: 8x 32GB\r\nNET: 4x 1Gbit\r\nFC: 2x 8Gbit',NULL,NULL,1,18,'36-37',NULL,NULL,'','2025-05-24 07:01:42','',0,1,'[]',1,'',NULL,NULL,NULL,NULL),(2,'МСК-СРВ-0002','0000072',4,'F14F0E5B9AF009917',NULL,6,NULL,NULL,'','00155d3b794100155d3b794300155d3b794500155d3b7947',5,'','','','CPU: 2x E5260v4\r\nRAM: 8x 32GB\r\nNET: 4x 1Gbit\r\nFC: 2x 8Gbit',NULL,NULL,2,18,'38-39',NULL,NULL,'','2025-05-24 07:01:42','',0,1,'[]',1,'',NULL,NULL,NULL,NULL),(3,'МСК-ПК-0001','',327,'AAX380016302',NULL,6,NULL,NULL,'','',4,'','','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(4,'МСК-ПК-0002','',327,'AAX380016308',NULL,6,NULL,NULL,'','',4,'','','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(5,'МСК-ПК-0003','0000039',327,'AAX380016309',NULL,3,2,6,'10.20.100.23','005056b4d780',5,'','поцарапан корпус','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,5,NULL,'',4,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(6,'МСК-ПК-0004','',327,'AAX380016311',NULL,3,4,6,'','024208d2b582',5,'','','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,6,NULL,'',4,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(7,'МСК-ПК-0005','',327,'AAX380016312',NULL,4,3,6,'','1a9cc268ceb7',5,'','','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,7,NULL,'',5,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(8,'ЧЕЛ-ПК-0001','',327,'AAX380016314',NULL,9,12,1,'','be6c0e44c1ac',5,'','','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,9,NULL,'',NULL,NULL,'[{\"type\":\"motherboard\",\"manufacturer\":\"Intel Corporation\",\"product\":\"NUC7JYB\",\"sn\":\"GEJY14900ACN\",\"cores\":null,\"capacity\":null,\"title\":\"Материнская плата\",\"manufacturer_id\":21,\"fingerprint\":\"motherboard|intel corporation|nuc7jyb|gejy14900acn\",\"hidden\":false,\"uid\":\"653b423a4a814\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null},{\"type\":\"processor\",\"manufacturer\":\"Intel(R)\",\"product\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\",\"sn\":\"\",\"cores\":\"2\",\"capacity\":null,\"title\":\"Процессор\",\"manufacturer_id\":21,\"fingerprint\":\"processor|intel(r)|intel(r) celeron(r) j4025 cpu @ 2.00ghz|\",\"hidden\":false,\"uid\":\"653b423a4a817\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null},{\"type\":\"memorybank\",\"manufacturer\":\"Kingston\",\"product\":\"8192MiB\",\"sn\":\"\",\"cores\":null,\"capacity\":8192,\"title\":\"Модуль памяти\",\"manufacturer_id\":341,\"fingerprint\":\"memorybank|kingston|8192mib|\",\"hidden\":false,\"uid\":\"653b423a4a818\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null},{\"type\":\"harddisk\",\"manufacturer\":\"KINGSTON\",\"product\":\"KINGSTON SA400S37240G 240GB\",\"sn\":\"\",\"cores\":null,\"capacity\":240,\"title\":\"Накопитель\",\"manufacturer_id\":341,\"fingerprint\":\"harddisk|kingston|kingston sa400s37240g 240gb|\",\"hidden\":false,\"uid\":\"653b423a4a81a\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null},{\"type\":\"videocard\",\"manufacturer\":\"Intel(R)\",\"product\":\"Intel(R) UHD Graphics 600 1024MiB\",\"sn\":\"\",\"cores\":null,\"capacity\":\"1024\",\"title\":\"Видеокарта\",\"manufacturer_id\":21,\"fingerprint\":\"videocard|intel(r)|intel(r) uhd graphics 600 1024mib|\",\"hidden\":false,\"uid\":\"653b423a4a81b\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null}]','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(9,'ЧЕЛ-ПК-0002','',327,'AAX380016315',NULL,9,13,NULL,'','ca817492a179',6,'','Иногда падает в BSOD','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,10,NULL,'',4,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(10,'ЧЕЛ-ПК-0003','',327,'AAX380016316',NULL,10,14,1,'','024475adf01a',5,'','','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,11,NULL,'',5,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(11,'ЧЕЛ-ПК-0004','',327,'AAX380016317',NULL,10,15,NULL,'','f245fcbb16aa',5,'','','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,12,NULL,'',NULL,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(12,'МСК-КОМ-0001','',151,'FCW1842A3AL10',NULL,6,NULL,NULL,'10.20.1.1\n10.20.7.1\n10.20.33.1\n10.20.40.1\n10.20.75.1\n10.20.100.1\n10.20.101.1','00241b9689d6',5,'','Ядро МСК','','',NULL,NULL,NULL,18,'43',NULL,NULL,'','2025-05-24 07:01:42','',1,0,'[]',1,'',NULL,NULL,NULL,NULL),(13,'МСК-КОМ-0002','',151,'FCW1842A3AL17',NULL,6,NULL,NULL,'','',4,'','','лежит в серверной в ЗИП','',NULL,NULL,NULL,NULL,'43',NULL,NULL,'','2025-05-24 07:01:42','',1,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(14,'ЧЕЛ-КОМ-0001','',151,'FCW1842A3AL21',NULL,8,NULL,NULL,'10.50.1.1','005706abaf8a',5,'','','','',NULL,NULL,NULL,19,'44',NULL,NULL,'','2025-05-24 07:01:42','',1,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(15,'МСК-МОН-0001','',193,'XKV0P9BA00YL',5,3,2,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(16,'МСК-МОН-0002','',193,'XKV0P9BA01YL',6,3,4,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(17,'МСК-ПК-0006','',327,'AAX3800163AA',NULL,4,7,6,'','024231533ef0',7,'','Пролила кофе','','SSD: 240GB\r\nRAM: 8GB',NULL,NULL,8,NULL,'',5,NULL,'','2025-05-24 07:01:42','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(18,'МСК-ШК-0001','',309,'',NULL,6,NULL,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:42','',0,0,'{\"rack-labels\":[{\"pos\":\"47\",\"back\":\"1\",\"label\":\"* светильник *\"},{\"pos\":\"42\",\"back\":\"1\",\"label\":\"- каб. органайзер -\"},{\"pos\":\"46\",\"back\":\"1\",\"label\":\"ПП 48 портов\"},{\"pos\":\"43\",\"back\":\"1\",\"label\":\" - каб. органайзер -\"},{\"pos\":\"26\",\"back\":\"\",\"label\":\"KVM консоль\"},{\"pos\":\"41\",\"back\":\"1\",\"label\":\"ПП 48 портов\"},{\"pos\":\"45\",\"back\":\"1\",\"label\":\" - каб. органайзер -\"},{\"pos\":\"24\",\"back\":\"1\",\"label\":\"Блок розеток\"},{\"pos\":\"22\",\"back\":\"1\",\"label\":\"Блок розеток\"}]}',1,'',NULL,NULL,NULL,NULL),(19,'ЧЕЛ-ШК-0001','',309,'',NULL,8,NULL,1,'','',NULL,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:42','',0,0,'{\"rack-labels\":[{\"pos\":\"47\",\"back\":\"1\",\"label\":\"* светильник *\"},{\"pos\":\"46\",\"back\":\"1\",\"label\":\"ПП 48 портов\"},{\"pos\":\"45\",\"back\":\"1\",\"label\":\" - каб. органайзер -\"},{\"pos\":\"42\",\"back\":\"1\",\"label\":\" - каб. органайзер -\"},{\"pos\":\"41\",\"back\":\"1\",\"label\":\"ПП 48 портов\"},{\"pos\":\"24\",\"back\":\"\",\"label\":\"KVM консоль\"}]}',NULL,'',NULL,NULL,NULL,NULL),(20,'МСК-ИБП-0001','',310,'N06F10KM3000EF',NULL,6,NULL,6,'','',5,'','','','',NULL,NULL,NULL,18,'3-7',NULL,NULL,'','2025-05-24 07:01:43','3-7,9-10',0,1,'[]',NULL,'',NULL,NULL,NULL,NULL),(21,'ЧЕЛ-ИБП-0001','',310,'N06F10KM300011',NULL,8,NULL,1,'','',5,'','','','',NULL,NULL,NULL,19,'3-7',NULL,NULL,'','2025-05-24 07:01:43','3-7,9-10',0,1,'[]',NULL,'',NULL,NULL,NULL,NULL),(22,'МСК-КОМ-0003','',194,'P1CV189071603',NULL,6,NULL,6,'10.20.1.2','',5,'','','','',NULL,NULL,NULL,18,'44',NULL,NULL,'','2025-05-24 07:01:43','',1,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(23,'ЧЕЛ-КОМ-0002','',194,'P1CV186021208',NULL,8,NULL,1,'10.50.1.2','00c3fee7a5ed',5,'','','','',NULL,NULL,NULL,19,'43',NULL,NULL,'','2025-05-24 07:01:43','',1,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(24,'ЧЕЛ-БУК-0001','',241,'FDO1628R0V7',NULL,11,10,10,'','dea9a5774774',1,'','','','',NULL,NULL,13,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',2,'',NULL,NULL,NULL,NULL),(25,'ЧЕЛ-БУК-0002','',241,'FDO1628R0V1',NULL,11,9,9,'','de3907302a54',1,'','','','',NULL,NULL,14,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(26,'МСК-МОН-0003','',193,'XKV0P9BA06YL',7,4,3,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(27,'МСК-МОН-0004','',193,'XKV0P9BA10YL',17,4,7,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(28,'ЧЕЛ-МОН-0001','',193,'XKV0P9BA15YL',8,9,12,1,'','',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(29,'ЧЕЛ-МОН-0002','',193,'XKV0P9BA1AYL',9,9,13,NULL,'','',NULL,'','','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(30,'ЧЕЛ-МОН-0003','',193,'XKV0P9BA1fYL',10,10,14,1,'','',5,'','','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(31,'ЧЕЛ-МОН-0004','',193,'XKV0P9BA21YL',11,10,15,NULL,'','',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(32,'ЧЕЛ-МОН-0005','',193,'XKV0P9BA25YL',NULL,8,NULL,1,'','',8,'','Разбили','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(33,'ЧЕЛ-СРВ-0001','',4,'F14F0E5B9AF009933',NULL,8,NULL,1,'10.50.1.10','02e5027e90fa02e5027e90fb02e5027e90fc02e5027e90fd',5,'','','','CPU: 2x E5260v4\r\nRAM: 8x 32GB\r\nNET: 4x 1Gbit\r\nFC: 2x 8Gbit',NULL,NULL,22,19,'37-38',NULL,NULL,'','2025-05-24 07:01:43','',0,1,'[]',NULL,'',NULL,NULL,NULL,NULL),(34,'ЧЕЛ-СРВ-0002','',4,'F14F0E5B9AF009935',NULL,8,NULL,1,'10.50.1.11','72e6bbae08ca72e6bbae08cb72e6bbae08cc72e6bbae08cd',5,'','','','CPU: 2x E5260v4\r\nRAM: 8x 32GB\r\nNET: 4x 1Gbit\r\nFC: 2x 8Gbit',NULL,NULL,23,19,'35-36',NULL,NULL,'','2025-05-24 07:01:43','',0,1,'[]',NULL,'',NULL,NULL,NULL,NULL),(36,'МСК-РЕГ-0001','',389,'1901000885',NULL,2,NULL,NULL,'','',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(37,'МСК-ВИД-0001','',38,'0415401793',NULL,3,NULL,NULL,'10.20.33.10','186882920d99',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(38,'МСК-ВИД-0002','',38,'0415401794',NULL,4,NULL,NULL,'10.20.33.12','186882920d9a',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(39,'МСК-ВИД-0003','',38,'0415401790',NULL,2,NULL,NULL,'10.20.33.13','186882920d9b',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(40,'МСК-ВИД-0004','',38,'',NULL,6,NULL,NULL,'10.20.33.15','186882920d90',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(41,'МСК-ПРН-0001','',53,'VCF8955916',NULL,3,NULL,NULL,'10.20.40.10','0017c87697b1',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(42,'МСК-ПРН-0002','',53,'VCF8955908',NULL,4,NULL,NULL,'10.20.40.12','0017c877a410',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(43,'ЧЕЛ-ПРН-0001','',53,'VCF8953216',NULL,9,NULL,NULL,'10.50.40.10','0017c8778faf',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(44,'ЧЕЛ-ПРН-0002','',53,'VCF8953219',NULL,10,NULL,NULL,'10.50.40.12','0017c87ab169',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(45,'МСК-СХД-0001','',390,'',NULL,6,NULL,NULL,'10.20.1.100\n10.20.1.101','0003c0a42bf90003c0a42bfb',5,'','','','',298,NULL,NULL,18,'33-34',NULL,NULL,'','2025-05-24 07:01:43','',0,1,'[]',NULL,'',NULL,NULL,NULL,NULL),(46,'МСК-ТЕЛ-0001','',138,'2104SN29201',5,3,2,6,'10.20.7.11','001c9295dd7f',NULL,'','1123','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(47,'МСК-ТЕЛ-0002','',138,'2104SN29202',6,3,4,6,'10.20.7.12','00478a632ad1',5,'','1122','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(48,'МСК-ТЕЛ-0003','',138,'2104SN29203',7,4,3,6,'10.20.7.13','00f93d7937b5',NULL,'','1201','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(49,'МСК-ТЕЛ-0004','',138,'2104SN29204',17,4,7,6,'10.20.7.14','000df36c941a',5,'','1202','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(50,'ЧЕЛ-ТЕЛ-0001','Тел. Yealink-2021-12',138,'2104SN29206',8,9,12,1,'10.50.7.11','007663e7f5e2',5,'','3021','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',1,'',NULL,NULL,NULL,NULL),(51,'ЧЕЛ-ТЕЛ-0002','',138,'2104SN29207',9,9,13,NULL,'10.50.7.12','00fdbb5e11fb',5,'','3024','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(52,'ЧЕЛ-ТЕЛ-0003','',138,'2104SN29208',10,10,14,1,'10.50.7.14','0005c854d8c8',5,'','3011','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(53,'ЧЕЛ-ТЕЛ-0004','',138,'2104SN29209',11,10,15,NULL,'10.50.7.16','00c1a95ed736',5,'','3014','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(54,'ЧЕЛ-ТЕЛ-0005','',138,'2104SN2920A',24,11,10,10,'10.50.7.15','00f0c5c515b5',5,'','3044','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(55,'ЧЕЛ-ТЕЛ-0006','',138,'2104SN2920B',25,11,9,9,'10.50.7.18','00adb2ddc53d',5,'','3041','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(56,'МСК-ИБП-0002','',69,'3B1629X15430',5,3,2,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:43','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(57,'МСК-ИБП-0003','',69,'3B1629X15432',6,3,4,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:44','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(58,'МСК-ИБП-0004','',69,'3B1629X15433',7,4,3,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:44','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(59,'МСК-ИБП-0005','',69,'3B1629X15436',17,4,7,6,'','',5,'','','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:44','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(60,'ЧЕЛ-ИБП-0002','',69,'3B1629X15437',8,9,12,1,'','',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:44','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(61,'ЧЕЛ-ИБП-0003','',69,'3B1629X15438',9,9,13,NULL,'','',5,'','','','',NULL,NULL,NULL,NULL,'',4,NULL,'','2025-05-24 07:01:44','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(62,'ЧЕЛ-ИБП-0004','',69,'3B1629X15439',10,10,14,1,'','',5,'','','','',NULL,NULL,NULL,NULL,'',5,NULL,'','2025-05-24 07:01:44','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL),(63,'ЧЕЛ-ИБП-0005','',69,'3B1629X1543A',11,10,15,NULL,'','',5,'','','','',NULL,NULL,NULL,NULL,'',NULL,NULL,'','2025-05-24 07:01:44','',0,0,'[]',NULL,'',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `techs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `techs_history`
--

DROP TABLE IF EXISTS `techs_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `techs_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `num` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inv_num` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sn` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uid` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hostname` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `domain_id` int DEFAULT NULL,
  `model_id` int DEFAULT NULL,
  `arms_id` int DEFAULT NULL,
  `installed_id` int DEFAULT NULL,
  `places_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `head_id` int DEFAULT NULL,
  `responsible_id` int DEFAULT NULL,
  `it_staff_id` int DEFAULT NULL,
  `state_id` int DEFAULT NULL,
  `scans_id` int DEFAULT NULL,
  `departments_id` int DEFAULT NULL,
  `comp_id` int DEFAULT NULL,
  `partners_id` int DEFAULT NULL,
  `ip` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mac` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `installed_pos` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `installed_pos_end` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `specs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hw` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `external_links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `installed_back` tinyint(1) DEFAULT NULL,
  `full_length` tinyint(1) DEFAULT NULL,
  `contracts_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `services_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lic_items_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lic_keys_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lic_groups_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `maintenance_reqs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `materials_usages_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `acls_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `maintenance_jobs_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `archived` tinyint(1) DEFAULT NULL,
  `management_service_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `techs_history-master_id` (`master_id`),
  KEY `techs_history-updated_at` (`updated_at`),
  KEY `techs_history-updated_by` (`updated_by`),
  KEY `idx-techs_history-management_service_id` (`management_service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `techs_history`
--

LOCK TABLES `techs_history` WRITE;
/*!40000 ALTER TABLE `techs_history` DISABLE KEYS */;
INSERT INTO `techs_history` VALUES (1,18,'2025-05-14 06:51:38',NULL,NULL,'num,model_id,places_id,it_staff_id,state_id,partners_id,external_links,contracts_ids','МСК-ШК-0001',NULL,NULL,NULL,NULL,NULL,309,NULL,NULL,6,NULL,NULL,NULL,6,5,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"rack-labels\":[{\"pos\":\"47\",\"back\":\"1\",\"label\":\"* светильник *\"},{\"pos\":\"42\",\"back\":\"1\",\"label\":\"- каб. органайзер -\"},{\"pos\":\"46\",\"back\":\"1\",\"label\":\"ПП 48 портов\"},{\"pos\":\"43\",\"back\":\"1\",\"label\":\" - каб. органайзер -\"},{\"pos\":\"26\",\"back\":\"\",\"label\":\"KVM консоль\"},{\"pos\":\"41\",\"back\":\"1\",\"label\":\"ПП 48 портов\"},{\"pos\":\"45\",\"back\":\"1\",\"label\":\" - каб. органайзер -\"},{\"pos\":\"24\",\"back\":\"1\",\"label\":\"Блок розеток\"},{\"pos\":\"22\",\"back\":\"1\",\"label\":\"Блок розеток\"}]}',0,0,'7',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(2,19,'2025-05-14 06:51:38',NULL,NULL,'num,model_id,places_id,it_staff_id,external_links,contracts_ids','ЧЕЛ-ШК-0001',NULL,NULL,NULL,NULL,NULL,309,NULL,NULL,8,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"rack-labels\":[{\"pos\":\"47\",\"back\":\"1\",\"label\":\"* светильник *\"},{\"pos\":\"46\",\"back\":\"1\",\"label\":\"ПП 48 портов\"},{\"pos\":\"45\",\"back\":\"1\",\"label\":\" - каб. органайзер -\"},{\"pos\":\"42\",\"back\":\"1\",\"label\":\" - каб. органайзер -\"},{\"pos\":\"41\",\"back\":\"1\",\"label\":\"ПП 48 портов\"},{\"pos\":\"24\",\"back\":\"\",\"label\":\"KVM консоль\"}]}',0,0,'7',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(3,6,'2025-05-14 09:15:53',NULL,NULL,'num,sn,model_id,places_id,user_id,head_id,it_staff_id,state_id,comp_id,partners_id,mac,specs,contracts_ids,lic_keys_ids,materials_usages_ids','МСК-ПК-0004',NULL,'AAX380016311',NULL,NULL,NULL,327,NULL,NULL,3,4,4,NULL,6,5,NULL,NULL,6,1,NULL,'024208d2b582',NULL,NULL,NULL,NULL,NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,NULL,0,0,'4',NULL,NULL,'8',NULL,NULL,'1',NULL,NULL,0,NULL),(4,1,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,installed_id,places_id,state_id,comp_id,partners_id,mac,installed_pos,specs,external_links,full_length,contracts_ids,lic_items_ids','МСК-СРВ-0001',NULL,'F14F0E5B9AF009901',NULL,NULL,NULL,4,NULL,18,6,NULL,NULL,NULL,NULL,5,NULL,NULL,1,1,NULL,'00155d3b793300155d3b793500155d3b793700155d3b7939','36-37',NULL,NULL,NULL,NULL,'CPU: 2x E5260v4\r\nRAM: 8x 32GB\r\nNET: 4x 1Gbit\r\nFC: 2x 8Gbit',NULL,'[]',0,1,'3',NULL,'2',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(5,2,'2025-05-23 11:27:09',NULL,NULL,'num,inv_num,sn,model_id,installed_id,places_id,state_id,comp_id,partners_id,mac,installed_pos,specs,external_links,full_length,contracts_ids,lic_items_ids','МСК-СРВ-0002','0000072','F14F0E5B9AF009917',NULL,NULL,NULL,4,NULL,18,6,NULL,NULL,NULL,NULL,5,NULL,NULL,2,1,NULL,'00155d3b794100155d3b794300155d3b794500155d3b7947','38-39',NULL,NULL,NULL,NULL,'CPU: 2x E5260v4\r\nRAM: 8x 32GB\r\nNET: 4x 1Gbit\r\nFC: 2x 8Gbit',NULL,'[]',0,1,'3',NULL,'2',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(6,3,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,places_id,state_id,partners_id,specs,external_links,contracts_ids,lic_keys_ids','МСК-ПК-0001',NULL,'AAX380016302',NULL,NULL,NULL,327,NULL,NULL,6,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,0,NULL),(7,4,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,places_id,state_id,partners_id,specs,external_links,contracts_ids,lic_keys_ids','МСК-ПК-0002',NULL,'AAX380016308',NULL,NULL,NULL,327,NULL,NULL,6,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'2',NULL,NULL,NULL,NULL,NULL,0,NULL),(8,5,'2025-05-23 11:27:09',NULL,NULL,'num,inv_num,sn,model_id,places_id,user_id,head_id,it_staff_id,state_id,comp_id,partners_id,ip,mac,comment,specs,external_links,contracts_ids,lic_keys_ids','МСК-ПК-0003','0000039','AAX380016309',NULL,NULL,NULL,327,NULL,NULL,3,2,4,NULL,6,5,NULL,NULL,5,1,'10.20.100.23','005056b4d780',NULL,NULL,NULL,'поцарапан корпус',NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'7',NULL,NULL,NULL,NULL,NULL,0,NULL),(9,6,'2025-05-23 11:27:09',NULL,NULL,'external_links','МСК-ПК-0004',NULL,'AAX380016311',NULL,NULL,NULL,327,NULL,NULL,3,4,4,NULL,6,5,NULL,NULL,6,1,NULL,'024208d2b582',NULL,NULL,NULL,NULL,NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'8',NULL,NULL,'1',NULL,NULL,0,NULL),(10,7,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,places_id,user_id,head_id,it_staff_id,state_id,comp_id,partners_id,mac,specs,external_links,contracts_ids,lic_keys_ids','МСК-ПК-0005',NULL,'AAX380016312',NULL,NULL,NULL,327,NULL,NULL,4,3,5,NULL,6,5,NULL,NULL,7,1,NULL,'1a9cc268ceb7',NULL,NULL,NULL,NULL,NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'10',NULL,NULL,NULL,NULL,NULL,0,NULL),(11,8,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,places_id,user_id,it_staff_id,state_id,comp_id,partners_id,mac,specs,hw,external_links,contracts_ids,lic_keys_ids','ЧЕЛ-ПК-0001',NULL,'AAX380016314',NULL,NULL,NULL,327,NULL,NULL,9,12,NULL,NULL,1,5,NULL,NULL,9,1,NULL,'be6c0e44c1ac',NULL,NULL,NULL,NULL,NULL,'SSD: 240GB\r\nRAM: 8GB','[{\"type\":\"motherboard\",\"manufacturer\":\"Intel Corporation\",\"product\":\"NUC7JYB\",\"sn\":\"GEJY14900ACN\",\"cores\":null,\"capacity\":null,\"title\":\"Материнская плата\",\"manufacturer_id\":21,\"fingerprint\":\"motherboard|intel corporation|nuc7jyb|gejy14900acn\",\"hidden\":false,\"uid\":\"653b423a4a814\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null},{\"type\":\"processor\",\"manufacturer\":\"Intel(R)\",\"product\":\"Intel(R) Celeron(R) J4025 CPU @ 2.00GHz\",\"sn\":\"\",\"cores\":\"2\",\"capacity\":null,\"title\":\"Процессор\",\"manufacturer_id\":21,\"fingerprint\":\"processor|intel(r)|intel(r) celeron(r) j4025 cpu @ 2.00ghz|\",\"hidden\":false,\"uid\":\"653b423a4a817\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null},{\"type\":\"memorybank\",\"manufacturer\":\"Kingston\",\"product\":\"8192MiB\",\"sn\":\"\",\"cores\":null,\"capacity\":8192,\"title\":\"Модуль памяти\",\"manufacturer_id\":341,\"fingerprint\":\"memorybank|kingston|8192mib|\",\"hidden\":false,\"uid\":\"653b423a4a818\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null},{\"type\":\"harddisk\",\"manufacturer\":\"KINGSTON\",\"product\":\"KINGSTON SA400S37240G 240GB\",\"sn\":\"\",\"cores\":null,\"capacity\":240,\"title\":\"Накопитель\",\"manufacturer_id\":341,\"fingerprint\":\"harddisk|kingston|kingston sa400s37240g 240gb|\",\"hidden\":false,\"uid\":\"653b423a4a81a\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null},{\"type\":\"videocard\",\"manufacturer\":\"Intel(R)\",\"product\":\"Intel(R) UHD Graphics 600 1024MiB\",\"sn\":\"\",\"cores\":null,\"capacity\":\"1024\",\"title\":\"Видеокарта\",\"manufacturer_id\":21,\"fingerprint\":\"videocard|intel(r)|intel(r) uhd graphics 600 1024mib|\",\"hidden\":false,\"uid\":\"653b423a4a81b\",\"inv_num\":null,\"manual_name\":null,\"manual_sn\":null}]','[]',0,0,'4',NULL,NULL,'3',NULL,NULL,NULL,NULL,NULL,0,NULL),(12,9,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,places_id,user_id,head_id,state_id,comp_id,partners_id,mac,comment,specs,external_links,contracts_ids,lic_keys_ids','ЧЕЛ-ПК-0002',NULL,'AAX380016315',NULL,NULL,NULL,327,NULL,NULL,9,13,4,NULL,NULL,6,NULL,NULL,10,1,NULL,'ca817492a179',NULL,NULL,NULL,'Иногда падает в BSOD',NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'5',NULL,NULL,NULL,NULL,NULL,0,NULL),(13,10,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,places_id,user_id,head_id,it_staff_id,state_id,comp_id,partners_id,mac,specs,external_links,contracts_ids,lic_keys_ids','ЧЕЛ-ПК-0003',NULL,'AAX380016316',NULL,NULL,NULL,327,NULL,NULL,10,14,5,NULL,1,5,NULL,NULL,11,1,NULL,'024475adf01a',NULL,NULL,NULL,NULL,NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'4',NULL,NULL,NULL,NULL,NULL,0,NULL),(14,11,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,places_id,user_id,state_id,comp_id,partners_id,mac,specs,external_links,contracts_ids,lic_keys_ids','ЧЕЛ-ПК-0004',NULL,'AAX380016317',NULL,NULL,NULL,327,NULL,NULL,10,15,NULL,NULL,NULL,5,NULL,NULL,12,1,NULL,'f245fcbb16aa',NULL,NULL,NULL,NULL,NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'6',NULL,NULL,NULL,NULL,NULL,0,NULL),(15,12,'2025-05-23 11:27:09',NULL,NULL,'num,sn,model_id,installed_id,places_id,state_id,partners_id,ip,mac,installed_pos,comment,external_links,installed_back,contracts_ids,services_ids','МСК-КОМ-0001',NULL,'FCW1842A3AL10',NULL,NULL,NULL,151,NULL,18,6,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,1,'10.20.1.1\n10.20.7.1\n10.20.33.1\n10.20.40.1\n10.20.75.1\n10.20.100.1\n10.20.101.1','00241b9689d6','43',NULL,NULL,'Ядро МСК',NULL,NULL,NULL,'[]',1,0,'4','4,6,7',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(16,13,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,places_id,state_id,installed_pos,history,external_links,installed_back,contracts_ids','МСК-КОМ-0002',NULL,'FCW1842A3AL17',NULL,NULL,NULL,151,NULL,NULL,6,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,'43',NULL,NULL,NULL,'лежит в серверной в ЗИП',NULL,NULL,'[]',1,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(17,14,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,installed_id,places_id,state_id,ip,mac,installed_pos,external_links,installed_back,contracts_ids,services_ids','ЧЕЛ-КОМ-0001',NULL,'FCW1842A3AL21',NULL,NULL,NULL,151,NULL,19,8,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.50.1.1','005706abaf8a','44',NULL,NULL,NULL,NULL,NULL,NULL,'[]',1,0,'4','4,6,7,15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(18,15,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,partners_id,external_links,contracts_ids','МСК-МОН-0001',NULL,'XKV0P9BA00YL',NULL,NULL,NULL,193,5,NULL,3,2,4,NULL,6,5,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(19,16,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,partners_id,external_links,contracts_ids','МСК-МОН-0002',NULL,'XKV0P9BA01YL',NULL,NULL,NULL,193,6,NULL,3,4,4,NULL,6,5,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(20,17,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,places_id,user_id,head_id,it_staff_id,state_id,comp_id,partners_id,mac,comment,specs,external_links,contracts_ids,lic_keys_ids','МСК-ПК-0006',NULL,'AAX3800163AA',NULL,NULL,NULL,327,NULL,NULL,4,7,5,NULL,6,7,NULL,NULL,8,1,NULL,'024231533ef0',NULL,NULL,NULL,'Пролила кофе',NULL,'SSD: 240GB\r\nRAM: 8GB',NULL,'[]',0,0,'4',NULL,NULL,'9',NULL,NULL,NULL,NULL,NULL,0,NULL),(21,20,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,installed_id,places_id,it_staff_id,state_id,installed_pos,installed_pos_end,external_links,full_length','МСК-ИБП-0001',NULL,'N06F10KM3000EF',NULL,NULL,NULL,310,NULL,18,6,NULL,NULL,NULL,6,5,NULL,NULL,NULL,NULL,NULL,NULL,'3-7','3-7,9-10',NULL,NULL,NULL,NULL,NULL,'[]',0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(22,21,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,installed_id,places_id,it_staff_id,state_id,installed_pos,installed_pos_end,external_links,full_length','ЧЕЛ-ИБП-0001',NULL,'N06F10KM300011',NULL,NULL,NULL,310,NULL,19,8,NULL,NULL,NULL,1,5,NULL,NULL,NULL,NULL,NULL,NULL,'3-7','3-7,9-10',NULL,NULL,NULL,NULL,NULL,'[]',0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(23,22,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,installed_id,places_id,it_staff_id,state_id,ip,installed_pos,external_links,installed_back,services_ids','МСК-КОМ-0003',NULL,'P1CV189071603',NULL,NULL,NULL,194,NULL,18,6,NULL,NULL,NULL,6,5,NULL,NULL,NULL,NULL,'10.20.1.2',NULL,'44',NULL,NULL,NULL,NULL,NULL,NULL,'[]',1,0,NULL,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(24,23,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,installed_id,places_id,it_staff_id,state_id,ip,mac,installed_pos,external_links,installed_back,services_ids','ЧЕЛ-КОМ-0002',NULL,'P1CV186021208',NULL,NULL,NULL,194,NULL,19,8,NULL,NULL,NULL,1,5,NULL,NULL,NULL,NULL,'10.50.1.2','00c3fee7a5ed','43',NULL,NULL,NULL,NULL,NULL,NULL,'[]',1,0,NULL,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(25,24,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,places_id,user_id,head_id,it_staff_id,state_id,comp_id,partners_id,mac,external_links','ЧЕЛ-БУК-0001',NULL,'FDO1628R0V7',NULL,NULL,NULL,241,NULL,NULL,11,10,5,NULL,10,1,NULL,NULL,13,2,NULL,'dea9a5774774',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(26,25,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,places_id,user_id,head_id,it_staff_id,state_id,comp_id,mac,external_links','ЧЕЛ-БУК-0002',NULL,'FDO1628R0V1',NULL,NULL,NULL,241,NULL,NULL,11,9,5,NULL,9,1,NULL,NULL,14,NULL,NULL,'de3907302a54',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(27,26,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,external_links,contracts_ids','МСК-МОН-0003',NULL,'XKV0P9BA06YL',NULL,NULL,NULL,193,7,NULL,4,3,5,NULL,6,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(28,27,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,external_links,contracts_ids','МСК-МОН-0004',NULL,'XKV0P9BA10YL',NULL,NULL,NULL,193,17,NULL,4,7,5,NULL,6,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(29,28,'2025-05-23 11:27:10',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,it_staff_id,state_id,external_links,contracts_ids','ЧЕЛ-МОН-0001',NULL,'XKV0P9BA15YL',NULL,NULL,NULL,193,8,NULL,9,12,NULL,NULL,1,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(30,29,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,external_links,contracts_ids','ЧЕЛ-МОН-0002',NULL,'XKV0P9BA1AYL',NULL,NULL,NULL,193,9,NULL,9,13,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(31,30,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,external_links,contracts_ids','ЧЕЛ-МОН-0003',NULL,'XKV0P9BA1fYL',NULL,NULL,NULL,193,10,NULL,10,14,5,NULL,1,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(32,31,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,state_id,external_links,contracts_ids','ЧЕЛ-МОН-0004',NULL,'XKV0P9BA21YL',NULL,NULL,NULL,193,11,NULL,10,15,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(33,32,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,it_staff_id,state_id,comment,external_links,contracts_ids,archived','ЧЕЛ-МОН-0005',NULL,'XKV0P9BA25YL',NULL,NULL,NULL,193,NULL,NULL,8,NULL,NULL,NULL,1,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Разбили',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL),(34,33,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,installed_id,places_id,it_staff_id,state_id,comp_id,ip,mac,installed_pos,specs,external_links,full_length','ЧЕЛ-СРВ-0001',NULL,'F14F0E5B9AF009933',NULL,NULL,NULL,4,NULL,19,8,NULL,NULL,NULL,1,5,NULL,NULL,22,NULL,'10.50.1.10','02e5027e90fa02e5027e90fb02e5027e90fc02e5027e90fd','37-38',NULL,NULL,NULL,NULL,'CPU: 2x E5260v4\r\nRAM: 8x 32GB\r\nNET: 4x 1Gbit\r\nFC: 2x 8Gbit',NULL,'[]',0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(35,34,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,installed_id,places_id,it_staff_id,state_id,comp_id,ip,mac,installed_pos,specs,external_links,full_length','ЧЕЛ-СРВ-0002',NULL,'F14F0E5B9AF009935',NULL,NULL,NULL,4,NULL,19,8,NULL,NULL,NULL,1,5,NULL,NULL,23,NULL,'10.50.1.11','72e6bbae08ca72e6bbae08cb72e6bbae08cc72e6bbae08cd','35-36',NULL,NULL,NULL,NULL,'CPU: 2x E5260v4\r\nRAM: 8x 32GB\r\nNET: 4x 1Gbit\r\nFC: 2x 8Gbit',NULL,'[]',0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(36,36,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,state_id,external_links,services_ids','МСК-РЕГ-0001',NULL,'1901000885',NULL,NULL,NULL,389,NULL,NULL,2,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,NULL,'21',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(37,37,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,state_id,ip,mac,external_links,services_ids','МСК-ВИД-0001',NULL,'0415401793',NULL,NULL,NULL,38,NULL,NULL,3,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.20.33.10','186882920d99',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,NULL,'21',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(38,38,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,state_id,ip,mac,external_links,services_ids','МСК-ВИД-0002',NULL,'0415401794',NULL,NULL,NULL,38,NULL,NULL,4,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.20.33.12','186882920d9a',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,NULL,'21',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(39,39,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,state_id,ip,mac,external_links,services_ids','МСК-ВИД-0003',NULL,'0415401790',NULL,NULL,NULL,38,NULL,NULL,2,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.20.33.13','186882920d9b',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,NULL,'21',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(40,40,'2025-05-23 11:27:11',NULL,NULL,'num,model_id,places_id,state_id,ip,mac,external_links,services_ids','МСК-ВИД-0004',NULL,NULL,NULL,NULL,NULL,38,NULL,NULL,6,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.20.33.15','186882920d90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,NULL,'21',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(41,41,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,state_id,ip,mac,external_links,contracts_ids','МСК-ПРН-0001',NULL,'VCF8955916',NULL,NULL,NULL,53,NULL,NULL,3,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.20.40.10','0017c87697b1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(42,42,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,state_id,ip,mac,external_links,contracts_ids','МСК-ПРН-0002',NULL,'VCF8955908',NULL,NULL,NULL,53,NULL,NULL,4,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.20.40.12','0017c877a410',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(43,43,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,state_id,ip,mac,external_links,contracts_ids','ЧЕЛ-ПРН-0001',NULL,'VCF8953216',NULL,NULL,NULL,53,NULL,NULL,9,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.50.40.10','0017c8778faf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(44,44,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,places_id,state_id,ip,mac,external_links,contracts_ids','ЧЕЛ-ПРН-0002',NULL,'VCF8953219',NULL,NULL,NULL,53,NULL,NULL,10,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.50.40.12','0017c87ab169',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(45,45,'2025-05-23 11:27:11',NULL,NULL,'num,model_id,installed_id,places_id,state_id,scans_id,ip,mac,installed_pos,external_links,full_length','МСК-СХД-0001',NULL,NULL,NULL,NULL,NULL,390,NULL,18,6,NULL,NULL,NULL,NULL,5,298,NULL,NULL,NULL,'10.20.1.100\n10.20.1.101','0003c0a42bf90003c0a42bfb','33-34',NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(46,46,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,ip,mac,comment,external_links,contracts_ids','МСК-ТЕЛ-0001',NULL,'2104SN29201',NULL,NULL,NULL,138,5,NULL,3,2,4,NULL,6,NULL,NULL,NULL,NULL,NULL,'10.20.7.11','001c9295dd7f',NULL,NULL,NULL,'1123',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(47,47,'2025-05-23 11:27:11',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,ip,mac,comment,external_links,contracts_ids','МСК-ТЕЛ-0002',NULL,'2104SN29202',NULL,NULL,NULL,138,6,NULL,3,4,4,NULL,6,5,NULL,NULL,NULL,NULL,'10.20.7.12','00478a632ad1',NULL,NULL,NULL,'1122',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(48,48,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,ip,mac,comment,external_links,contracts_ids','МСК-ТЕЛ-0003',NULL,'2104SN29203',NULL,NULL,NULL,138,7,NULL,4,3,5,NULL,6,NULL,NULL,NULL,NULL,NULL,'10.20.7.13','00f93d7937b5',NULL,NULL,NULL,'1201',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(49,49,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,ip,mac,comment,external_links,contracts_ids','МСК-ТЕЛ-0004',NULL,'2104SN29204',NULL,NULL,NULL,138,17,NULL,4,7,5,NULL,6,5,NULL,NULL,NULL,NULL,'10.20.7.14','000df36c941a',NULL,NULL,NULL,'1202',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(50,50,'2025-05-23 11:27:12',NULL,NULL,'num,inv_num,sn,model_id,arms_id,places_id,user_id,it_staff_id,state_id,partners_id,ip,mac,comment,external_links,contracts_ids','ЧЕЛ-ТЕЛ-0001','Тел. Yealink-2021-12','2104SN29206',NULL,NULL,NULL,138,8,NULL,9,12,NULL,NULL,1,5,NULL,NULL,NULL,1,'10.50.7.11','007663e7f5e2',NULL,NULL,NULL,'3021',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(51,51,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,state_id,ip,mac,comment,external_links,contracts_ids','ЧЕЛ-ТЕЛ-0002',NULL,'2104SN29207',NULL,NULL,NULL,138,9,NULL,9,13,4,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.50.7.12','00fdbb5e11fb',NULL,NULL,NULL,'3024',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(52,52,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,ip,mac,comment,external_links,contracts_ids','ЧЕЛ-ТЕЛ-0003',NULL,'2104SN29208',NULL,NULL,NULL,138,10,NULL,10,14,5,NULL,1,5,NULL,NULL,NULL,NULL,'10.50.7.14','0005c854d8c8',NULL,NULL,NULL,'3011',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(53,53,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,state_id,ip,mac,comment,external_links,contracts_ids','ЧЕЛ-ТЕЛ-0004',NULL,'2104SN29209',NULL,NULL,NULL,138,11,NULL,10,15,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.50.7.16','00c1a95ed736',NULL,NULL,NULL,'3014',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(54,54,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,ip,mac,comment,external_links,contracts_ids','ЧЕЛ-ТЕЛ-0005',NULL,'2104SN2920A',NULL,NULL,NULL,138,24,NULL,11,10,5,NULL,10,5,NULL,NULL,NULL,NULL,'10.50.7.15','00f0c5c515b5',NULL,NULL,NULL,'3044',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(55,55,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,ip,mac,comment,external_links,contracts_ids','ЧЕЛ-ТЕЛ-0006',NULL,'2104SN2920B',NULL,NULL,NULL,138,25,NULL,11,9,5,NULL,9,5,NULL,NULL,NULL,NULL,'10.50.7.18','00adb2ddc53d',NULL,NULL,NULL,'3041',NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(56,56,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,external_links,contracts_ids','МСК-ИБП-0002',NULL,'3B1629X15430',NULL,NULL,NULL,69,5,NULL,3,2,4,NULL,6,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(57,57,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,external_links,contracts_ids','МСК-ИБП-0003',NULL,'3B1629X15432',NULL,NULL,NULL,69,6,NULL,3,4,4,NULL,6,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(58,58,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,external_links,contracts_ids','МСК-ИБП-0004',NULL,'3B1629X15433',NULL,NULL,NULL,69,7,NULL,4,3,5,NULL,6,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(59,59,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,external_links,contracts_ids','МСК-ИБП-0005',NULL,'3B1629X15436',NULL,NULL,NULL,69,17,NULL,4,7,5,NULL,6,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(60,60,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,it_staff_id,state_id,external_links,contracts_ids','ЧЕЛ-ИБП-0002',NULL,'3B1629X15437',NULL,NULL,NULL,69,8,NULL,9,12,NULL,NULL,1,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(61,61,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,state_id,external_links,contracts_ids','ЧЕЛ-ИБП-0003',NULL,'3B1629X15438',NULL,NULL,NULL,69,9,NULL,9,13,4,NULL,NULL,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(62,62,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,head_id,it_staff_id,state_id,external_links,contracts_ids','ЧЕЛ-ИБП-0004',NULL,'3B1629X15439',NULL,NULL,NULL,69,10,NULL,10,14,5,NULL,1,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(63,63,'2025-05-23 11:27:12',NULL,NULL,'num,sn,model_id,arms_id,places_id,user_id,state_id,external_links,contracts_ids','ЧЕЛ-ИБП-0005',NULL,'3B1629X1543A',NULL,NULL,NULL,69,11,NULL,10,15,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL),(64,41,'2025-05-25 16:07:23',NULL,NULL,'materials_usages_ids','МСК-ПРН-0001',NULL,'VCF8955916',NULL,NULL,NULL,53,NULL,NULL,3,NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,'10.20.40.10','0017c87697b1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',0,0,'4',NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `techs_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `techs_in_services`
--

DROP TABLE IF EXISTS `techs_in_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `techs_in_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_id` int DEFAULT NULL,
  `tech_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-techs_in_services_uid` (`tech_id`),
  KEY `idx-techs_in_services_sid` (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `techs_in_services`
--

LOCK TABLES `techs_in_services` WRITE;
/*!40000 ALTER TABLE `techs_in_services` DISABLE KEYS */;
INSERT INTO `techs_in_services` VALUES (6,4,14),(8,4,23),(10,6,14),(14,7,14),(16,15,14),(17,21,36),(18,21,37),(19,21,38),(20,21,39),(21,21,40),(23,4,12),(24,6,12),(25,7,12),(26,4,22);
/*!40000 ALTER TABLE `techs_in_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ui_dynagrid`
--

DROP TABLE IF EXISTS `ui_dynagrid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ui_dynagrid` (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Unique dynagrid setting identifier',
  `filter_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Filter setting identifier',
  `sort_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sort setting identifier',
  `data` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Json encoded data for the dynagrid configuration',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ui_dynagrid`
--

LOCK TABLES `ui_dynagrid` WRITE;
/*!40000 ALTER TABLE `ui_dynagrid` DISABLE KEYS */;
INSERT INTO `ui_dynagrid` VALUES ('comps-index_',NULL,NULL,'{\"page\":\"20\",\"theme\":\"panel-primary\",\"keys\":[\"e04112b1\",\"815e4826\",\"8432c01f\",\"fec7ddce\",\"6043e090\",\"d72dfff6\",\"a67558c3\",\"ff7e2279\"],\"filter\":\"\",\"sort\":\"\"}'),('contracts-index_',NULL,NULL,'{\"page\":\"100\",\"theme\":\"panel-success\",\"keys\":[\"e04112b1\",\"608b6dcd\",\"d17677bd\",\"382ea44d\",\"d3dfbda5\"],\"filter\":\"\",\"sort\":\"\"}'),('maintenance-jobs-index_',NULL,NULL,'{\"page\":\"200\",\"theme\":\"panel-primary\",\"keys\":[\"e04112b1\",\"f6bc3db2\",\"7e9692ec\"],\"filter\":\"\",\"sort\":\"\"}'),('networks-index_',NULL,NULL,'{\"page\":100,\"theme\":\"panel-primary\",\"keys\":[\"e04112b1\",\"940318f0\",\"744d892a\",\"7027b453\",\"27f9b629\",\"b2715e5a\"],\"filter\":\"\",\"sort\":\"\"}'),('services-index_',NULL,NULL,'{\"page\":100,\"theme\":\"panel-primary\",\"keys\":[\"e04112b1\",\"7d7e01b7\",\"940318f0\",\"a71a2a92\",\"b027c4c9\",\"1fa83a3a\"],\"filter\":null,\"sort\":\"\"}'),('soft-index_',NULL,NULL,'{\"page\":\"1000\",\"theme\":\"panel-primary\",\"keys\":[\"ee6a33ae\",\"744d892a\",\"448b7fcc\",\"7a706434\"],\"filter\":\"\",\"sort\":\"\"}'),('techs-index_',NULL,NULL,'{\"page\":\"100\",\"theme\":\"panel-primary\",\"keys\":[\"c15b98b6\",\"07c0f858\",\"bddd5274\",\"8ff1ef6e\",\"b5633c6c\",\"b6d29147\",\"608b6dcd\",\"8432c01f\",\"815e4826\",\"9b8d8098\",\"7e1fbe19\",\"d7ffff64\",\"82347bee\",\"744d892a\",\"d3dfbda5\"],\"filter\":\"\",\"sort\":\"\"}');
/*!40000 ALTER TABLE `ui_dynagrid` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ui_dynagrid_dtl`
--

DROP TABLE IF EXISTS `ui_dynagrid_dtl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ui_dynagrid_dtl` (
  `id` varchar(100) NOT NULL COMMENT 'Unique dynagrid detail setting identifier',
  `category` varchar(10) NOT NULL COMMENT 'Dynagrid detail setting category "filter" or "sort"',
  `name` varchar(150) NOT NULL COMMENT 'Name to identify the dynagrid detail setting',
  `data` varchar(5000) DEFAULT NULL COMMENT 'Json encoded data for the dynagrid detail configuration',
  `dynagrid_id` varchar(100) NOT NULL COMMENT 'Related dynagrid identifier',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_dynagrid_dtl_UK1` (`name`,`category`,`dynagrid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ui_dynagrid_dtl`
--

LOCK TABLES `ui_dynagrid_dtl` WRITE;
/*!40000 ALTER TABLE `ui_dynagrid_dtl` DISABLE KEYS */;
/*!40000 ALTER TABLE `ui_dynagrid_dtl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ui_tables_cols`
--

DROP TABLE IF EXISTS `ui_tables_cols`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ui_tables_cols` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `column` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-ui_tables_cols-table` (`table`),
  KEY `idx-ui_tables_cols-column` (`column`),
  KEY `idx-ui_tables_cols-user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ui_tables_cols`
--

LOCK TABLES `ui_tables_cols` WRITE;
/*!40000 ALTER TABLE `ui_tables_cols` DISABLE KEYS */;
INSERT INTO `ui_tables_cols` VALUES (1,'org-inet-index','services_id',1,'11.11'),(2,'org-inet-index','name',1,'11.1'),(3,'org-inet-index','places_id',1,'8.19'),(4,'org-inet-index','account',1,'11.11'),(5,'org-inet-index','networks_ids',1,'14.03'),(6,'org-inet-index','totalUnpaid',1,'9.78'),(7,'org-inet-index','charge',1,'5.85'),(8,'org-inet-index','cost',1,'12.44'),(9,'org-inet-index','comment',1,'16.39'),(10,'lic-types','comment',1,'70.78'),(11,'lic-types','descr',1,'29.19'),(12,'services-index','name',1,'14.28'),(13,'services-index','sites',1,'8.98'),(14,'services-index','providingSchedule',1,'13.24'),(15,'services-index','segment',1,'13.34'),(16,'services-index','supportSchedule',1,'14.85'),(17,'services-index','responsible',1,'18.21'),(18,'services-index','compsAndTechs',1,'17.09'),(20,'services-comps-index','ip',1,'9.08'),(21,'services-comps-index','name',1,'9.07'),(22,'services-comps-index','comment',1,'8.51'),(23,'services-comps-index','mac',1,'9.09'),(24,'services-comps-index','services_ids',1,'16.94'),(25,'services-comps-index','os',1,'7.7'),(26,'services-comps-index','vRamGb',1,'8.01'),(27,'services-comps-index','vHddGb',1,'5.67'),(28,'services-comps-index','vCpuCores',1,'7.69'),(29,'services-comps-index','arm_id',1,'9.1'),(30,'services-comps-index','places_id',1,'9.1');
/*!40000 ALTER TABLE `ui_tables_cols` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_groups`
--

DROP TABLE IF EXISTS `user_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_groups` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notebook` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ad_group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sync_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_groups`
--

LOCK TABLES `user_groups` WRITE;
/*!40000 ALTER TABLE `user_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(16) DEFAULT NULL,
  `org_id` int DEFAULT NULL,
  `Orgeh` varchar(16) DEFAULT NULL,
  `Doljnost` varchar(255) DEFAULT NULL,
  `Ename` varchar(255) NOT NULL COMMENT 'Полное имя',
  `Persg` int NOT NULL DEFAULT '1',
  `Uvolen` tinyint(1) NOT NULL COMMENT 'Уволен',
  `Login` varchar(32) DEFAULT NULL,
  `Email` varchar(64) DEFAULT NULL,
  `Phone` varchar(32) DEFAULT NULL,
  `Mobile` varchar(255) DEFAULT NULL,
  `work_phone` varchar(32) DEFAULT NULL,
  `Bday` varchar(16) DEFAULT NULL,
  `manager_id` varchar(16) DEFAULT NULL,
  `employ_date` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Дата приема',
  `resign_date` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Дата увольнения',
  `nosync` tinyint(1) NOT NULL DEFAULT '0',
  `auth_key` varchar(255) DEFAULT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `notepad` text,
  `private_phone` varchar(255) DEFAULT NULL,
  `external_links` text,
  `uid` varchar(64) DEFAULT NULL,
  `ips` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `struct_id` (`Orgeh`),
  KEY `dismissed` (`Uvolen`),
  KEY `Persg` (`Persg`),
  KEY `nosync` (`nosync`),
  KEY `idx-users-employee_id` (`employee_id`),
  KEY `idx-users-org_id` (`org_id`),
  KEY `idx-users-uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'1',2,'','Системный администратор','Безруков Аверьян Егорович',1,0,'admin','admin@X3.team','3034','+7 (912) 137-28-86','','9-02-1984','',NULL,NULL,0,NULL,NULL,'','',NULL,'','','$2y$10$7OAEju9r2jCAWUpA2F0PZew.62aUgCjZ6MED1A8ZOXsJc7m5SgU9i'),(2,'08000004',1,'1','Бухгалтер','Белозёрова Нина Геннадиевна',1,0,'NinaBelozerova','NinaBelozerova@taburetka.fidonet','1123','+7 (998) 558-41-00','','27-09-2001','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(3,'08000002',1,'2','Менеджер','Садовский Ким Тарасович',1,0,'KimSadovskiy','KimSadovskiy@taburetka.fidonet','1201','+7 (961) 050-16-30','','20.08.1998','',NULL,NULL,0,NULL,NULL,'','+7 (931) 777-21-14',NULL,'','',NULL),(4,'08000006',1,'1','Главный бухгалтер','Бровина Серафима Викторовна',1,0,'SerafimaBrovina','SerafimaBrovina@taburetka.fidonet','1122','+7 (922) 194-16-41','','17.03.1983','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(5,'08000007',1,'4','Генеральный директор','Александров Назар Иванович',1,0,'NazarAleksandrov','NazarAleksandrov@taburetka.fidonet','1100','+7 (959) 891-61-87','','18.11.1983','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(6,'08000011',1,'3','Системный администратор','Зимин Даниил Егорович',1,0,'DaniilZimin','DaniilZimin@taburetka.fidonet','1136','+7 (952) 073-14-59','','26.05.1991','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(7,'08000012',1,'2','Менеджер','Мартынова Анфиса Романовна',1,0,'AnfisaMartynova','AnfisaMartynova@taburetka.fidonet','1202','+7 (919) 346-73-99','','17.08.2000','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(8,'08000011',1,'5','Офис менеджер','Мартын Викторина Антоновна',1,0,'ViktorinaMartyn','info@taburetka.fidonet','1000','+7 (947) 481-53-92','','30.09.2001','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(9,'2',2,'','Системный администратор','Левченко Вениамин Васильевич',1,0,'VeniaminLevchenko','VeniaminLevchenko@X3.team','3041','+7 (991) 634-40-89','','18.01.2000','',NULL,NULL,0,NULL,NULL,'','',NULL,'','10.50.50.6',NULL),(10,'4',2,'','Специалист техподдержки','Баринов Борис Григорьевич',1,0,'BorisBarinov','BorisBarinov@X3.team','3044','+7 (967) 238-36-05','','24.05.1977','',NULL,NULL,0,NULL,NULL,'','',NULL,'','10.50.50.5',NULL),(11,'08000002',1,'2','Менеджер','Тарская Элина Львовна',1,1,'ElinaTarskaya','ElinaTarskaya@taburetka.fidonet','','+7 (919) 074-41-69','','08.12.1975','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(12,'08000013',1,'1','Бухгалтер','Городнов Силантий Петрович',1,0,'SilantiyGorodnov','SilantiyGorodnov@taburetka.fidonet','3021','','','08.12.1975','',NULL,NULL,0,NULL,NULL,'','+7 (950) 575-66-51',NULL,'','10.50.50.4',NULL),(13,'08000014',1,'1','Бухгалтер по первичным документам','Осипова Борислава Филипповна',1,0,'BorislavaOsipova','BorislavaOsipova@taburetka.fidonet','3024','','','04.05.1995','',NULL,NULL,0,NULL,NULL,'','+7 (964) 960-41-93',NULL,'','',NULL),(14,'08000015',1,'2','Менеджер по работе с корп. клиентами','Питерский Арнольд Матвеевич',1,0,'ArnoldPiterskiy','sales@taburetka.fidonet','3011','+7 (901) 400-88-82','','27.06.1975','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(15,'08000017',1,'2','Менеджер','Кириллов Аполлон Евгеньевич',1,0,'ApollonKirillov','sales2@taburetka.fidonet','3014','+7 (961) 020-15-86','','27.09.1975','',NULL,NULL,0,NULL,NULL,'','',NULL,'','',NULL),(17,'',NULL,NULL,'','Гость',3,0,'guest','','','','','','',NULL,NULL,0,NULL,NULL,'','',NULL,'','','$2y$10$0.iukrq0FBy6VHvLzBvULOPUliLd70Qo7Sl1hGvJs0.r5sJH9t1gu');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_in_aces`
--

DROP TABLE IF EXISTS `users_in_aces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_in_aces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `users_id` int NOT NULL,
  `aces_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-users_in_aces_ace_id` (`aces_id`),
  KEY `idx-users_in_aces_user_id` (`users_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_in_aces`
--

LOCK TABLES `users_in_aces` WRITE;
/*!40000 ALTER TABLE `users_in_aces` DISABLE KEYS */;
INSERT INTO `users_in_aces` VALUES (1,10,1),(2,9,1),(4,1,3),(7,6,4);
/*!40000 ALTER TABLE `users_in_aces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_in_contracts`
--

DROP TABLE IF EXISTS `users_in_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_in_contracts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `users_id` int NOT NULL,
  `contracts_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-users_in_contracts-users_id` (`users_id`),
  KEY `idx-users_in_contracts-contracts_id` (`contracts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_in_contracts`
--

LOCK TABLES `users_in_contracts` WRITE;
/*!40000 ALTER TABLE `users_in_contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_in_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_in_groups`
--

DROP TABLE IF EXISTS `users_in_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_in_groups` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `users_id` int NOT NULL,
  `groups_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-users_in_groups-users_id` (`users_id`),
  KEY `idx-users_in_groups-groups_id` (`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_in_groups`
--

LOCK TABLES `users_in_groups` WRITE;
/*!40000 ALTER TABLE `users_in_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_in_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_in_services`
--

DROP TABLE IF EXISTS `users_in_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_in_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-users_in_services_uid` (`user_id`),
  KEY `idx-users_in_services_sid` (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_in_services`
--

LOCK TABLES `users_in_services` WRITE;
/*!40000 ALTER TABLE `users_in_services` DISABLE KEYS */;
INSERT INTO `users_in_services` VALUES (6,5,1),(14,12,10),(21,22,6),(22,23,9),(24,3,10),(25,3,9),(34,18,1),(35,13,6),(36,11,10),(41,21,1),(42,24,6),(43,20,6),(44,20,9),(45,17,1),(49,26,6);
/*!40000 ALTER TABLE `users_in_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_in_svc_infrastructure`
--

DROP TABLE IF EXISTS `users_in_svc_infrastructure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_in_svc_infrastructure` (
  `id` int NOT NULL AUTO_INCREMENT,
  `services_id` int DEFAULT NULL,
  `users_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-users_in_svc_infrastructure-services` (`services_id`),
  KEY `idx-users_in_svc_infrastructure-users` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_in_svc_infrastructure`
--

LOCK TABLES `users_in_svc_infrastructure` WRITE;
/*!40000 ALTER TABLE `users_in_svc_infrastructure` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_in_svc_infrastructure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_cache`
--

DROP TABLE IF EXISTS `wiki_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wiki_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `page` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dependencies` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `valid` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx-wiki_cache-page` (`page`(250))
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_cache`
--

LOCK TABLES `wiki_cache` WRITE;
/*!40000 ALTER TABLE `wiki_cache` DISABLE KEYS */;
INSERT INTO `wiki_cache` VALUES (1,'_internal.sys_:maintenance-jobs:1:description','||','2025-05-29 10:34:51',1),(2,'_internal.sys_:maintenance-jobs:2:description','||','2025-05-29 08:37:25',0),(3,'_internal.sys_:maintenance-jobs:2:descriptionRecursive','||','2025-05-27 11:48:18',1),(4,'_internal.sys_:maintenance-jobs:1:descriptionRecursive','||','2025-05-29 09:08:01',1),(5,'_internal.sys_:maintenance-jobs:3:descriptionRecursive','||','2025-05-29 11:32:42',1),(6,'_internal.sys_:maintenance-jobs:3:description','||','2025-05-29 10:34:23',0);
/*!40000 ALTER TABLE `wiki_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'arms_test_crud'
--

--
-- Dumping routines for database 'arms_test_crud'
--
/*!50003 DROP FUNCTION IF EXISTS `getplacepath` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `getplacepath`(`place_id` INT) RETURNS text CHARSET utf8mb4 COLLATE utf8mb4_general_ci
    DETERMINISTIC
BEGIN
    DECLARE res TEXT CHARACTER SET utf8mb4;
    CALL getplacepath(place_id, res);
    RETURN res;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `getplacetop` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `getplacetop`(`place_id` INT) RETURNS int
    DETERMINISTIC
BEGIN
    DECLARE res INT;
    CALL getplacetop(place_id, res);
    RETURN res;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `getServiceSegment` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `getServiceSegment`(`itemId` INT) RETURNS int
    DETERMINISTIC
BEGIN
    DECLARE res INT;
	CALL getServiceSegment(itemId, res);
    RETURN res;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `getplacepath` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `getplacepath`(IN `place_id` INT, OUT `path` TEXT CHARACTER SET utf8mb4)
    READS SQL DATA
    COMMENT 'Recursive path build'
BEGIN
    DECLARE placename VARCHAR(20) CHARACTER SET utf8mb4;
    DECLARE temppath TEXT CHARACTER SET utf8mb4;
    DECLARE tempparent INT;
    SET max_sp_recursion_depth = 32;
    SELECT short, parent_id FROM places WHERE id=place_id INTO placename, tempparent;
    IF tempparent IS NULL
    THEN
        SET path = placename;
    ELSE
        CALL getplacepath(tempparent, temppath);
        SET path = CONCAT(temppath, '/', placename);
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `getplacetop` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `getplacetop`(IN `place_id` INT, OUT `top` INT)
BEGIN
    DECLARE tempparent INT;
    SET max_sp_recursion_depth = 32;
    SELECT parent_id FROM places WHERE id=place_id INTO tempparent;
    IF tempparent IS NULL
    THEN
        SET top = place_id;
    ELSE
        CALL getplacetop(tempparent, top);
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `getServiceSegment` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `getServiceSegment`(IN `itemId` INT, OUT `resultValue` INT)
    READS SQL DATA
    COMMENT 'Recursive search of NOT NULL segment_id value'
BEGIN
  DECLARE parentId INT;
  SET max_sp_recursion_depth = 32;
  SELECT segment_id, parent_id FROM services WHERE id=itemId INTO resultValue,parentId;
  IF (resultValue IS NULL) and (NOT parentId IS NULL) THEN
    CALL getServiceSegment(parentId,resultValue);
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-29 12:42:07
