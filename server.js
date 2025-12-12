import express from 'express';
import cors from 'cors';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3001;

app.use(cors());
app.use(express.json());

const DATA_FILE = path.join(__dirname, 'backend-data.json');

// Initialize data file if it doesn't exist
if (!fs.existsSync(DATA_FILE)) {
  fs.writeFileSync(DATA_FILE, JSON.stringify({
    questionCount: 40,
    sessions: []
  }, null, 2));
}

// Helper to read data
const readData = () => {
  try {
    const data = fs.readFileSync(DATA_FILE, 'utf8');
    return JSON.parse(data);
  } catch (error) {
    return { questionCount: 40, sessions: [] };
  }
};

// Helper to write data
const writeData = (data) => {
  fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2));
};

// Get question count
app.get('/api/question-count', (req, res) => {
  const data = readData();
  res.json({ questionCount: data.questionCount });
});

// Set question count (admin only)
app.post('/api/question-count', (req, res) => {
  const { questionCount } = req.body;
  
  if (!questionCount || questionCount < 1 || questionCount > 100) {
    return res.status(400).json({ error: 'Invalid question count' });
  }
  
  const data = readData();
  data.questionCount = questionCount;
  writeData(data);
  
  res.json({ success: true, questionCount });
});

// Save session
app.post('/api/sessions', (req, res) => {
  const sessionData = req.body;
  const data = readData();
  
  // Find and update existing session or add new one
  const existingIndex = data.sessions.findIndex(s => 
    (s.matric && s.matric === sessionData.matric) || s.phone === sessionData.phone
  );
  
  if (existingIndex >= 0) {
    data.sessions[existingIndex] = sessionData;
  } else {
    data.sessions.push(sessionData);
  }
  
  writeData(data);
  res.json({ success: true });
});

// Get all sessions
app.get('/api/sessions', (req, res) => {
  const data = readData();
  res.json(data.sessions);
});

// Get time extension for a participant
app.get('/api/time-extension/:identifier', (req, res) => {
  const { identifier } = req.params;
  const data = readData();
  
  const session = data.sessions.find(s => 
    s.matric === identifier || s.phone === identifier
  );
  
  const extension = session?.timeExtensions?.find(te => !te.acknowledged);
  res.json(extension || null);
});

// Acknowledge time extension
app.post('/api/time-extension/acknowledge', (req, res) => {
  const { identifier } = req.body;
  const data = readData();
  
  const session = data.sessions.find(s => 
    s.matric === identifier || s.phone === identifier
  );
  
  if (session?.timeExtensions) {
    session.timeExtensions = session.timeExtensions.map(te => ({
      ...te,
      acknowledged: true
    }));
    writeData(data);
  }
  
  res.json({ success: true });
});

// Add time extension
app.post('/api/time-extension', (req, res) => {
  const { identifier, minutesAdded, reason } = req.body;
  const data = readData();
  
  const session = data.sessions.find(s => 
    s.matric === identifier || s.phone === identifier
  );
  
  if (session) {
    if (!session.timeExtensions) {
      session.timeExtensions = [];
    }
    
    session.timeExtensions.push({
      minutesAdded,
      reason,
      timestamp: new Date().toISOString(),
      acknowledged: false
    });
    
    writeData(data);
    res.json({ success: true });
  } else {
    res.status(404).json({ error: 'Session not found' });
  }
});

// Messages endpoints
app.post('/api/messages', (req, res) => {
  const message = req.body;
  const data = readData();
  
  if (!data.messages) {
    data.messages = [];
  }
  
  data.messages.push({
    ...message,
    timestamp: new Date().toISOString(),
    read: false
  });
  
  writeData(data);
  res.json({ success: true });
});

app.get('/api/messages/:sender/:receiver', (req, res) => {
  const { sender, receiver } = req.params;
  const data = readData();
  
  const messages = (data.messages || []).filter(m => 
    (m.sender === sender && m.receiver === receiver) ||
    (m.sender === receiver && m.receiver === sender)
  );
  
  res.json(messages);
});

app.post('/api/messages/mark-read', (req, res) => {
  const { sender, receiver } = req.body;
  const data = readData();
  
  if (data.messages) {
    data.messages.forEach(m => {
      if (m.sender === sender && m.receiver === receiver) {
        m.read = true;
      }
    });
    writeData(data);
  }
  
  res.json({ success: true });
});

app.listen(PORT, () => {
  console.log(`Backend server running on http://localhost:${PORT}`);
});
