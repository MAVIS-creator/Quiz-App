<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $pdo->exec("DROP DATABASE IF EXISTS quiz_app");
    $pdo->exec("CREATE DATABASE quiz_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE quiz_app");
    
    // Now create all tables
    $tables = [
        // Config
        "CREATE TABLE config (
          id TINYINT PRIMARY KEY,
          exam_minutes INT NOT NULL DEFAULT 60,
          question_count INT NOT NULL DEFAULT 40
        ) ENGINE=InnoDB",
        
        "INSERT INTO config (id, exam_minutes, question_count) VALUES (1, 60, 40)",
        
        // Questions
        "CREATE TABLE questions (
          id INT PRIMARY KEY,
          category VARCHAR(255),
          prompt TEXT NOT NULL,
          option_a VARCHAR(500) NOT NULL,
          option_b VARCHAR(500) NOT NULL,
          option_c VARCHAR(500) NOT NULL,
          option_d VARCHAR(500) NOT NULL,
          answer VARCHAR(500) NOT NULL
        ) ENGINE=InnoDB",
        
        // Sessions
        "CREATE TABLE sessions (
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
          time_adjustment_seconds INT DEFAULT 0,
          point_deduction INT DEFAULT 0,
          status ENUM('active', 'booted', 'cancelled', 'completed') DEFAULT 'active',
          accuracy_score DECIMAL(5,2) DEFAULT 0.00,
          avg_time_per_question DECIMAL(10,2) DEFAULT 0.00,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Violations
        "CREATE TABLE violations (
          id INT AUTO_INCREMENT PRIMARY KEY,
          identifier VARCHAR(255) NOT NULL,
          type VARCHAR(255) NOT NULL,
          severity INT NOT NULL,
          message VARCHAR(500),
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_violations_identifier (identifier)
        ) ENGINE=InnoDB",
        
        // Messages
        "CREATE TABLE messages (
          id INT AUTO_INCREMENT PRIMARY KEY,
          sender VARCHAR(255) NOT NULL,
          receiver VARCHAR(255) NOT NULL,
          text TEXT NOT NULL,
          read_status TINYINT DEFAULT 0,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_messages_conv (receiver, sender)
        ) ENGINE=InnoDB",
        
        // Snapshots
        "CREATE TABLE snapshots (
          id INT AUTO_INCREMENT PRIMARY KEY,
          identifier VARCHAR(255) NOT NULL,
          image MEDIUMTEXT NOT NULL,
          timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_snapshots_identifier (identifier)
        ) ENGINE=InnoDB",
        
        // Student Questions Order
        "CREATE TABLE student_questions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          identifier VARCHAR(255) NOT NULL,
          question_ids_order JSON NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY unique_student (identifier)
        ) ENGINE=InnoDB",
        
        // Time Adjustments
        "CREATE TABLE time_adjustments (
          id INT AUTO_INCREMENT PRIMARY KEY,
          identifier VARCHAR(255) NOT NULL,
          adjustment_seconds INT NOT NULL,
          reason VARCHAR(500),
          admin_name VARCHAR(255) DEFAULT 'Admin',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_time_adj_identifier (identifier)
        ) ENGINE=InnoDB",
        
        // Admin Actions
        "CREATE TABLE admin_actions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          identifier VARCHAR(255) NOT NULL,
          action_type ENUM('time_penalty', 'point_deduction', 'boot_out', 'exam_cancelled', 'warning') NOT NULL,
          value INT DEFAULT 0,
          reason TEXT,
          admin_name VARCHAR(255) DEFAULT 'Admin',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_actions_identifier (identifier)
        ) ENGINE=InnoDB",
        
        // Audio Detections
        "CREATE TABLE audio_detections (
          id INT AUTO_INCREMENT PRIMARY KEY,
          identifier VARCHAR(255) NOT NULL,
          volume_level INT NOT NULL,
          detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_audio_identifier (identifier)
        ) ENGINE=InnoDB",
        
        // Face Detections
        "CREATE TABLE face_detections (
          id INT AUTO_INCREMENT PRIMARY KEY,
          identifier VARCHAR(255) NOT NULL,
          face_count INT NOT NULL,
          snapshot_id INT,
          detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_face_identifier (identifier),
          FOREIGN KEY (snapshot_id) REFERENCES snapshots(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // Audio Clips (5-10 second recordings)
        "CREATE TABLE audio_clips (
          id INT AUTO_INCREMENT PRIMARY KEY,
          identifier VARCHAR(255) NOT NULL,
          audio_data MEDIUMTEXT NOT NULL,
          timestamp BIGINT NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_audio_clips_identifier (identifier)
        ) ENGINE=InnoDB"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
        echo "✓ Executed\n";
    }
    
    echo "\n✅ Database created successfully with all tables!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
