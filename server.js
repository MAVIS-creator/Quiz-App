import express from 'express';
import cors from 'cors';
import path from 'path';
import { fileURLToPath } from 'url';
import Database from 'better-sqlite3';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3001;

app.use(cors());
app.use(express.json());

// Initialize SQLite database
const dbPath = path.join(__dirname, 'quiz.db');
const db = new Database(dbPath);
db.pragma('journal_mode = WAL');

// Create tables
db.exec(`
  CREATE TABLE IF NOT EXISTS config (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS sessions (
    identifier TEXT PRIMARY KEY,
    matric TEXT,
    phone TEXT,
    name TEXT,
    startTime TEXT,
    answers TEXT,
    questionTimings TEXT,
    violations INTEGER DEFAULT 0,
    submitted INTEGER DEFAULT 0,
    submittedAt TEXT,
    lastSaved TEXT,
    questionCount INTEGER
  );

  CREATE TABLE IF NOT EXISTS time_extensions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identifier TEXT NOT NULL,
    minutesAdded INTEGER NOT NULL,
    reason TEXT,
    timestamp TEXT NOT NULL,
    acknowledged INTEGER DEFAULT 0
  );

  CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender TEXT NOT NULL,
    receiver TEXT NOT NULL,
    body TEXT NOT NULL,
    timestamp TEXT NOT NULL,
    read INTEGER DEFAULT 0
  );

  CREATE UNIQUE INDEX IF NOT EXISTS idx_sessions_identifier ON sessions(identifier);
  CREATE INDEX IF NOT EXISTS idx_time_extensions_identifier ON time_extensions(identifier);
  CREATE INDEX IF NOT EXISTS idx_messages_sender_receiver ON messages(sender, receiver);
`);

// Seed default question count
const existingConfig = db.prepare('SELECT value FROM config WHERE key = ?').get('question_count');
if (!existingConfig) {
  db.prepare('INSERT INTO config (key, value) VALUES (?, ?)').run('question_count', '40');
}

const getQuestionCount = () => {
  const row = db.prepare('SELECT value FROM config WHERE key = ?').get('question_count');
  return row ? Number(row.value) : 40;
};

// Helpers
const serialize = (value) => JSON.stringify(value ?? {});
const deserialize = (value) => {
  try {
    return value ? JSON.parse(value) : {};
  } catch (err) {
    return {};
  }
};

const attachExtensions = (identifier) => {
  return db.prepare(`
    SELECT minutesAdded, reason, timestamp, acknowledged
    FROM time_extensions
    WHERE identifier = ?
    ORDER BY timestamp DESC
  `).all(identifier);
};

// Routes
app.get('/api/question-count', (req, res) => {
  res.json({ questionCount: getQuestionCount() });
});

