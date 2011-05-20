--
-- Upgrading Database scheme
--  From version: 0.9.0
--  To   version: 0.9.1
--

DROP TABLE IF EXISTS event_type;
CREATE TABLE event_type (
  bitmask     int(10) unsigned NOT NULL default '0',
  label       varchar(15) NOT NULL,
  description varchar(255),
  PRIMARY KEY (bitmask)
) ENGINE=InnoDB;

LOCK TABLES event_type WRITE;
INSERT INTO event_type (bitmask, label, description) VALUES
(  1, "new_stream", "New stream detected"),
(  2, "drop"      , "Drops detected, both skips and discon"),
(  4, "no_signal" , "Stream have stopped transmitting data"),
( 32, "transition", "The event_state changed since last poll"),
( 64, "heartbeat" , "Heartbeat event to monitor status"),
(128, "invalid"   , "Some invalid event situation arose");
UNLOCK TABLES;

ALTER TABLE log_event
      MODIFY event_type int(10) unsigned NOT NULL default '0';

ALTER TABLE log_event
      MODIFY delta_poll float unsigned default NULL;

ALTER TABLE log_event
      ADD last_update   timestamp NOT NULL default '0000-00-00 00:00:00',
      ADD delta_update  float unsigned default NULL;

-- Add delta colums for payload_bytes and packets

ALTER TABLE log_event
      ADD `delta_payload_bytes` int(10) unsigned NOT NULL default '0' AFTER packets,
      ADD `delta_packets`       int(10) unsigned NOT NULL default '0' AFTER packets;

-- Rename column "bytes" to "payload_bytes"

ALTER TABLE log_event
      CHANGE `bytes` `payload_bytes` bigint(20) unsigned default '0';

-- Fix the last renaming from mp2t to mpeg2ts

ALTER TABLE daemon_session
      CHANGE `mp2t_created` `mpeg2ts_created` timestamp NOT NULL default '0000-00-00 00:00:00',
      CHANGE `mp2t_version` `mpeg2ts_version` varchar(50) default NULL;
