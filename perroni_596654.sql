-- Progettazione Web 
DROP DATABASE if exists perroni_596654; 
CREATE DATABASE perroni_596654; 
USE perroni_596654; 
-- MySQL dump 10.13  Distrib 5.7.28, for Win64 (x86_64)
--
-- Host: localhost    Database: perroni_596654
-- ------------------------------------------------------
-- Server version	5.7.28

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `gamehall`
--

DROP TABLE IF EXISTS `gamehall`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gamehall` (
  `MahjongTableId` int(11) NOT NULL AUTO_INCREMENT,
  `Player_0` varchar(20) DEFAULT NULL,
  `Player_1` varchar(20) DEFAULT NULL,
  `MatchStartingTime` datetime DEFAULT NULL,
  `MatchEndingTime` datetime DEFAULT NULL,
  PRIMARY KEY (`MahjongTableId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gamehall`
--

LOCK TABLES `gamehall` WRITE;
/*!40000 ALTER TABLE `gamehall` DISABLE KEYS */;
/*!40000 ALTER TABLE `gamehall` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaderboard`
--

DROP TABLE IF EXISTS `leaderboard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leaderboard` (
  `User` varchar(20) NOT NULL,
  `Points` int(11) NOT NULL,
  `NumPlayedMatches` int(11) NOT NULL,
  PRIMARY KEY (`User`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leaderboard`
--

LOCK TABLES `leaderboard` WRITE;
/*!40000 ALTER TABLE `leaderboard` DISABLE KEYS */;
/*!40000 ALTER TABLE `leaderboard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matchstate`
--

DROP TABLE IF EXISTS `matchstate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matchstate` (
  `TableId` int(11) NOT NULL,
  `PlayerWind_0` enum('E','S') DEFAULT NULL,
  `PlayerWind_1` enum('E','S') DEFAULT NULL,
  `CurrentRound` tinyint(4) DEFAULT '1',
  `Wall` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`TableId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matchstate`
--

LOCK TABLES `matchstate` WRITE;
/*!40000 ALTER TABLE `matchstate` DISABLE KEYS */;
/*!40000 ALTER TABLE `matchstate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result`
--

DROP TABLE IF EXISTS `result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `result` (
  `TableId` int(11) NOT NULL,
  `Winner` int(11) NOT NULL,
  `WinningHand` varchar(100) DEFAULT NULL COMMENT 'NULL in caso di pareggio o se un giocatore si arrende',
  `WinningTile` varchar(4) DEFAULT NULL,
  `HandType` json DEFAULT NULL,
  `Points` int(11) NOT NULL,
  PRIMARY KEY (`TableId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result`
--

LOCK TABLES `result` WRITE;
/*!40000 ALTER TABLE `result` DISABLE KEYS */;
/*!40000 ALTER TABLE `result` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER update_leaderboard_after_insert_on_result AFTER INSERT ON `result`
FOR EACH ROW

BEGIN

DECLARE winner_user VARCHAR(20);
DECLARE defeated_user VARCHAR(20);

-- recupero i nomi dei partecipanti
SELECT IF(NEW.Winner = 0, Player_0, Player_1), IF(NEW.Winner = 0, Player_1, Player_0)INTO winner_user, defeated_user
FROM gamehall
WHERE MahjongTableId = NEW.TableId;

CALL update_leaderboard(winner_user, NEW.Points);
CALL update_leaderboard(defeated_user, (-1) * NEW.Points);

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `roundstate`
--

DROP TABLE IF EXISTS `roundstate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roundstate` (
  `TableId` int(11) NOT NULL,
  `LastAction_E` tinyint(4) NOT NULL DEFAULT '0',
  `LastAction_S` tinyint(4) NOT NULL DEFAULT '0',
  `ActivePlayer` enum('E','S') DEFAULT 'E',
  `LastDiscard` varchar(4) DEFAULT NULL,
  `LastOpenTriplet` varchar(20) DEFAULT NULL,
  `Turn` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`TableId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roundstate`
--

LOCK TABLES `roundstate` WRITE;
/*!40000 ALTER TABLE `roundstate` DISABLE KEYS */;
/*!40000 ALTER TABLE `roundstate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utente`
--

DROP TABLE IF EXISTS `utente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utente` (
  `UserId` int(11) NOT NULL AUTO_INCREMENT,
  `Username` varchar(20) NOT NULL,
  `Hash` varchar(128) NOT NULL,
  `Stato` enum('offline','online','ingame') NOT NULL DEFAULT 'offline',
  PRIMARY KEY (`UserId`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utente`
--

LOCK TABLES `utente` WRITE;
/*!40000 ALTER TABLE `utente` DISABLE KEYS */;
/*!40000 ALTER TABLE `utente` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-01-25 20:39:03
