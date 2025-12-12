# Quiz App

A modern, secure online quiz application built with React, TypeScript, and Tailwind CSS. Features real-time participant monitoring, admin controls, and anti-cheating measures.

## Features

### For Participants
- **Secure Login**: Access via matric number or phone number
- **Live Quiz Interface**: 
  - 40 questions from a pool of 100
  - Shuffled question and option order
  - Real-time timer (60 minutes)
  - Progress tracking
  - Question navigator grid
- **Anti-Cheating Measures**:
  - Tab/window switch detection with violation warnings
  - Auto-submit on 3 violations
  - Session persistence for crash recovery
- **Time Management**:
  - Real-time countdown timer
  - Low time warning (< 5 minutes)
  - Admin can grant time extensions
- **Answer Tracking**: Accuracy and time-per-question analytics

### For Admin
- **Admin Dashboard**: Full participant monitoring at `/admin`
- **Live Participant Monitor**:
  - See all participants with avatar circles
  - Real-time progress, accuracy, and violation tracking
  - Status indicators (Active/Completed)
- **One-on-One Messaging**: 
  - Click participant avatar to open chat panel
  - Send real-time messages to individual participants
  - Full conversation history
- **Time Management**:
  - Add time extensions with reason
  - SweetAlert2 form interface
  - Participants receive smooth popup notification
- **Analytics**:
  - Summary cards (Total, Completed, Active, Violations)
  - Performance metrics per participant
  - Progress bars and accuracy percentages
- **Question Management**:
  - Import questions from Markdown files
  - Auto-parser with JSON preview
- **Admin Password**: `admin123`

## Installation & Setup

### Prerequisites
- Node.js (v16+)
- npm or yarn

### Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd "Quiz App"
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Start development server**
   ```bash
   npm run dev
   ```

4. **Open in browser**
   - Navigate to `http://localhost:5173/`

5. **Build for production**
   ```bash
   npm run build
   ```

## How to Use

### As a Participant

