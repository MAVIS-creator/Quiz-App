import express from 'express';
import cors from 'cors';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import initSqlJs from 'sql.js';
import { createServer } from 'http';
import { Server as SocketServer } from 'socket.io';
import multer from 'multer';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3001;
const uploadDir = path.join(__dirname, 'uploads/evidence');

// Ensure upload directory exists
if (!fs.existsSync(uploadDir)) {
  fs.mkdirSync(uploadDir, { recursive: true });
}

// Setup multer for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadDir);
  },
  filename: (req, file, cb) => {
    const ext = file.mimetype.split('/')[1];
    cb(null, `${Date.now()}_${Math.random().toString(36).slice(2)}.${ext}`);
  }
});
const upload = multer({ storage, limits: { fileSize: 10 * 1024 * 1024 } });

// HTTP and WebSocket setup
const httpServer = createServer(app);
const io = new SocketServer(httpServer, {
  cors: { origin: '*', methods: ['GET', 'POST'] }
});

app.use(cors());
app.use(express.json({ limit: '5mb' }));

// In-memory violation tracking (per exam session)
const violations = new Map();
const activeStudents = new Map();

let db;
const dbPath = path.join(__dirname, 'quiz.db');

// Helper to save DB to file
const saveDB = () => {
  const data = db.export();
  const buffer = Buffer.from(data);
  fs.writeFileSync(dbPath, buffer);
};

// Helper to read DB from file
const loadDB = async (SQL) => {
  if (fs.existsSync(dbPath)) {
    const data = fs.readFileSync(dbPath);
    return new SQL.Database(new Uint8Array(data));
  }
  return new SQL.Database();
};

// Initialize database
const SQL = await initSqlJs();
db = await loadDB(SQL);

// Create tables
db.run(`
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
    questionCount INTEGER,
    examMinutes INTEGER
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
`);

saveDB();

// Simple migrations
try {
  db.run('ALTER TABLE sessions ADD COLUMN examMinutes INTEGER');
} catch (err) {
  // ignore if exists
}

// Seed default question count
const configResult = db.exec('SELECT value FROM config WHERE key = ?', ['question_count']);
if (configResult.length === 0) {
  db.run('INSERT INTO config (key, value) VALUES (?, ?)', ['question_count', '40']);
  saveDB();
}

// Seed default exam minutes
const examMinutesResult = db.exec('SELECT value FROM config WHERE key = ?', ['exam_minutes']);
if (examMinutesResult.length === 0) {
  db.run('INSERT INTO config (key, value) VALUES (?, ?)', ['exam_minutes', '60']);
  saveDB();
}

const getQuestionCount = () => {
  const result = db.exec('SELECT value FROM config WHERE key = ?', ['question_count']);
  if (result.length > 0 && result[0].values.length > 0) {
    return Number(result[0].values[0][0]);
  }
  return 40;
};

const getExamMinutes = () => {
  const result = db.exec('SELECT value FROM config WHERE key = ?', ['exam_minutes']);
  if (result.length > 0 && result[0].values.length > 0) {
    return Number(result[0].values[0][0]);
  }
  return 60;
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
  const result = db.exec(
    `SELECT minutesAdded, reason, timestamp, acknowledged
     FROM time_extensions
     WHERE identifier = ?
     ORDER BY timestamp DESC`,
    [identifier]
  );
  
  if (result.length === 0) return [];
  
  const cols = result[0].columns;
  return result[0].values.map(row => ({
    minutesAdded: row[cols.indexOf('minutesAdded')],
    reason: row[cols.indexOf('reason')],
    timestamp: row[cols.indexOf('timestamp')],
    acknowledged: row[cols.indexOf('acknowledged')]
  }));
};

// In-memory camera snapshots (per session)
const snapshots = new Map();

// Routes
app.get('/api/question-count', (req, res) => {
  res.json({ questionCount: getQuestionCount() });
});

app.post('/api/question-count', (req, res) => {
  const { questionCount } = req.body;
  if (!questionCount || questionCount < 1 || questionCount > 100) {
    return res.status(400).json({ error: 'Invalid question count' });
  }
  
  db.run(
    'INSERT OR REPLACE INTO config (key, value) VALUES (?, ?)',
    ['question_count', String(questionCount)]
  );
  saveDB();
  
  res.json({ success: true, questionCount });
});

// Combined config (question count + exam minutes)
app.get('/api/config', (req, res) => {
  res.json({
    questionCount: getQuestionCount(),
    examMinutes: getExamMinutes(),
  });
});

