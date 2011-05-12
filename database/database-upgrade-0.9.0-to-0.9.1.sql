---
--- Upgrading Database scheme
---  From version: 0.9.0
---  To   version: 0.9.1
---

--- Rename column "bytes" to "payload_bytes"

ALTER TABLE `log_event`
  	CHANGE `bytes` `payload_bytes` BIGINT(20) unsigned default '0';

