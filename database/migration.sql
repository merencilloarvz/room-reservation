-- run this only if you already imported the old database.sql
-- phpMyAdmin: open the room_reservation database, SQL tab, paste, Go
ALTER TABLE reservations
  ADD COLUMN reference_code VARCHAR(12) NULL UNIQUE AFTER id,
  ADD COLUMN email VARCHAR(100) NULL AFTER contact;

-- give the old reservations a reference code too
UPDATE reservations SET reference_code = CONCAT('RR-', LPAD(id, 6, '0')) WHERE reference_code IS NULL;