1. **Login Page** (`/`)
   - Enter your **matric number** (if you have one) OR
   - Enter your **phone number** (if you don't have a matric)
   - Click "Start Quiz"
   - Please fill in the required fields

2. **Quiz Page** (`/quiz`)
   - Answer 40 questions within 60 minutes
   - Use the **Question Navigator** grid (bottom) to jump between questions
   - Watch for your **timer** in the top right
   - Each question shows:
     - Category
     - Question text
     - 4 multiple choice options
   - **Don't switch tabs/windows** - violations trigger warnings
   - After 3 violations, quiz auto-submits
   - Use **Previous/Next** buttons to navigate
   - Click **Submit Quiz** when finished or out of time

3. **Time Extension**
   - If admin grants extra time, you'll receive a **SweetAlert popup**
   - Shows minutes added and reason
   - Your timer automatically updates

### As an Admin

1. **Admin Login** (`/admin`)
   - Click "Admin Login"
   - Password: `admin123`

2. **Dashboard Overview**
   - See summary cards: Total participants, Completed, Active, Violations
   - Live participant table with real-time updates

3. **Monitor Participants**
   - **Avatars**: Colored circles show participant faces
   - **Click avatar** to open direct messaging chat
   - View progress bar (% questions answered)
   - See accuracy percentage
   - Check violation count
   - Monitor status (Active/Completed)

4. **Message a Participant**
   - Click any participant's avatar or "Message" button
   - Chat panel opens on the right
   - Type your message and press Enter or click Send
   - See full conversation history
   - Timestamps for each message

5. **Add Time Extension**
   - Click "Add Time" button next to participant name
   - Enter number of minutes (1-60)
   - Provide a reason
   - Participant receives instant SweetAlert notification
   - Their timer updates automatically

6. **Import Questions**
   - Click "Import Questions" button
   - Upload a `.md` (Markdown) file
   - Format example:
     ```
     ## HTML Basics
     1. What is HTML?
        - Markup language
        - Programming language
        - Database language
        - Server language
        Answer: Markup language
     ```
   - Preview parsed JSON
   - Copy to use in codebase

## Project Structure

```
Quiz App/
├── src/
│   ├── components/          # Reusable React components
│   ├── pages/
│   │   ├── Login.tsx        # Login/roster page
│   │   ├── Quiz.tsx         # Participant quiz interface
│   │   └── Admin.tsx        # Admin dashboard
│   ├── data/
│   │   ├── questions.ts     # 100 quiz questions
│   │   └── participants.ts  # 15 roster entries
│   ├── types/
│   │   └── session.ts       # TypeScript type definitions
│   ├── utils/
│   │   ├── sessionStore.ts  # localStorage session management
│   │   └── messaging.ts     # Admin-participant messaging
│   ├── App.tsx              # Main router
│   ├── main.tsx             # Entry point
│   └── index.css            # Tailwind styles
├── package.json
├── tsconfig.json
├── tailwind.config.js
├── vite.config.ts
└── README.md
```

## Technology Stack

- **Frontend**: React 18.3 + TypeScript
- **Bundler**: Vite 6.4
- **Styling**: Tailwind CSS + custom utilities
- **Icons**: Boxicons 2.1
- **Alerts/Modals**: SweetAlert2 11.14
- **Routing**: React Router DOM 7.1
- **Storage**: Browser localStorage

## Key Features Explained

### Session Persistence
- Quiz state saved every 10 seconds
- Automatic recovery on page refresh
- Prevents data loss

### Time Tracking
- Tracks time spent per question
- Calculates average time per question
- Stores question timings for analytics

### Anti-Cheating
- Detects tab/window switches
- 3-strike system auto-submits quiz
- Logs all violations in session data

### Real-time Updates
- Admin dashboard refreshes every 3 seconds
- Live participant status updates
- Messaging works in real-time via localStorage

## Admin Password
**Default Admin Password**: `admin123`

## Data Storage
All data is stored locally in browser's localStorage:
- `quiz_sessions`: Participant quiz data
- `quiz_messages`: Admin-participant messages
- `time_extensions`: Time extension records

## Customization

### Change App Title
Edit in [src/pages/Login.tsx](src/pages/Login.tsx#L44):
```tsx
<h1 className="text-3xl font-bold text-slate-50">Quiz App</h1>
```

### Change Instructions Text
Edit the subtitle in [src/pages/Login.tsx](src/pages/Login.tsx#L47):
```tsx
<p className="mt-2 text-slate-300">Please fill in the required fields</p>
```

### Add/Edit Questions
Edit [src/data/questions.ts](src/data/questions.ts):
```typescript
export const questions: Question[] = [
  {
    id: 1,
    prompt: "Your question?",
    options: ["Option A", "Option B", "Option C", "Option D"],
    answer: "Option A",
    category: "Category Name"
  },
  // ... more questions
];
```

### Add/Edit Participants
Edit [src/data/participants.ts](src/data/participants.ts):
```typescript
export const participants = [
  { name: "John Doe", matric: "2024001", phone: "1234567890" },
  // ... more participants
];
```

### Change Admin Password
In [src/pages/Admin.tsx](src/pages/Admin.tsx#L42):
```typescript
if (password !== 'your-new-password') {
  // Wrong password logic
}
```

### Change Quiz Duration
In [src/pages/Quiz.tsx](src/pages/Quiz.tsx#L19):
```typescript
const [timeLeft, setTimeLeft] = useState(3600); // Change 3600 (60 minutes) to desired seconds
```

## Features Overview

| Feature | Participant | Admin |
|---------|-------------|-------|
| Login | ✅ | ✅ |
| Take Quiz | ✅ | ❌ |
| View Results | ❌ | ✅ |
| Monitor Participants | ❌ | ✅ |
| See Participant Faces | ❌ | ✅ |
| Message Participants | ❌ | ✅ |
| Grant Time Extensions | ❌ | ✅ |
| Import Questions | ❌ | ✅ |
| Track Violations | ❌ | ✅ |

## Troubleshooting

### Can't login
- Ensure you're in the roster (check [src/data/participants.ts](src/data/participants.ts))
- Use correct matric number or phone number
- Phone number is required if matric is null

### Timer not working
- Check browser console for errors
- Ensure JavaScript is enabled
- Try refreshing the page

### Admin can't add time
- Use correct password: `admin123`
- Ensure participant is still active (not submitted)
- Check if browser allows localStorage

### Messages not appearing
- Refresh the admin page
- Check if localStorage is enabled
- Try messaging again

## Browser Support
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)

## License
© 2025 MAVIS. All rights reserved.

## Support
For issues or questions, contact the development team.

---

**Made with ❤️ by MAVIS**
