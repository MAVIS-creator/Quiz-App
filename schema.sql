-- SQLite schema for Quiz PHP app
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS config (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  exam_minutes INTEGER NOT NULL DEFAULT 60,
  question_count INTEGER NOT NULL DEFAULT 40
);
INSERT OR IGNORE INTO config (id, exam_minutes, question_count) VALUES (1, 60, 40);

CREATE TABLE IF NOT EXISTS questions (
  id INTEGER PRIMARY KEY,
  category TEXT,
  prompt TEXT NOT NULL,
  option_a TEXT NOT NULL,
  option_b TEXT NOT NULL,
  option_c TEXT NOT NULL,
  option_d TEXT NOT NULL,
  answer TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  identifier TEXT NOT NULL,
  name TEXT,
  submitted INTEGER NOT NULL DEFAULT 0,
  last_saved TEXT,
  answers_json TEXT,
  timings_json TEXT,
  question_ids_json TEXT,
  violations INTEGER NOT NULL DEFAULT 0,
  exam_minutes INTEGER,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_sessions_identifier ON sessions(identifier);

CREATE TABLE IF NOT EXISTS violations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  identifier TEXT NOT NULL,
  type TEXT NOT NULL,
  severity INTEGER NOT NULL,
  message TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_violations_identifier ON violations(identifier);

CREATE TABLE IF NOT EXISTS messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sender TEXT NOT NULL,
  receiver TEXT NOT NULL,
  text TEXT NOT NULL,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_messages_conv ON messages(receiver, sender);

CREATE TABLE IF NOT EXISTS snapshots (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  identifier TEXT NOT NULL,
  image TEXT NOT NULL, -- base64 data URI
  timestamp TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_snapshots_identifier ON snapshots(identifier);
