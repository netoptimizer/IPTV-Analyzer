--
-- Upgrading Database scheme
--  From version: 0.9.1
--  To   version: 0.9.2
--

INSERT INTO event_type (bitmask, label, description) VALUES
  (  8, "ok_signal" , "Indicate signal returned (clear no-signal trap)"),
  ( 16, "ttl_change", "Indicate TTL changed");
