--
-- Upgrading Database scheme
--  From version: 0.9.0
--  To   version: 0.9.1
--

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

