-- reserve (study hub room reservation) database
-- on infinityfree: delete the CREATE DATABASE and USE lines, create the database in the control panel,
-- then import this file inside that database
CREATE DATABASE IF NOT EXISTS room_reservation;
USE room_reservation;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- kinds of spaces (the admin can add more in the Room Types page)
CREATE TABLE room_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type_name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(150) NULL
) ENGINE=InnoDB;

CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_name VARCHAR(100) NOT NULL,
  building VARCHAR(100) NOT NULL,
  room_type_id INT NOT NULL,
  capacity INT NOT NULL,
  price_per_hour DECIMAL(8,2) NOT NULL DEFAULT 0,
  status ENUM('Available','Maintenance') NOT NULL DEFAULT 'Available',
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reference_code VARCHAR(12) NULL UNIQUE,
  room_id INT NOT NULL,
  guest_name VARCHAR(100) NOT NULL,
  contact VARCHAR(50) NOT NULL,
  email VARCHAR(100) NULL,
  purpose VARCHAR(255) NOT NULL,
  reservation_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  status ENUM('Pending','Approved','Cancelled') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- default admin login: admin / admin123
INSERT INTO users (username, password, full_name) VALUES
('admin', '$2y$10$FlzdBX/o2tzMOZgr4kVXpeRelQ95l6cXACEHRkd5j1YuOtZsZwy9u', 'System Administrator');

INSERT INTO room_types (type_name, description) VALUES
('Study Room', 'Quiet rooms for solo or group study'),
('Meeting Room', 'Rooms for team meetings and discussions'),
('Hackathon Hall', 'Big open space for events and hackathons'),
('Co-working Table', 'Shared table with power outlet');

-- sample rooms (replace them with your own in the admin pages)
INSERT INTO rooms (room_name, building, room_type_id, capacity, price_per_hour, status, description) VALUES
('Quiet Study Room', 'Ground Floor', 1, 4, 50, 'Available', 'Silent zone with Wi-Fi and power outlets'),
('Group Study Room', 'Ground Floor', 1, 8, 80, 'Available', 'Whiteboard and a big table for group work'),
('Meeting Room A', '2nd Floor', 2, 12, 150, 'Available', 'TV screen, whiteboard and air-conditioning'),
('Hackathon Hall', '3rd Floor', 3, 40, 500, 'Available', 'Open space, many outlets, projector and sound system'),
('Co-working Table 1', '2nd Floor', 4, 1, 30, 'Available', 'Hot desk with outlet and Wi-Fi'),
('Meeting Room B', '2nd Floor', 2, 10, 120, 'Maintenance', 'Aircon under repair');

INSERT INTO reservations (reference_code, room_id, guest_name, contact, email, purpose, reservation_date, start_time, end_time, status) VALUES
('RR-000001', 4, 'Juan Dela Cruz', '09171234567', 'juan@gmail.com', 'Hackathon practice', CURDATE(), '12:00:00', '15:00:00', 'Approved'),
('RR-000002', 2, 'Maria Santos', '09981234567', 'maria@gmail.com', 'Group study', CURDATE(), '13:00:00', '16:00:00', 'Pending');
