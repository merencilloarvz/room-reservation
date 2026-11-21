-- run this only if you already imported the previous database.sql (the one with the Classroom / Laboratory room types)
-- phpMyAdmin: open the room_reservation database, SQL tab, paste, Go. run it only once
-- it keeps your data, adds the room_types table and the price column

CREATE TABLE room_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type_name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(150) NULL
) ENGINE=InnoDB;

-- keep the types your rooms already use, then add the study hub types
INSERT INTO room_types (type_name) SELECT DISTINCT room_type FROM rooms;
INSERT IGNORE INTO room_types (type_name, description) VALUES
('Study Room', 'Quiet rooms for solo or group study'),
('Meeting Room', 'Rooms for team meetings and discussions'),
('Hackathon Hall', 'Big open space for events and hackathons'),
('Co-working Table', 'Shared table with power outlet');

-- rooms get a room_type_id and a price per hour
ALTER TABLE rooms
  ADD COLUMN room_type_id INT NULL AFTER building,
  ADD COLUMN price_per_hour DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER capacity;
UPDATE rooms r JOIN room_types t ON t.type_name = r.room_type SET r.room_type_id = t.id;
ALTER TABLE rooms
  MODIFY room_type_id INT NOT NULL,
  ADD FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON UPDATE CASCADE,
  DROP COLUMN room_type;
