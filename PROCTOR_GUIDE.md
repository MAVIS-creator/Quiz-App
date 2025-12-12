# Smart Proctor System - Implementation Guide

## Overview

The Smart Proctor system uses **client-side AI monitoring** to detect integrity violations during quizzes with minimal bandwidth overhead.

### Architecture

```
Student (React App)
  ├─ AI Video Monitor (COCO-SSD)
  ├─ Audio Detector (Web Audio API)
  └─ Socket.io Client → Backend

Backend (Node.js + Socket.io)
  ├─ Real-time Violation Hub
  ├─ Evidence Storage (/uploads)
  └─ Punishment Command Handler

Admin (React Dashboard)
  ├─ Proctor Command Center
  ├─ Live Violation Grid
  └─ Evidence Review + Actions
```

## Features

### 1. Student Side ("The Silent Spy")

#### Video Monitor
- **Tech**: TensorFlow.js (COCO-SSD)
- **Detects**: 
  - Cell phones
  - Books/notebooks
  - Second person in frame
  - Head tilt (looking away from screen)
  - Absence from frame

#### 3-Second Rule
- Only triggers alert if violation lasts 3+ seconds
- Prevents false positives (sneezing, scratching)

#### Audio Monitor
- **Tech**: Web Audio API + MediaRecorder
- **Detects**: 
  - Whispering (volume 15-50)
  - Speaking (volume > 50)
- **Action**: Auto-records last 10 seconds as evidence

### 2. Real-Time Communication

#### Socket.io Channels
```
violation_alert
├─ From: Student
├─ Payload: { violationType, timestamp, severity, evidence }
└─ To: Admin Dashboard

issue_punishment
├─ From: Admin
├─ Types: warn | deduct_time | deduct_points | kick
└─ To: Student
```

### 3. Admin Dashboard (/proctor)

#### Live Grid
- Status indicators for each student
- 🟢 Green: All clear
- 🟡 Yellow: Warning violation
- 🔴 Red: Alert violation

#### Actions
- ⚠️ **Warn**: Yellow flash + message
- ⏳ **-5 Min**: Deduct time
- 📉 **-10 Pts**: Deduct points
- 🚫 **Kick**: Disqualify student

#### Evidence Review
- Violation timeline with timestamps
- Screenshot evidence for each violation
- Audio clips (when whispering/speaking)

## Setup Instructions

### 1. Backend Dependencies (Already Installed)
```bash
npm install @tensorflow/tfjs @tensorflow-models/coco-ssd socket.io socket.io-client multer
```

### 2. Start the Backend
```bash
npm run dev
# or
node server.js
```

The server will start on `http://localhost:3001` with Socket.io enabled.

### 3. Access Points
- **Student Quiz**: `http://localhost:5173/quiz`
- **Admin Control**: `http://localhost:5173/admin`
- **Proctor Dashboard**: `http://localhost:5173/proctor`

## How It Works

### Student Takes Quiz
1. Quiz loads, Smart Proctor initializes camera/microphone
2. Student is asked to grant permissions
3. AI models run locally on student's computer
4. Every 1 second:
   - Video frame analyzed for objects, head position
   - Audio level checked
   - If violation detected → counter increments
   - If counter >= 3 (3+ seconds) → alert sent to admin

### Admin Monitors
1. Open Proctor Dashboard (`/proctor`)
2. See live grid of all students
3. Click student tile to view violation details
4. Issue commands: warn, deduct time, deduct points, or kick
5. Student receives notification immediately

### Student Receives Punishment
- **Warn**: Yellow flash, message appears
- **Deduct Time**: Timer reduced instantly
- **Deduct Points**: Score reduced instantly
- **Kick**: Redirect to home, session invalidated

## File Structure

```
src/
├─ utils/
│  └─ proctoring.ts          # AI detection logic
├─ hooks/
│  └─ useSmartProctor.ts     # Socket + monitoring hook
├─ pages/
│  ├─ Quiz.tsx               # Student quiz (integrated)
│  ├─ Admin.tsx              # Admin panel
│  └─ ProctorDashboard.tsx   # NEW: Command center
└─ App.tsx                    # Route: /proctor

server.js                      # Socket.io + violation hub
```

## Key Configuration

### Detection Sensitivity
**File**: `src/utils/proctoring.ts`
```typescript
// 3-second rule (3 checks at 1Hz = 3 seconds)
if (counter >= 3) {
  triggerAlert(violationType);
}

// Audio thresholds
const isWhispering = level > 15 && level <= 50;
const isSpeaking = level > 50;
```

### Violations Tracked
```typescript
type ViolationType = 
  | 'phone'           // Cell phone detected
  | 'book'            // Book/notes detected
  | 'person'          // Second person detected
  | 'looking_away'    // Head tilt > threshold
  | 'no_face'         // User left frame
  | 'speaking'        // Loud talking
  | 'whispering';     // Whisper detected
```

## Bandwidth Optimization

### Why Low-Data?
- No video streaming (saves 2-5 MB/min per student)
- Only snapshots (1 KB JPEG per second)
- Audio only recorded on violation (10 sec = 150 KB)
- Text-based alerts (< 1 KB)

### Comparison
```
Traditional Streaming: 2-5 MB/min per student
Smart Proctor System:  ~60 KB/min per student
Savings: 97%+ reduction
```

## Troubleshooting

### Camera Permission Denied
- Check browser permissions (Chrome: Settings > Privacy > Site Settings > Camera)
- Ensure HTTPS (if deployed; localhost is exempt)

### Audio Not Detecting
- Grant microphone permission
- Check microphone is working (test in browser audio settings)

### Violations Not Appearing
- Open browser console, check for Socket.io connection errors
- Verify backend is running on port 3001
- Check CORS settings if running on different domain

### Socket.io Connection Failed
```
Backend Error: Error: EADDRINUSE: address already in use :::3001
Solution: Kill process on port 3001
  lsof -ti:3001 | xargs kill -9  # macOS/Linux
  netstat -ano | findstr :3001   # Windows
```

## Privacy & Legal Notes

- Inform students that AI proctoring is active
- Store evidence securely
- Delete evidence after exam period (configure in admin)
- Comply with local regulations on recording

## Future Enhancements

- [ ] WebRTC for optional live video verification
- [ ] Facial expression analysis
- [ ] Eye-tracking (gaze detection)
- [ ] Keystroke pattern analysis
- [ ] Network monitoring (tab switches, network loss)
- [ ] PDF report generation
- [ ] Bulk punishment actions
- [ ] Evidence retention/deletion policies
