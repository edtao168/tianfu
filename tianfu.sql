-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: tianfu
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1783775612),('-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1783775612;',1783775612);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` bigint unsigned NOT NULL DEFAULT '1',
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'expense',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否啟用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_shop_id_index` (`shop_id`),
  KEY `categories_parent_id_index` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,1,NULL,'食品餐飲','expense','cake',100,1,'2026-06-29 22:59:32','2026-06-29 22:59:32'),(2,1,1,'食材','expense','shopping-bag',101,1,'2026-06-29 22:59:32','2026-06-29 22:59:32'),(3,1,1,'早餐','expense','cake',102,1,'2026-06-29 22:59:32','2026-06-29 22:59:32'),(4,1,1,'午餐','expense','hand-raised',103,1,'2026-06-29 22:59:32','2026-06-29 22:59:32'),(5,1,1,'晚餐','expense','sparkles',104,1,'2026-06-29 22:59:32','2026-06-29 22:59:32'),(6,1,1,'酒','expense','trophy',105,1,'2026-06-29 22:59:32','2026-06-29 22:59:32'),(7,1,1,'宵夜','expense','moon',106,1,'2026-06-29 22:59:32','2026-06-29 22:59:32'),(8,1,NULL,'居家生活','expense','home',200,1,'2026-06-29 22:59:32','2026-06-29 22:59:32'),(9,1,8,'線下購物','expense','shopping-cart',201,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(10,1,8,'線上購物','expense','globe-alt',202,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(11,1,8,'房租房貸','expense','key',203,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(12,1,8,'水費','expense','beaker',204,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(13,1,8,'電費','expense','bolt',205,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(14,1,8,'燃氣','expense','fire',206,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(15,1,8,'網路通信','expense','wifi',207,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(16,1,8,'家電','expense','tv',208,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(17,1,8,'修繕','expense','paint-brush',209,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(18,1,NULL,'交通出行','expense','truck',300,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(19,1,18,'保養維修','expense','cog',301,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(20,1,18,'大眾運輸','expense','ticket',302,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(21,1,18,'油費','expense','fire',303,1,'2026-06-29 22:59:33','2026-06-29 22:59:33'),(22,1,18,'停車費','expense','no-symbol',304,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(23,1,18,'過路費','expense','credit-card',305,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(24,1,18,'計程車','expense','users',306,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(25,1,NULL,'學習教育','expense','academic-cap',400,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(26,1,25,'學費','expense','academic-cap',401,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(27,1,25,'書籍','expense','book-open',402,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(28,1,25,'文具','expense','pencil',403,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(29,1,25,'證照費','expense','identification',404,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(30,1,NULL,'娛樂休閒','expense','film',500,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(31,1,30,'旅遊','expense','map',501,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(32,1,30,'電影劇場','expense','film',502,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(33,1,30,'唱歌','expense','musical-note',503,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(34,1,30,'遊戲','expense','puzzle-piece',504,1,'2026-06-29 22:59:34','2026-06-29 22:59:34'),(35,1,30,'美髮美體','expense','scissors',505,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(36,1,NULL,'人情往來','expense','users',600,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(37,1,36,'送禮','expense','gift',601,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(38,1,36,'孝親','expense','users',602,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(39,1,36,'子女教育','expense','academic-cap',603,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(40,1,36,'慈善','expense','sun',604,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(41,1,36,'寵物','expense','face-smile',605,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(42,1,NULL,'醫療保健','expense','heart',700,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(43,1,42,'醫療','expense','beaker',701,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(44,1,42,'保健','expense','heart',702,1,'2026-06-29 22:59:35','2026-06-29 22:59:35'),(45,1,NULL,'金融稅收','expense','shield-check',800,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(46,1,45,'社會保險','expense','building-office',801,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(47,1,45,'私人保險','expense','shield-check',802,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(48,1,45,'財產險','expense','document-duplicate',803,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(49,1,45,'手續費','expense','arrow-trending-up',804,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(50,1,45,'投資虧損','expense','arrow-trending-down',805,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(51,1,45,'所得稅','expense','receipt-percent',806,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(52,1,45,'房屋稅','expense','home',807,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(53,1,45,'地價稅','expense','globe-alt',808,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(54,1,45,'牌照稅','expense','key',809,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(55,1,45,'公路養管費','expense','folder',810,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(56,1,NULL,'營業支出','expense','briefcase',900,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(57,1,56,'進貨','expense','cube',901,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(58,1,56,'人工','expense','user-plus',902,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(59,1,56,'行銷','expense','megaphone',903,1,'2026-06-29 22:59:36','2026-06-29 22:59:36'),(60,1,56,'辦公','expense','building-office',904,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(61,1,NULL,'其它支出','expense','ellipsis-horizontal',1000,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(62,1,61,'借出','expense','paper-airplane',1001,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(63,1,61,'罰款','expense','exclamation-triangle',1002,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(64,1,61,'遺失','expense','eye-slash',1003,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(65,1,61,'呆帳','expense','trash',1004,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(66,1,NULL,'主動收入','income','currency-dollar',1200,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(67,1,66,'工資','income','currency-dollar',1201,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(68,1,66,'獎金','income','currency-dollar',1202,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(69,1,66,'兼職','income','briefcase',1203,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(70,1,66,'營業收入','income','building-storefront',1204,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(71,1,NULL,'被動收入','income','chart-bar',1300,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(72,1,71,'投資獲利','income','chart-bar',1301,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(73,1,71,'存款利息','income','banknotes',1302,1,'2026-06-29 22:59:37','2026-06-29 22:59:37'),(74,1,71,'配息','income','arrow-trending-up',1303,1,'2026-06-29 22:59:38','2026-06-29 22:59:38'),(75,1,71,'租金收入','income','home',1304,1,'2026-06-29 22:59:38','2026-06-29 22:59:38'),(76,1,71,'禮金','income','gift',1305,1,'2026-06-29 22:59:38','2026-06-29 22:59:38'),(77,1,71,'零用錢','income','folder',1306,1,'2026-06-29 22:59:38','2026-06-29 22:59:38'),(78,1,71,'退休金','income','heart',1307,1,'2026-06-29 22:59:38','2026-06-29 22:59:38'),(79,1,NULL,'其它收入','income','gift',1500,1,'2026-06-29 22:59:38','2026-06-29 22:59:38'),(80,1,79,'中獎','income','trophy',1501,1,'2026-06-29 22:59:38','2026-06-29 22:59:38'),(81,1,79,'退款','income','arrow-uturn-left',1502,1,'2026-06-29 22:59:38','2026-06-29 22:59:38'),(82,1,79,'理賠','income','shield-check',1503,1,'2026-06-29 22:59:39','2026-06-29 22:59:39');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_accounts`
--

DROP TABLE IF EXISTS `financial_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` bigint unsigned NOT NULL DEFAULT '1',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `balance` decimal(16,4) NOT NULL DEFAULT '0.0000',
  `credit_limit` decimal(16,4) NOT NULL DEFAULT '0.0000',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TWD',
  `memo` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financial_accounts_shop_id_index` (`shop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_accounts`
--

LOCK TABLES `financial_accounts` WRITE;
/*!40000 ALTER TABLE `financial_accounts` DISABLE KEYS */;
INSERT INTO `financial_accounts` VALUES (1,1,'台幣','cash',24244.0000,0.0000,'TWD',NULL,1,'2026-06-29 03:47:22','2026-07-05 22:34:01'),(2,1,'國泰世華銀行','bank',453800.0000,0.0000,'TWD',NULL,1,'2026-06-29 03:47:23','2026-06-29 05:20:53'),(3,1,'微信支付 (CNY)','e-wallet',27456.6500,0.0000,'CNY',NULL,1,'2026-06-29 03:47:23','2026-07-06 00:13:46'),(4,1,'支付寶 (CNY)','e-wallet',8798.0000,0.0000,'CNY',NULL,1,'2026-06-29 03:47:23','2026-07-03 22:46:49'),(5,1,'兆豐銀行台灣pay','bank',4128.0000,0.0000,'TWD',NULL,1,'2026-06-29 04:12:42','2026-07-02 03:53:05'),(6,1,'郵局','bank',79.0000,0.0000,'TWD',NULL,1,'2026-06-29 05:22:45','2026-06-29 05:22:45'),(7,1,'合作金庫','bank',910.0000,0.0000,'TWD',NULL,1,'2026-06-29 05:23:47','2026-06-29 05:23:47'),(8,1,'中信證券','securities',956400.0000,0.0000,'CNY',NULL,1,'2026-06-29 05:25:08','2026-07-06 05:53:02'),(9,1,'東吳證券','securities',240953.3300,0.0000,'CNY',NULL,1,'2026-06-29 05:25:46','2026-07-05 22:38:58'),(10,1,'國泰證券','securities',90814.0000,0.0000,'TWD',NULL,1,'2026-06-29 05:26:40','2026-06-29 05:26:40'),(11,1,'國泰證券複委託','securities',212406.0000,0.0000,'TWD',NULL,1,'2026-06-29 05:27:09','2026-06-29 05:27:09'),(12,1,'中國銀行5405','bank',0.0000,0.0000,'CNY',NULL,1,'2026-07-05 22:35:55','2026-07-06 00:13:46'),(13,1,'中國銀行5397','bank',916.6800,0.0000,'CNY',NULL,1,'2026-07-05 22:37:02','2026-07-05 22:37:02');
/*!40000 ALTER TABLE `financial_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_29_083520_create_financial_accounts_table',2),(5,'2026_06_29_084048_create_categories_table',3),(6,'2026_06_29_084252_create_transactions_table',3),(7,'2026_06_30_065802_add_is_active_to_categories_table',4),(8,'2026_06_30_233018_create_transaction_templates_table',5),(9,'2026_07_02_113155_add_user_id_to_transaction_templates_table',6),(10,'2026_07_02_114644_rename_and_reorder_columns_in_transaction_templates_table',7),(11,'2026_07_04_114846_convert_heroicons_to_phosphor_in_categories',8),(12,'2026_07_09_134915_create_partners_table',9);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partners`
--

DROP TABLE IF EXISTS `partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `contacts` json DEFAULT NULL,
  `joined_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partners_user_id_foreign` (`user_id`),
  CONSTRAINT `partners_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners`
--

LOCK TABLES `partners` WRITE;
/*!40000 ALTER TABLE `partners` DISABLE KEYS */;
INSERT INTO `partners` VALUES (1,1,'陶勇助','partners/NksKegT39VZTz3MGZk8KTmEGguEfWxNjiuYr2sWD.jpg',NULL,'管理員','{\"line\": null, \"carrier_num\": null}',NULL,1,'2026-07-11 04:34:02','2026-07-11 05:12:35',NULL);
/*!40000 ALTER TABLE `partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('edtaoisgod@gmail.com','$2y$12$5MfhFAMWY4J0E7e6XB79TOTbkCGcXHEt.SooqkqnOjAEcQNNNAbVK','2026-07-03 00:22:07');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('0oK0I7MndkJ7zZu1ua4qSVHz0sUVpyevcEwJnknW',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJIUnJ6UVpLdGVmQ3JFM0R5MjRLT2hKVW5yMFhDMDBGVjZtb1JZOWRHIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC90aWFuZnUudGVzdFwvZmluYW5jZVwvY29udGFjdCIsInJvdXRlIjoiZmluYW5jZS5jb250YWN0In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1784123763),('FUHoH1hjx7FpkzUJhuyrnWP4G1BuH9Lyg5EqbuX7',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJXcW5hMGozN25uaDZhbUoyTEdHVlNVUEZWMnozSnlnbldqR0dYd0RjIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC90aWFuZnUudGVzdCIsInJvdXRlIjoiZmluYW5jZS5hY2NvdW50cyJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1784207709);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_templates`
--

DROP TABLE IF EXISTS `transaction_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL DEFAULT '1',
  `user_id` bigint unsigned DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `from_account_id` bigint unsigned DEFAULT NULL,
  `to_account_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(16,4) NOT NULL DEFAULT '0.0000',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `memo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_templates_account_id_foreign` (`from_account_id`),
  KEY `transaction_templates_to_account_id_foreign` (`to_account_id`),
  KEY `transaction_templates_category_id_foreign` (`category_id`),
  KEY `transaction_templates_shop_id_type_index` (`shop_id`,`type`),
  KEY `transaction_templates_user_id_foreign` (`user_id`),
  CONSTRAINT `transaction_templates_account_id_foreign` FOREIGN KEY (`from_account_id`) REFERENCES `financial_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_templates_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_templates_to_account_id_foreign` FOREIGN KEY (`to_account_id`) REFERENCES `financial_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_templates`
--

LOCK TABLES `transaction_templates` WRITE;
/*!40000 ALTER TABLE `transaction_templates` DISABLE KEYS */;
INSERT INTO `transaction_templates` VALUES (1,1,1,'expense',6,5,NULL,240.0000,'高粱酒','小北高粱酒','2026-07-02 03:53:02','2026-07-02 03:53:02'),(2,1,NULL,'expense',2,5,NULL,244.0000,'高粱酒','小北高粱酒','2026-07-13 04:04:01','2026-07-14 23:22:06');
/*!40000 ALTER TABLE `transaction_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` bigint unsigned NOT NULL DEFAULT '1',
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `from_account_id` bigint unsigned DEFAULT NULL,
  `to_account_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(16,4) NOT NULL DEFAULT '0.0000',
  `recorded_at` datetime NOT NULL,
  `memo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_shop_id_index` (`shop_id`),
  KEY `transactions_user_id_index` (`user_id`),
  KEY `transactions_type_index` (`type`),
  KEY `transactions_category_id_index` (`category_id`),
  KEY `transactions_from_account_id_index` (`from_account_id`),
  KEY `transactions_to_account_id_index` (`to_account_id`),
  KEY `transactions_recorded_at_index` (`recorded_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='交易明細';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,1,1,'expense',2,5,NULL,240.0000,'2026-07-01 06:46:00','家樂福啤酒豆乾','2026-06-30 22:48:19','2026-06-30 22:48:19'),(2,1,1,'income',72,NULL,9,9804.3300,'2026-07-01 06:57:00','卖出300088长信科技11.1*2000','2026-06-30 22:59:05','2026-07-02 05:25:54'),(3,1,1,'expense',10,4,NULL,271.5800,'2026-07-01 08:08:00','拼多多\n特步骑行手套39.89\n刷子3.01\n狗冲锋衣138\n地垫室内51\n地垫室外40.68\n','2026-07-01 00:09:55','2026-07-02 06:08:34'),(4,1,1,'expense',10,4,NULL,833.7900,'2026-07-01 09:02:00','淘宝\n悬浮床437.95\n制冰盒6.82*2=13.64\n觅声北极星176.88\n手机支架9.87\n水管夹四个3.74\n毛毛雨毛巾两条37.55\n猫人凉席59.09\n《AI时代创富》26.89\n联想LP25蓝牙耳机68.18','2026-07-01 01:03:38','2026-07-02 06:05:24'),(5,1,1,'expense',10,4,NULL,28.6000,'2026-07-02 11:33:00','拼多多凳子','2026-07-02 03:34:01','2026-07-02 03:34:01'),(6,1,1,'expense',6,5,NULL,240.0000,'2026-07-02 11:52:00','小北高粱酒','2026-07-02 03:53:05','2026-07-02 03:53:05'),(7,1,1,'income',72,NULL,8,6400.0000,'2026-07-02 13:26:00','卖出601678滨化股份7.45*2000=14900','2026-07-02 05:28:39','2026-07-02 05:28:39'),(8,1,1,'expense',2,1,NULL,96.0000,'2026-07-03 08:26:00','元祥生香蕉檸檬青江菜青花菜','2026-07-03 00:27:51','2026-07-03 00:27:51'),(9,1,1,'income',81,NULL,4,437.9500,'2026-07-04 06:45:00','淘宝悬浮床逾期不发货','2026-07-03 22:46:49','2026-07-03 22:46:49'),(10,1,1,'expense',2,1,NULL,60.0000,'2026-07-05 06:33:00','挫冰','2026-07-05 22:34:01','2026-07-05 22:34:01'),(11,1,1,'transfer',NULL,9,12,21000.0000,'2026-07-06 06:37:00','','2026-07-05 22:38:15','2026-07-05 22:38:15'),(12,1,1,'transfer',NULL,12,3,20000.0000,'2026-07-06 06:38:00','中轉晶之緣微信','2026-07-05 22:38:39','2026-07-06 00:13:47'),(13,1,1,'transfer',NULL,12,9,1000.0000,'2026-07-06 06:38:00','','2026-07-05 22:38:58','2026-07-05 22:38:58');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'陶勇助','edtaoisgod@gmail.com',NULL,'$2y$12$oLduerRVpcT8dWBoFmmteenUGBJL6FsGDG5yexrUJ.AY2.XZBJ22m','xzrGedjwmrvy21NCr1pmT1wNmOzuYPGOfuHL65hQerD2ZZroaJ01B25UN1dU','2026-07-02 03:24:47','2026-07-02 03:24:47');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-16 21:21:09
