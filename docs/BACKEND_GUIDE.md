# Quiz App - Backend Integration

## Running the Application

### 1. Install Dependencies
```bash
npm install
```

### 2. Start Backend Server (Terminal 1)
```bash
npm run server
```
The backend will run on `http://localhost:3001`

### 3. Start Frontend (Terminal 2)
```bash
npm run dev
```
The frontend will run on `http://localhost:5173`

## Backend API Endpoints

### Question Count
- `GET /api/question-count` - Get current question count
- `POST /api/question-count` - Set question count (admin only)

### Sessions
- `GET /api/sessions` - Get all quiz sessions
- `POST /api/sessions` - Save quiz session

### Time Extensions
- `GET /api/time-extension/:identifier` - Get pending extension
- `POST /api/time-extension` - Add time extension
- `POST /api/time-extension/acknowledge` - Mark extension as acknowledged

### Messages
- `POST /api/messages` - Send message
- `GET /api/messages/:sender/:receiver` - Get conversation
- `POST /api/messages/mark-read` - Mark messages as read

## Data Storage

The backend uses a `backend-data.json` file to persist:
- Default question count (set by admin)
- All quiz sessions
- Time extensions
- Messages between admin and participants

This file is created automatically on first run.