app.post('/api/config', (req, res) => {
  const { questionCount, examMinutes } = req.body;
  if (!questionCount || questionCount < 1 || questionCount > 100) {
    return res.status(400).json({ error: 'Invalid question count' });
  }
  if (!examMinutes || examMinutes < 5 || examMinutes > 300) {
    return res.status(400).json({ error: 'Invalid exam minutes (5-300 allowed)' });
  }

  db.run('INSERT OR REPLACE INTO config (key, value) VALUES (?, ?)', ['question_count', String(questionCount)]);
  db.run('INSERT OR REPLACE INTO config (key, value) VALUES (?, ?)', ['exam_minutes', String(examMinutes)]);
  saveDB();

  res.json({ success: true, questionCount, examMinutes });
});

app.post('/api/sessions', (req, res) => {
  const session = req.body;
  const identifier = session.matric || session.phone;
  if (!identifier) {
    return res.status(400).json({ error: 'identifier required (matric or phone)' });
  }

  db.run(`
    INSERT OR REPLACE INTO sessions (identifier, matric, phone, name, startTime, answers, questionTimings, violations, submitted, submittedAt, lastSaved, questionCount, examMinutes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `, [
    identifier,
    session.matric ?? null,
    session.phone ?? null,
    session.name ?? '',
    session.startTime ?? new Date().toISOString(),
    serialize(session.answers),
    serialize(session.questionTimings),
    session.violations ?? 0,
    session.submitted ? 1 : 0,
    session.submittedAt ?? null,
    session.lastSaved ?? new Date().toISOString(),
    session.questionCount ?? null,
    session.examMinutes ?? null
  ]);

  saveDB();
  res.json({ success: true });
});

app.get('/api/sessions', (req, res) => {
  const result = db.exec('SELECT * FROM sessions');
  
  if (result.length === 0) {
    return res.json([]);
  }

  const cols = result[0].columns;
  const sessions = result[0].values.map(row => {
    const identifier = row[cols.indexOf('identifier')];
    return {
      matric: row[cols.indexOf('matric')],
      phone: row[cols.indexOf('phone')],
      name: row[cols.indexOf('name')],
      startTime: row[cols.indexOf('startTime')],
      answers: deserialize(row[cols.indexOf('answers')]),
      questionTimings: deserialize(row[cols.indexOf('questionTimings')]),
      violations: row[cols.indexOf('violations')] ?? 0,
      submitted: !!row[cols.indexOf('submitted')],
      submittedAt: row[cols.indexOf('submittedAt')],
      lastSaved: row[cols.indexOf('lastSaved')],
      questionCount: row[cols.indexOf('questionCount')] ?? undefined,
      examMinutes: row[cols.indexOf('examMinutes')] ?? undefined,
      timeExtensions: attachExtensions(identifier)
    };
  });

  res.json(sessions);
});

app.get('/api/time-extension/:identifier', (req, res) => {
  const { identifier } = req.params;
  const result = db.exec(`
    SELECT minutesAdded, reason, timestamp, acknowledged
    FROM time_extensions
    WHERE identifier = ? AND acknowledged = 0
    ORDER BY timestamp DESC
    LIMIT 1
  `, [identifier]);

  if (result.length === 0 || result[0].values.length === 0) {
    return res.json(null);
  }

  const cols = result[0].columns;
  const row = result[0].values[0];
  res.json({
    minutesAdded: row[cols.indexOf('minutesAdded')],
    reason: row[cols.indexOf('reason')],
    timestamp: row[cols.indexOf('timestamp')],
    acknowledged: row[cols.indexOf('acknowledged')]
  });
});

app.post('/api/time-extension/acknowledge', (req, res) => {
  const { identifier } = req.body;
  if (!identifier) return res.status(400).json({ error: 'identifier required' });
  
  db.run('UPDATE time_extensions SET acknowledged = 1 WHERE identifier = ?', [identifier]);
  saveDB();
  
  res.json({ success: true });
});

app.post('/api/time-extension', (req, res) => {
  const { identifier, minutesAdded, reason } = req.body;
  if (!identifier || !minutesAdded) {
    return res.status(400).json({ error: 'identifier and minutesAdded required' });
  }
  
  db.run(`
    INSERT INTO time_extensions (identifier, minutesAdded, reason, timestamp, acknowledged)
    VALUES (?, ?, ?, ?, 0)
  `, [identifier, minutesAdded, reason ?? '', new Date().toISOString()]);

  saveDB();
  res.json({ success: true });
});

app.post('/api/messages', (req, res) => {
  const { sender, receiver, body } = req.body;
  if (!sender || !receiver || !body) {
    return res.status(400).json({ error: 'sender, receiver and body are required' });
  }
  
  db.run(`
    INSERT INTO messages (sender, receiver, body, timestamp, read)
    VALUES (?, ?, ?, ?, 0)
  `, [sender, receiver, body, new Date().toISOString()]);

  saveDB();
  res.json({ success: true });
});