app.post('/api/question-count', (req, res) => {
  const { questionCount } = req.body;
  if (!questionCount || questionCount < 1 || questionCount > 100) {
    return res.status(400).json({ error: 'Invalid question count' });
  }
  db.prepare('INSERT INTO config (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
    .run('question_count', String(questionCount));
  res.json({ success: true, questionCount });
});

app.post('/api/sessions', (req, res) => {
  const session = req.body;
  const identifier = session.matric || session.phone;
  if (!identifier) {
    return res.status(400).json({ error: 'identifier required (matric or phone)' });
  }

  db.prepare(`
    INSERT INTO sessions (identifier, matric, phone, name, startTime, answers, questionTimings, violations, submitted, submittedAt, lastSaved, questionCount)
    VALUES (@identifier, @matric, @phone, @name, @startTime, @answers, @questionTimings, @violations, @submitted, @submittedAt, @lastSaved, @questionCount)
    ON CONFLICT(identifier) DO UPDATE SET
      matric = excluded.matric,
      phone = excluded.phone,
      name = excluded.name,
      startTime = excluded.startTime,
      answers = excluded.answers,
      questionTimings = excluded.questionTimings,
      violations = excluded.violations,
      submitted = excluded.submitted,
      submittedAt = excluded.submittedAt,
      lastSaved = excluded.lastSaved,
      questionCount = excluded.questionCount
  `).run({
    identifier,
    matric: session.matric ?? null,
    phone: session.phone ?? null,
    name: session.name ?? '',
    startTime: session.startTime ?? new Date().toISOString(),
    answers: serialize(session.answers),
    questionTimings: serialize(session.questionTimings),
    violations: session.violations ?? 0,
    submitted: session.submitted ? 1 : 0,
    submittedAt: session.submittedAt ?? null,
    lastSaved: session.lastSaved ?? new Date().toISOString(),
    questionCount: session.questionCount ?? null
  });

  res.json({ success: true });
});

app.get('/api/sessions', (req, res) => {
  const rows = db.prepare('SELECT * FROM sessions').all();
  const sessions = rows.map((row) => ({
    matric: row.matric,
    phone: row.phone,
    name: row.name,
    startTime: row.startTime,
    answers: deserialize(row.answers),
    questionTimings: deserialize(row.questionTimings),
    violations: row.violations ?? 0,
    submitted: !!row.submitted,
    submittedAt: row.submittedAt,
    lastSaved: row.lastSaved,
    questionCount: row.questionCount ?? undefined,
    timeExtensions: attachExtensions(row.matric || row.phone)
  }));
  res.json(sessions);
});

app.get('/api/time-extension/:identifier', (req, res) => {
  const { identifier } = req.params;
  const ext = db.prepare(`
    SELECT minutesAdded, reason, timestamp, acknowledged
    FROM time_extensions
    WHERE identifier = ? AND acknowledged = 0
    ORDER BY timestamp DESC
    LIMIT 1
  `).get(identifier);
  res.json(ext || null);
});

app.post('/api/time-extension/acknowledge', (req, res) => {
  const { identifier } = req.body;
  if (!identifier) return res.status(400).json({ error: 'identifier required' });
  db.prepare('UPDATE time_extensions SET acknowledged = 1 WHERE identifier = ?').run(identifier);
  res.json({ success: true });
});

app.post('/api/time-extension', (req, res) => {
  const { identifier, minutesAdded, reason } = req.body;
  if (!identifier || !minutesAdded) {
    return res.status(400).json({ error: 'identifier and minutesAdded required' });
  }
  db.prepare(`
    INSERT INTO time_extensions (identifier, minutesAdded, reason, timestamp, acknowledged)
    VALUES (?, ?, ?, ?, 0)
  `).run(identifier, minutesAdded, reason ?? '', new Date().toISOString());
  res.json({ success: true });
});

app.post('/api/messages', (req, res) => {
  const { sender, receiver, body } = req.body;
  if (!sender || !receiver || !body) {
    return res.status(400).json({ error: 'sender, receiver and body are required' });
  }
  db.prepare(`
    INSERT INTO messages (sender, receiver, body, timestamp, read)
    VALUES (?, ?, ?, ?, 0)
  `).run(sender, receiver, body, new Date().toISOString());
  res.json({ success: true });
});

app.get('/api/messages/:sender/:receiver', (req, res) => {
  const { sender, receiver } = req.params;
  const msgs = db.prepare(`
    SELECT sender, receiver, body, timestamp, read
    FROM messages
    WHERE (sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?)
    ORDER BY timestamp ASC
  `).all(sender, receiver, receiver, sender);
  res.json(msgs);
});

app.post('/api/messages/mark-read', (req, res) => {
  const { sender, receiver } = req.body;
  if (!sender || !receiver) {
    return res.status(400).json({ error: 'sender and receiver required' });
  }
  db.prepare('UPDATE messages SET read = 1 WHERE sender = ? AND receiver = ?').run(sender, receiver);
  res.json({ success: true });
});

app.listen(PORT, () => {
  console.log(`Backend server running on http://localhost:${PORT}`);
});
