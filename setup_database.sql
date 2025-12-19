-- Complete MySQL setup for Quiz App
-- Run this file in MySQL: mysql -u root -p < setup_database.sql

-- Create database
DROP DATABASE IF EXISTS quiz_app;
CREATE DATABASE quiz_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quiz_app;

-- Config table
CREATE TABLE config (
  id TINYINT PRIMARY KEY,
  exam_minutes INT NOT NULL DEFAULT 60,
  question_count INT NOT NULL DEFAULT 40
) ENGINE=InnoDB;

INSERT INTO config (id, exam_minutes, question_count) VALUES (1, 60, 40);

-- Questions table
CREATE TABLE questions (
  id INT PRIMARY KEY,
  category VARCHAR(255),
  prompt TEXT NOT NULL,
  option_a VARCHAR(500) NOT NULL,
  option_b VARCHAR(500) NOT NULL,
  option_c VARCHAR(500) NOT NULL,
  option_d VARCHAR(500) NOT NULL,
  answer VARCHAR(500) NOT NULL
) ENGINE=InnoDB;

-- Sessions table
CREATE TABLE sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(255) NOT NULL UNIQUE,
  name VARCHAR(255),
  submitted TINYINT NOT NULL DEFAULT 0,
  last_saved DATETIME,
  answers_json JSON,
  timings_json JSON,
  question_ids_json JSON,
  violations INT NOT NULL DEFAULT 0,
  exam_minutes INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Violations table
CREATE TABLE violations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(255) NOT NULL,
  type VARCHAR(255) NOT NULL,
  severity INT NOT NULL,
  message VARCHAR(500),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_violations_identifier (identifier)
) ENGINE=InnoDB;

-- Messages table
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender VARCHAR(255) NOT NULL,
  receiver VARCHAR(255) NOT NULL,
  text TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_messages_conv (receiver, sender)
) ENGINE=InnoDB;

-- Snapshots table
CREATE TABLE snapshots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(255) NOT NULL,
  image MEDIUMTEXT NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_snapshots_identifier (identifier)
) ENGINE=InnoDB;
