/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: swd_shop
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `swd_shop`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `swd_shop` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `swd_shop`;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `slug` varchar(90) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Phones','phones'),
(2,'Laptops','laptops'),
(3,'Accessories','accessories'),
(4,'Home','home'),
(5,'Kitchen','kitchen');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(10) unsigned NOT NULL CHECK (`quantity` > 0),
  PRIMARY KEY (`order_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(20) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` > 0),
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,4,'Stoneware Ripple Mug','stoneware-ripple-mug','Hand-finished stoneware with a soft, tactile glaze for your daily coffee ritual.',8500.00,24,'https://placehold.co/600x400?text=Stoneware%20Ripple%20Mug',1,'2026-11-03 08:42:24'),
(2,4,'Linen Cushion Cover','linen-cushion-cover','A relaxed, breathable linen cover in a warm natural shade. Cushion insert not included.',14500.00,18,'https://placehold.co/600x400?text=Linen%20Cushion%20Cover',1,'2026-11-03 08:42:24'),
(3,4,'Woven Storage Basket','woven-storage-basket','A sturdy handwoven basket that keeps throws, toys and everyday clutter in order.',22000.00,0,'https://placehold.co/600x400?text=Woven%20Storage%20Basket',1,'2026-11-03 08:42:24'),
(4,4,'Ceramic Table Vase','ceramic-table-vase','A sculptural matte ceramic vase that looks lovely with fresh stems or on its own.',18000.00,12,'https://placehold.co/600x400?text=Ceramic%20Table%20Vase',1,'2026-11-03 08:42:24'),
(5,4,'Cotton Throw Blanket','cotton-throw-blanket','A generously sized, soft cotton throw for slow mornings and cool evenings.',32500.00,9,'https://placehold.co/600x400?text=Cotton%20Throw%20Blanket',1,'2026-11-03 08:42:24'),
(6,5,'Acacia Serving Board','acacia-serving-board','A beautifully grained acacia board for serving, chopping and sharing.',16500.00,15,'https://placehold.co/600x400?text=Acacia%20Serving%20Board',1,'2026-11-03 08:42:24'),
(7,5,'Glass Oil Bottle','glass-oil-bottle','A clear glass pourer with a neat stainless spout for oils and dressings.',7800.00,31,'https://placehold.co/600x400?text=Glass%20Oil%20Bottle',1,'2026-11-03 08:42:24'),
(8,5,'Everyday Cotton Apron','everyday-cotton-apron','A durable cotton apron with an adjustable neck strap and roomy front pocket.',12500.00,20,'https://placehold.co/600x400?text=Everyday%20Cotton%20Apron',1,'2026-11-03 08:42:24'),
(9,5,'Stackable Prep Bowls','stackable-prep-bowls','A set of three glazed ceramic bowls, designed to stack neatly between uses.',19500.00,14,'https://placehold.co/600x400?text=Stackable%20Prep%20Bowls',1,'2026-11-03 08:42:24'),
(10,5,'Bamboo Utensil Set','bamboo-utensil-set','Five lightweight bamboo cooking tools with smooth, comfortable handles.',9900.00,27,'https://placehold.co/600x400?text=Bamboo%20Utensil%20Set',1,'2026-11-03 08:42:24'),
(11,3,'Canvas Market Tote','canvas-market-tote','A roomy, reusable cotton-canvas tote with reinforced handles for everyday errands.',11500.00,36,'https://placehold.co/600x400?text=Canvas%20Market%20Tote',1,'2026-11-03 08:42:24'),
(12,3,'Minimal Brass Keyring','minimal-brass-keyring','A polished solid-brass keyring that makes a small, thoughtful gift.',6500.00,42,'https://placehold.co/600x400?text=Minimal%20Brass%20Keyring',1,'2026-11-03 08:42:24'),
(13,3,'Everyday Leather Wallet','everyday-leather-wallet','A compact full-grain leather wallet with useful card slots and a slim profile.',28500.00,8,'https://placehold.co/600x400?text=Everyday%20Leather%20Wallet',1,'2026-11-03 08:42:24'),
(14,3,'Botanical Scented Candle','botanical-scented-candle','A hand-poured soy candle with a gentle botanical scent and cotton wick.',13500.00,17,'https://placehold.co/600x400?text=Botanical%20Scented%20Candle',1,'2026-11-03 08:42:24'),
(15,3,'Classic Steel Water Bottle','classic-steel-water-bottle','A double-wall stainless bottle that keeps drinks cold or warm while you are out.',17500.00,23,'https://placehold.co/600x400?text=Classic%20Steel%20Water%20Bottle',1,'2026-11-03 08:42:24');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-03  2:22:15
