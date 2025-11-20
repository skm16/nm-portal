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
-- Table structure for table `csr_advertising`
--

DROP TABLE IF EXISTS `csr_advertising`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `csr_advertising` (
  `AdvertisingId` varchar(50) NOT NULL,
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
  `MediaType` varchar(45) DEFAULT NULL,
  `MediaTypeOther` text,
  `CollaboratingCompanies` text,
  `FundingExplanation` text,
  `Differentiation` text,
  `TargetMarkets` text,
  `AdvertisingDates` text,
  `ExpectedReach` text,
  `CostBreakdown` text,
  `Approved` tinyint DEFAULT NULL,
  `Rejected` tinyint DEFAULT NULL,
  `AdminInitials` varchar(5) DEFAULT NULL,
  `DateSubmitted` datetime DEFAULT CURRENT_TIMESTAMP,
  `DateApproved` datetime DEFAULT NULL,
  `DateRejected` datetime DEFAULT NULL,
  `UserId` varchar(50) DEFAULT NULL,
  `authTokenGUID` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`AdvertisingId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csr_advertising`
--

LOCK TABLES `csr_advertising` WRITE;
/*!40000 ALTER TABLE `csr_advertising` DISABLE KEYS */;
INSERT INTO `csr_advertising` VALUES ('1ed53ede-65b0-44a9-a445-f53efdb72f36','380793a4-6ac2-479b-b8e4-5519942d9eec','New Mexico Pinon Coffee','Madison','Rumbaugh','505-298-1964','madison@nmpinoncoffee.com','2420 Comanche RD NE ste D2','Albuquerque','NM','87107','','','','','Other','Box Truck Wrap','N/A','Vehicle Wrap for new Box Truck ','This is to have our logo along with Taste the Traditions Logo on our vehicle that delivers our products around the state. ','New Mexico','June 2025','500,000 k','Trailer Wrap and Installation- $5,000 ',0,0,NULL,'2025-07-15 15:34:32',NULL,NULL,'334cd0b1-b4bf-44d9-9f8f-7d848405220b','a52f935c-f814-4e3e-8259-7792ac78407f'),('639f68b1-2607-4a22-93c8-dec3e2863a0b','6790ebb0-26c8-474e-9044-6137d4089942','Gilly Loco ','Mark ','Escajeda','505-298-7777','mark@gillyloco.com','10655 Montgomery Blvd NE','Albuquerque','New Mexico','87111','10655 Montgomery Blvd NE','Albuquerque','New Mexico','87111','Non-Organic Social Media Posts/Campaign','','We will be using Cumulus Radio to run our social media','We are a small (but growing) operation and we can\'t really afford to pay for the social media side the way it should be done. With the new age of marketing we feel like this is so important to the growth of our company. We employ two college students to help with in store taste testing. This funding will help keep a budget for their employment and grow brand awareness through social media. ','We have never used a media company like this before where we would run advertisements on Facebook, Instagram, YouTube, and Google PPC. ','We want to run multiple ads all with different purpose. Overall though, we want to hit a wide demographic to showcase the NM cuisine of chips, salsa and margaritas! ','This will be a 16 week ad from when we have the YouTube ads, banner ads and social media posts ready to launch. Target date is January 1st. ','Minimum of 500,000 impressions. For the YouTube ads, we plan on allowing have the view either skip or watch the whole ad. If the viewer skips or watches some of the ad we are not charged, if they watch the full ad we will be charged.  ','Standard Display $4,000\nGoogle Audience $3,200\nYouTube $4,800\nFacebook and Instagram $4,000\nTotal $16,000\nTotal With tax $17,220.00',0,0,NULL,'2023-12-21 18:07:17',NULL,NULL,'72bd64ff-78f4-41a9-a6d6-add2788276bb','5448f16e-4791-447e-990f-caac2f27b28f'),('70065831-1925-44ab-9404-785bec4639fb','1757c052-823f-431c-8e84-6474f23b4fe9','Corrales Growers Market','Bonnie','Gonzales','505-414-6706','b.g.growersmarket@gmail.com','PO Box 2598','Corrales','New Mexico','87048','500 Jones Road','Corrales','NM','87048','Other','Print advertising to reach metropolitan communities outside of Corrales. Specifically Rio Rancho and Albuquerque. ','We provide marketing resources and a marketing facility for 40 individual farms and ranches. ','We have a limited advertising budget which covers a print schedule for advertising our Sunday seasonal schedule, Wednesday peak season markets, and winter schedule. We use the majority of our dollars to promote seasonal availability (NMFMA Harvest Calendar) and for promotion of FMNP for WIC and Senior clients (Digital Benefits). As a year round market we pay attention to providing scheduling information matched through print ads, our website, and on-site outreach. ','The reimbursement we are requesting allowed us to use print ads to advertise further into denser population areas ( Rio Rancho, Albuquerque) and reach a target with a greater population of FMNP client','Rio Rancho and Albuquerque metropolitan areas.','The 1/4 page ads specific to the Ambassador Chef program ran in the Rio Rancho Observer on Thursday August 24th and the Albuquerque Journal Saturday August 26th preceding the Ambassador Chef presentat','People looking for a market that focuses on local food access, availability and use generally become regular customers once they have visited us. This extended advertising and promotional event gave u','We can provide invoices, bank payment documentation and tear sheets \n\nProduct	Qty	                Unit Price\n(1) Payment Amount\n                         	        $1,965.67\n      Subtotal	                 $1,965.67\n3% Processing Fee	$58.97\nTotal	                       $2,024.64\nThis is the total for:\n 1-1/4 page, Rio Rancho Observer August 24th\n1-1/4 page, Albuquerque Journal August 26th',1,0,'DS','2023-08-31 17:45:31','2023-10-24 14:34:16',NULL,'4287c217-681f-424b-8b60-be73f2c96334','6c7fe8e1-891b-4bb2-bbb2-e80ba6619596'),('ebcc9d82-79ee-489b-acef-f4ae54da7014','380793a4-6ac2-479b-b8e4-5519942d9eec','New Mexico Pinon Coffee','Madison','Rumbaugh','505-298-1964','madison@nmpinoncoffee.com','2420 Comanche RD NE ste D2','Albuquerque','NM','87107','','','','','Other','Box Truck Wrap','N/A','Vehicle Wrap for new Box Truck ','This is to have our logo along with Taste the Traditions Logo on our vehicle that delivers our products around the state. ','New Mexico','June 2025','500,000 k','Trailer Wrap and Installation- $5,000 ',0,0,NULL,'2025-06-02 18:42:59',NULL,NULL,'334cd0b1-b4bf-44d9-9f8f-7d848405220b','a52f935c-f814-4e3e-8259-7792ac78407f');
/*!40000 ALTER TABLE `csr_advertising` ENABLE KEYS */;
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

-- Dump completed on 2025-09-02 13:42:26