app.get('/api/messages/:sender/:receiver', (req, res) => {
  const { sender, receiver } = req.params;
  const result = db.exec(`
    SELECT sender, receiver, body, timestamp, read
    FROM messages
    WHERE (sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?)
    ORDER BY timestamp ASC
  `, [sender, receiver, receiver, sender]);

  if (result.length === 0) {
    return res.json([]);
  }

  const cols = result[0].columns;
  const msgs = result[0].values.map(row => ({
    sender: row[cols.indexOf('sender')],
    receiver: row[cols.indexOf('receiver')],
    body: row[cols.indexOf('body')],
    timestamp: row[cols.indexOf('timestamp')],
    read: !!row[cols.indexOf('read')]
  }));

  res.json(msgs);
});

app.post('/api/messages/mark-read', (req, res) => {
  const { sender, receiver } = req.body;
  if (!sender || !receiver) {
    return res.status(400).json({ error: 'sender and receiver required' });
  }
  
  db.run('UPDATE messages SET read = 1 WHERE sender = ? AND receiver = ?', [sender, receiver]);
  saveDB();
  
  res.json({ success: true });
});

// Camera snapshot upload
app.post('/api/snapshot', (req, res) => {
  const { identifier, image } = req.body;
  if (!identifier || !image) {
    return res.status(400).json({ error: 'identifier and image required' });
  }

  // Basic size guard (image should be data URL string)
  if (typeof image !== 'string' || image.length > 500_000) {
    return res.status(400).json({ error: 'image too large or invalid' });
  }

  snapshots.set(identifier, { image, timestamp: new Date().toISOString() });
  res.json({ success: true });
});

// Latest camera snapshot fetch
app.get('/api/snapshot/:identifier', (req, res) => {
  const snap = snapshots.get(req.params.identifier);
  if (!snap) return res.json({ image: null, timestamp: null });
  res.json(snap);
});

// All snapshots (for proctor grid)
app.get('/api/snapshots', (req, res) => {
  const all = Array.from(snapshots.entries()).map(([identifier, snap]) => ({
    identifier,
    image: snap.image,
    timestamp: snap.timestamp,
  }));
  res.json(all);
});

app.listen(PORT, () => {
  console.log(`Backend server running on http://localhost:${PORT}`);
});

// Socket.io events
io.on('connection', (socket) => {
  console.log(`Student connected: ${socket.id}`);

  socket.on('student_join', (data) => {
    const identifier = data.identifier || socket.id;
    activeStudents.set(identifier, { socketId: socket.id, joinedAt: new Date() });
    violations.set(identifier, []);
  });

  socket.on('violation_alert', (data) => {
    const identifier = data.identifier;
    if (violations.has(identifier)) {
      violations.get(identifier).push({
        type: data.violationType,
        timestamp: data.timestamp,
        severity: data.severity,
        evidence: data.evidence
      });
    }

    // Broadcast to admin dashboard
    io.to('admin').emit('violation_update', {
      identifier,
      violations: violations.get(identifier) || []
    });
  });

  socket.on('punishment_command', (data) => {
    const targetSocket = activeStudents.get(data.identifier)?.socketId;
    if (targetSocket) {
      io.to(targetSocket).emit('receive_punishment', {
        type: data.punishmentType,
        value: data.value,
        message: data.message
      });
    }
  });

  socket.on('admin_join', () => {
    socket.join('admin');
    socket.emit('active_students', Array.from(activeStudents.entries()).map(([id, data]) => ({
      identifier: id,
      violations: violations.get(id) || []
    })));
  });

  socket.on('disconnect', () => {
    for (const [id, data] of activeStudents) {
      if (data.socketId === socket.id) {
        activeStudents.delete(id);
        violations.delete(id);
      }
    }
  });
});

// REST API for evidence upload
app.post('/api/evidence/upload', upload.single('file'), (req, res) => {
  if (!req.file) {
    return res.status(400).json({ error: 'No file provided' });
  }

  res.json({
    success: true,
    filename: req.file.filename,
    path: `/evidence/${req.file.filename}`,
    size: req.file.size
  });
});

// Serve uploaded evidence
app.use('/evidence', express.static(uploadDir));

// Get all violations for admin
app.get('/api/violations', (req, res) => {
  const allViolations = Array.from(violations.entries()).map(([id, vios]) => ({
    identifier: id,
    violationCount: vios.length,
    violations: vios
  }));
  res.json(allViolations);
});

// Start server with simple retry if port is in use
const startServer = (port, attempt = 0) => {
  httpServer.listen(port, () => {
    console.log(`Backend server running on http://localhost:${port}`);
    console.log(`WebSocket server ready for proctoring`);
  }).on('error', (err) => {
    if (err.code === 'EADDRINUSE' && attempt < 3) {
      const nextPort = Number(port) + 1;
      console.warn(`Port ${port} in use. Retrying on ${nextPort}...`);
      setTimeout(() => startServer(nextPort, attempt + 1), 500);
    } else {
      console.error('Failed to start server:', err);
      process.exit(1);
    }
  });
};

startServer(process.env.PORT || PORT);
