<?php
// Adds a dedicated `group` column to the existing `config` table and migrates data.
// Safe to run multiple times.

require __DIR__ . '/../db.php';

function out($msg) { echo $msg . "\n"; }

try {
    $pdo = db();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if table exists
    $tbl = $pdo->query("SHOW TABLES LIKE 'config'")->fetchColumn();
    if (!$tbl) {
        out("config table not found. Aborting.");
        exit(1);
    }

    // Check if `group` column exists
    $hasGroup = $pdo->query("SHOW COLUMNS FROM config LIKE 'group'")->fetch();
    if ($hasGroup) {
        out("Column `group` already exists. Nothing to do.");
        exit(0);
    }

    out("Adding column `group` INT to config...");
    $pdo->exec("ALTER TABLE config ADD COLUMN `group` INT NOT NULL DEFAULT 1");

    out("Migrating existing rows: setting group = id...");
    $pdo->exec("UPDATE config SET `group` = id");

    // Optional: add index to speed up lookups by group
    out("Adding index on `group`...");
    $pdo->exec("ALTER TABLE config ADD INDEX idx_config_group (`group`)");

    out("Done.\nRun API calls with group-based config now.");
} catch (Exception $e) {
    out("Error: " . $e->getMessage());
    exit(1);
}
