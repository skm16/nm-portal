-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: nmda1.crrpwh5yscfu.us-west-2.rds.amazonaws.com    Database: nmda_qa
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
-- Table structure for table `business_address`
--

DROP TABLE IF EXISTS `business_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_address` (
  `BusinessAddressId` varchar(50) NOT NULL,
  `BusinessId` varchar(50) NOT NULL,
  `AddressName` varchar(100) DEFAULT NULL,
  `AddressType` varchar(100) DEFAULT NULL,
  `Address` varchar(100) DEFAULT NULL,
  `Address2` varchar(100) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `State` varchar(100) DEFAULT NULL,
  `Zip` varchar(100) DEFAULT NULL,
  `Other` text,
  `Reservation` text,
  `Category` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`BusinessAddressId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_address`
--

LOCK TABLES `business_address` WRITE;
/*!40000 ALTER TABLE `business_address` DISABLE KEYS */;
INSERT INTO `business_address` VALUES ('08ddf0c6-3b1c-434a-a105-3e6373c40723','ae755dd4-3012-4b28-b106-a37a5217575d','test catsssss','Not open to the public (doesnâ€™t appear on the webs','123 tersffdsfds','','asdfsdfasf','fd','34234','','','Farmersâ€™ Markets,Local Food + Drink,Shop Local,Lodging'),('2cc4e640-77a3-4494-88eb-77cce985c82a','ae755dd4-3012-4b28-b106-a37a5217575d','Test1','Open to the public with a reservation','2101 Mountain Rd NW','','Albuquerque','NM','87104','','This is where you make a reservation','Farms + Ranches,Local Food + Drink,Lodging'),('4f0d959e-97e7-41fa-a4ad-04b0a66afb6b','ae755dd4-3012-4b28-b106-a37a5217575d','Test2','Open to the public during regular business hours','2101 Mountain Rd NW','Suite A','Albuquerque','NM','87104','','','Farmersâ€™ Markets'),('74ec65de-d166-44ec-89b8-c66004f20d32','ae755dd4-3012-4b28-b106-a37a5217575d','Test Oct 1','Open to the public during regular business hours','123 test','','Albuq','NM','87111','','',''),('92771a41-8f01-4bb3-a315-7b6f0df9b15b','ae755dd4-3012-4b28-b106-a37a5217575d','RTS','Other','2101 Mountain Rd NW','Suite A','Albuquerque','NM','87104','You can only contact us through our website','','Farms + Ranches,Pick-Your-Own,Local Food + Drink'),('93b2030e-ce00-427b-b5e1-ee6b8e4c1abf','ae755dd4-3012-4b28-b106-a37a5217575d','Test cats','Not open to the public (doesnâ€™t appear on the webs','123 test dr','','Alb','NM','87111','','','Shop Local,Lodging');
/*!40000 ALTER TABLE `business_address` ENABLE KEYS */;
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

-- Dump completed on 2025-09-02 13:42:29
