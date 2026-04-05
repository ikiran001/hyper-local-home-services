-- Home Services Booking Platform — MySQL schema
-- Run this in phpMyAdmin or: mysql -u root -p < database/schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS home_services
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE home_services;

-- Optional users table (reserved for future auth / roles)
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS technicians (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  service_type VARCHAR(50) NOT NULL COMMENT 'electrician, ac_repair, plumber',
  area VARCHAR(255) NOT NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_technicians_service (service_type),
  KEY idx_technicians_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT NOT NULL,
  service VARCHAR(50) NOT NULL,
  issue TEXT NOT NULL,
  status ENUM('pending', 'assigned', 'completed') NOT NULL DEFAULT 'pending',
  technician_id INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_bookings_phone (phone),
  KEY idx_bookings_status (status),
  KEY idx_bookings_technician (technician_id),
  CONSTRAINT fk_bookings_technician
    FOREIGN KEY (technician_id) REFERENCES technicians (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Sample technicians (optional — remove in production if not needed)
INSERT INTO technicians (name, phone, service_type, area, is_available) VALUES
('Ravi Kumar', '9876543210', 'electrician', 'Indiranagar, Bangalore', 1),
('Suresh Nair', '9876543211', 'ac_repair', 'Koramangala, Bangalore', 1),
('Amit Patil', '9876543212', 'plumber', 'Whitefield, Bangalore', 1);
