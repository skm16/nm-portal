-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: nmda1.crrpwh5yscfu.us-west-2.rds.amazonaws.com    Database: nmda
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '';

--
-- Table structure for table `group_type`
--

DROP TABLE IF EXISTS `group_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_type` (
  `GroupTypeId` varchar(50) NOT NULL,
  `GroupType` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`GroupTypeId`),
  UNIQUE KEY `CompanyTypeId_UNIQUE` (`GroupTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_type`
--

LOCK TABLES `group_type` WRITE;
/*!40000 ALTER TABLE `group_type` DISABLE KEYS */;
INSERT INTO `group_type` VALUES ('0833cc7e-a46f-426f-a96f-25a97a1feb63','Packer/Shipper'),('22d4a750-b0e4-4b9a-bbea-ff8c41416626','Other'),('4ed8b3d4-2639-4b42-828e-b86b0b2e0905','Nursery'),('57f9a1dc-71e4-4fdc-aeca-84c47faefae7','Restaurant'),('7021c29e-2407-4250-8b3c-a2644a0e4056','Processor'),('7dbaaba1-f57a-4b67-97d1-82a4664df4e4','Wholesaler'),('83abfb50-176e-4274-ab62-a5a5cc464f4f','Retailer'),('886e7812-101f-4cdd-a0ec-1c7372cfda58','Distributor'),('93c34fdd-4eea-41b5-abb3-4ed0baa58ad7','Winery'),('9f4d3f54-adeb-47f0-b72a-4795f3c0a468','Grower');
/*!40000 ALTER TABLE `group_type` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-02 13:42:38
