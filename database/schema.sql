-- Home Services Dispatch System — MySQL schema (XAMPP / production)
-- Import: mysql -u root -p < database/schema.sql
-- Or phpMyAdmin → Import

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS home_services_dispatch
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE home_services_dispatch;

CREATE TABLE IF NOT EXISTS technicians (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  service_type VARCHAR(50) NOT NULL COMMENT 'electrician, ac_repair, plumber',
  area VARCHAR(64) NOT NULL COMMENT 'area key e.g. vikhroli',
  is_available TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=available, 0=busy',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tech_service_area (service_type, area),
  KEY idx_tech_phone (phone),
  KEY idx_tech_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT NOT NULL,
  area VARCHAR(64) NOT NULL,
  service VARCHAR(50) NOT NULL,
  issue TEXT NOT NULL,
  priority ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal',
  status ENUM('pending', 'assigned', 'completed') NOT NULL DEFAULT 'pending',
  technician_id INT UNSIGNED DEFAULT NULL,
  price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  completed_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_book_phone (phone),
  KEY idx_book_status (status),
  KEY idx_book_area (area),
  KEY idx_book_completed (completed_at),
  KEY idx_book_technician (technician_id),
  CONSTRAINT fk_bookings_technician
    FOREIGN KEY (technician_id) REFERENCES technicians (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Sample technicians (matching areas & services for demo)
INSERT INTO technicians (name, phone, service_type, area, is_available) VALUES
('Ravi Kumar', '9876543210', 'electrician', 'vikhroli', 1),
('Suresh Nair', '9876543211', 'ac_repair', 'ghatkopar', 1),
('Amit Patil', '9876543212', 'plumber', 'powai', 1),
('Vikram Singh', '9876543213', 'electrician', 'ghatkopar', 1);
