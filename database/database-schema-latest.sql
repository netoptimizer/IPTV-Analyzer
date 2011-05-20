-- MySQL dump 10.11
--
-- Host: localhost    Database: tvprobe
-- ------------------------------------------------------
-- Server version	5.0.51a-24+lenny4-log

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
-- Table structure for table `daemon_session`
--

DROP TABLE IF EXISTS `daemon_session`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `daemon_session` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `start_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `stop_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `heartbeat` timestamp NOT NULL default '0000-00-00 00:00:00',
  `probe_id` int(11) default NULL,
  `daemon_pid` smallint(5) unsigned default NULL,
  `mpeg2ts_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `mpeg2ts_version` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `log_event`
--

DROP TABLE IF EXISTS `log_event`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `log_event` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `stream_session_id` bigint(20) unsigned NOT NULL default '0',
  `record_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `probe_id` int(11) NOT NULL default '0',
  `daemon_session_id` int(10) unsigned NOT NULL default '0',
  `skips` bigint(20) unsigned NOT NULL default '0',
  `discontinuity` bigint(20) unsigned NOT NULL default '0',
  `errsec` int(10) unsigned NOT NULL default '0',
  `delta_skips` int(10) unsigned NOT NULL default '0',
  `delta_discon` int(10) unsigned NOT NULL default '0',
  `delta_errsec` int(10) unsigned NOT NULL default '0',
  `payload_bytes` bigint(20) unsigned default '0',
  `delta_payload_bytes` int(10) unsigned NOT NULL default '0',
  `packets` bigint(20) unsigned default '0',
  `delta_packets` int(10) unsigned NOT NULL default '0',
  `event_type` int(10) unsigned NOT NULL default '0',
  `pids` smallint(5) unsigned NOT NULL default '0',
  `delta_poll` int(10) unsigned default NULL,
  `last_poll` timestamp NOT NULL default '0000-00-00 00:00:00',
  `probe_time` timestamp NOT NULL default '0000-00-00 00:00:00',
   last_update  timestamp NOT NULL default '0000-00-00 00:00:00',
   delta_update float unsigned default NULL,
  `multicast_dst` char(15) NOT NULL,
  `ip_src` char(15) default NULL,
  `ttl` smallint(5) unsigned default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65536 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS event_type;
CREATE TABLE event_type (
  bitmask     int(10) unsigned NOT NULL default '0',
  label       varchar(15) NOT NULL,
  description varchar(255),
  PRIMARY KEY (bitmask)
) ENGINE=InnoDB

LOCK TABLES event_type WRITE;
INSERT INTO event_type (bitmask, label, description) VALUES
(  1, "new_stream", "New stream detected"),
(  2, "drop"      , "Drops detected, both skips and discon"),
(  4, "no_signal" , "Stream have stopped transmitting data"),
( 32, "transition", "The event_state changed since last poll"),
( 64, "heartbeat" , "Heartbeat event to monitor status"),
(128, "invalid"   , "Some invalid event situation arose");
UNLOCK TABLES;

--
-- Table structure for table `probes`
--

DROP TABLE IF EXISTS `probes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `probes` (
  `id` int(11) NOT NULL auto_increment,
  `ip` varchar(15) NOT NULL,
  `input` varchar(100) NOT NULL,
  `shortloc` varchar(50) default 'unknown',
  `switch` varchar(50) default 'unknown',
  `name` varchar(100) default 'unknown',
  `description` varchar(100) default 'unknown',
  `location` varchar(100) default 'unknown',
  `address` varchar(100) default 'unknown',
  `distance` int(11) default '0',
  `input_ip` varchar(15) default '',
  `input_dev` varchar(16) default '',
  `procfile` varchar(100) default NULL,
  `switchport` varchar(50) default '',
  `switchtype` varchar(50) default '',
  `hidden` enum('no','yes') NOT NULL default 'no',
  PRIMARY KEY  (`id`),
  KEY `idx_identify` (`ip`,`input`,`shortloc`,`switch`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `stream_session`
--

DROP TABLE IF EXISTS `stream_session`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `stream_session` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `probe_id` int(11) NOT NULL default '0',
  `daemon_session_id` int(10) unsigned NOT NULL default '0',
  `start_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `stop_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `multicast_dst` char(15) NOT NULL,
  `ip_src` char(15) default NULL,
  `port_dst` smallint(5) unsigned default NULL,
  `port_src` smallint(5) unsigned default NULL,
  `logid_begin` bigint(20) unsigned NOT NULL default '0',
  `logid_end` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4719 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-11-01 14:26:23
