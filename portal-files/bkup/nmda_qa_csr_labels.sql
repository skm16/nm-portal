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
-- Table structure for table `csr_labels`
--

DROP TABLE IF EXISTS `csr_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `csr_labels` (
  `LabelsId` varchar(50) NOT NULL,
  `BusinessId` varchar(50) DEFAULT NULL,
  `CompanyName` varchar(100) DEFAULT NULL,
  `FirstName` varchar(45) DEFAULT NULL,
  `LastName` varchar(45) DEFAULT NULL,
  `Phone` varchar(45) DEFAULT NULL,
  `Email` varchar(45) DEFAULT NULL,
  `MailingAddress` varchar(100) DEFAULT NULL,
  `MailingCity` varchar(45) DEFAULT NULL,
  `MailingState` varchar(45) DEFAULT NULL,
  `MailingZip` varchar(45) DEFAULT NULL,
  `PhysicalAddress` varchar(100) DEFAULT NULL,
  `PhysicalCity` varchar(45) DEFAULT NULL,
  `PhysicalState` varchar(45) DEFAULT NULL,
  `PhysicalZip` varchar(45) DEFAULT NULL,
  `EligibilityProduct` tinyint DEFAULT NULL,
  `EligibilityProductLine` tinyint DEFAULT NULL,
  `EligibilityRebranding` tinyint DEFAULT NULL,
  `EligibilityTechnicalChanges` tinyint DEFAULT NULL,
  `GraphicDesignFee` text,
  `PlateCharges` text,
  `PhysicalLabels` text,
  `LogoProgram` varchar(10) DEFAULT NULL,
  `NoLabels` text,
  `ComplianceLaws` tinyint DEFAULT NULL,
  `ComplianceLicensing` tinyint DEFAULT NULL,
  `LabelProofs` tinyint DEFAULT NULL,
  `AgreeReprints` tinyint DEFAULT NULL,
  `AgreePayment` tinyint DEFAULT NULL,
  `AgreeCap` tinyint DEFAULT NULL,
  `Approved` tinyint DEFAULT NULL,
  `Rejected` tinyint DEFAULT NULL,
  `AdminInitials` varchar(5) DEFAULT NULL,
  `DateSubmitted` datetime DEFAULT CURRENT_TIMESTAMP,
  `DateApproved` datetime DEFAULT NULL,
  `DateRejected` datetime DEFAULT NULL,
  `UserId` varchar(50) DEFAULT NULL,
  `authTokenGUID` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`LabelsId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csr_labels`
--

LOCK TABLES `csr_labels` WRITE;
/*!40000 ALTER TABLE `csr_labels` DISABLE KEYS */;
INSERT INTO `csr_labels` VALUES ('0eabeaac-2052-463b-a5b0-19bbfc0e17ba','777a4bce-8970-41f4-837a-8d2f78f13265','The Daily Jerky','Stephen','Wersonick','505-250-5136','orders@thedailyjerky.com','1445 Eubank Blvd NE','Albuquerque','NM','87112','1445 Eubank Blvd NE','Albuquerque','NM','87112',0,0,1,0,'','','500000','Taste','All labels have been approved with Taste the Tradition logo and labels need to be reordered',1,1,1,1,1,1,0,0,NULL,'2023-11-30 17:34:36',NULL,NULL,'0b813a68-f03c-431e-b20d-743982c58a6c','19fe1102-dccd-4754-af07-63f3abf85dce'),('13f471fe-7ec0-4c07-a759-a4e50bee039a','993ad081-2f0d-421d-a9ff-c5e2f9878806','El Charro Mexican Food Industries','Susie','Snyder','575-317-1588','elcharrofoods@gmail.com','2504 Gaye Dr.','Roswell','New Mexico','88201','','','','',0,0,1,0,'100000','100000','450000','Taste','',1,1,1,1,1,1,0,0,NULL,'2023-11-09 20:23:14',NULL,NULL,'1611b8f5-fd3a-4344-9c9a-d6b69d03ce00','225a2339-7cb6-4900-aa10-0c9909ae2dfc'),('61d8dee5-0339-4b2b-b0b0-99b565ec6bfa','96008602-9ed7-48a5-a8a8-f6d6f37a0cb8','Lytle Ventures LLC, Dba Hatch Chile Country','Vernon','Lytle','575-937-4459','vernon@hatchchilecountry.com','2810 Sudderth Drive','Ruidoso','NM','88345-6306','Hatch Chile Country','Ruidoso','NM','88345-6306',1,1,0,0,'1284.13','1353.00','4800.00','Taste','Will be used on all labels for the new product line that will consist of 11 products ',1,1,1,1,1,1,1,0,'DS','2023-09-27 20:58:56','2023-10-19 17:28:33',NULL,'29d2b54a-b8d1-4e86-b149-0ce9adf1d30a','34fb1a3f-10e1-4e92-804e-cd3bdd21db1b'),('c93101f0-cecd-4c0a-ac27-eb68566edb7b','777a4bce-8970-41f4-837a-8d2f78f13265','The Daily Jerky','Stephen','Wersonick','505-250-5136','orders@thedailyjerky.com','1445 Eubank Blvd NE','Albuquerque','NM','87112','1445 Eubank Blvd NE','Albuquerque','NM','87112',0,0,1,0,'','','500000','Taste','All labels have been approved with Taste the Tradition logo and labels need to be reordered',1,1,1,1,1,1,0,1,'','2023-11-28 20:37:35',NULL,'2023-12-01 22:20:47','0b813a68-f03c-431e-b20d-743982c58a6c','82fa486e-d807-409f-8c45-8e43ba17d8e7'),('d49381d1-9a96-44c0-ac1b-d907da136ffd','b16344d9-24fd-4a6d-b0e9-0e635b7650a4','Cervantes Food Products, Inc.','Arian','Gonzales','505-254-9414','arian@cervantessalsa.com','1125 Arizona St. SE','Albuquerque','NM','87108','1125 Arizona St. SE','Albuquerque','NM','87108',0,0,0,1,'990.00','960.00','4724.00','Taste','New Mexico Taste the Tradition logo used',1,1,1,1,1,1,1,0,'','2023-10-11 20:02:09','2023-10-19 17:42:45',NULL,'06fce93e-6ce6-4b19-b5b8-1b47f8904fdd','6d22317a-8e26-4840-a5f8-94eeeb85e814'),('e6460eee-9103-43cd-9fb9-e489c2fcf4a4','f129ce4e-04f8-4a5e-aa2f-a347ef7d1589','Freeze Dried Products, LLC','Lane','Grado','505-280-6314','Lane@33Bongos.com','320 E. Wyatt Dr. Suite C','Las Cruces','NM','88001','320 E. Wyatt Dr.  Suite C','Las Cruces','NM','88001',0,0,1,1,'','145000','950000','Taste','The packaging (bags)  are freshly printed. I thought we ordered the packaging and then submitted for reimbursement after the packaging has been received and paid for. Therefore, the \"label proofs\" box checked below is not accurate, but I had to check it in order to submit. The new packaging does NOT have the \"Taste...\" logo on the bags, but they do have the Certified Hatch Chile logos.\n\nThe price breakdown is as follows: \nFreight: $2,625.00  \nPackaging (bags) $9,600.00\nPlates Charges: $1,350.00',1,1,1,1,1,1,0,0,NULL,'2023-12-05 21:55:37',NULL,NULL,'53e83eb7-8a2f-4440-9021-c0fa481104a6','ce54cbb8-4ab6-44b9-b046-c5350df91b10');
/*!40000 ALTER TABLE `csr_labels` ENABLE KEYS */;
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

-- Dump completed on 2025-09-02 13:42:31
