--
-- Upgrading Database scheme
--  From version: 0.9.0
--  To   version: 0.9.1
--
-- Add delta colums for payload_bytes and packets

ALTER TABLE log_event
      ADD `delta_payload_bytes` int(10) unsigned NOT NULL default '0' AFTER packets,
      ADD `delta_packets`       int(10) unsigned NOT NULL default '0' AFTER packets;

-- Rename column "bytes" to "payload_bytes"

ALTER TABLE log_event
      CHANGE `bytes` `payload_bytes` bigint(20) unsigned default '0';

