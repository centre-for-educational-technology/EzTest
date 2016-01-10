-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.1.9-MariaDB-log - mariadb.org binary distribution
-- Server OS:                    Win32
-- HeidiSQL Version:             9.3.0.4984
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping database structure for edu_testing
CREATE DATABASE IF NOT EXISTS `edu_testing` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `edu_testing`;


-- Dumping structure for table edu_testing.groups
CREATE TABLE IF NOT EXISTS `groups` (
  `GroupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `GroupName` varchar(64) NOT NULL,
  `Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data exporting was unselected.


-- Dumping structure for table edu_testing.groups_users
CREATE TABLE IF NOT EXISTS `groups_users` (
  `GroupID` int(11) unsigned NOT NULL,
  `UserID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`GroupID`,`UserID`),
  KEY `FK_groups_users_users` (`UserID`),
  CONSTRAINT `FK_groups_users_groups` FOREIGN KEY (`GroupID`) REFERENCES `groups` (`GroupID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_groups_users_users` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data exporting was unselected.


-- Dumping structure for table edu_testing.questions
CREATE TABLE IF NOT EXISTS `questions` (
  `QuestionID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Type` varchar(25) NOT NULL,
  `Stimulus` mediumtext NOT NULL,
  `Data` mediumtext NOT NULL,
  `Hash` char(32) NOT NULL,
  `UserID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`QuestionID`),
  UNIQUE KEY `Hash` (`Hash`),
  KEY `FK_questions_users` (`UserID`),
  CONSTRAINT `FK_questions_users` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data exporting was unselected.


-- Dumping structure for table edu_testing.users
CREATE TABLE IF NOT EXISTS `users` (
  `UserID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Email` varchar(64) NOT NULL,
  `Name` varchar(64) DEFAULT NULL,
  `Password` binary(60) NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `IsAdmin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data exporting was unselected.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
