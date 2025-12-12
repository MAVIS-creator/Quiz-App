# Quiz App - Quick Reference Guide

## 🌐 App URLs

| Page | URL | Purpose |
|------|-----|---------|
| Login | http://localhost:5173/ | Participant roster login |
| Quiz | http://localhost:5173/quiz | Take the 40-question exam |
| Admin | http://localhost:5173/admin | Monitor all participants |

---

## 👤 Sample Login Credentials

### Participants (use ANY from roster):
- **Matric Number**: 2024001
- **Phone**: 1234567890

Check [src/data/participants.ts](src/data/participants.ts) for full list.

### Admin Panel:
- **Password**: `admin123`

---

## ✨ Key Features at a Glance

### 🎓 Participant Features
✅ Matric or phone number login  
✅ 40 shuffled questions from 100  
✅ 60-minute countdown timer  
✅ Real-time progress tracking  
✅ Anti-cheating (tab switch detection)  
✅ Auto-submit on 3 violations  
✅ Time extension notifications  
✅ Question navigator grid  

### 👨‍💼 Admin Features
✅ Password-protected dashboard  
✅ **Live participant faces (colored avatars)**  
✅ **One-on-one messaging** (click avatar)  
✅ Real-time progress monitoring  
✅ Accuracy & violation tracking  
✅ Time extension management  
✅ Question markdown import  
✅ Summary analytics cards  

---

## 💬 How to Message a Participant

1. Login to Admin (`/admin`) with password: `admin123`
2. Look at the **Live Participant Monitor** table
3. **Click the colored avatar circle** next to any participant name
4. **Chat panel opens on the right**
5. Type your message
6. Press **Enter** or click **Send button**
7. Full conversation history visible

---

## ⏱️ How to Grant Time Extensions

1. In Admin dashboard, find the participant
2. Click **"Add Time"** button
3. Enter:
   - **Additional Minutes** (1-60)
   - **Reason** (e.g., "Technical issue")
4. Click **"Add Time"** in dialog
5. Participant receives **instant popup notification**
6. Timer automatically updates

---

## 📱 Participant's View During Quiz

```
┌─────────────────────────────────────────────┐
│ Timer: 45:30  |  Violations: 0/3            │
├─────────────────────────────────────────────┤
│                                              │
│ Question 12 of 40  [██████░░░░░░░░░░] 30%  │
│                                              │
│ 📋 What is the Box Model?                  │
│                                              │
│ ○ Content, Padding, Border, Margin        │
│ ○ Width, Height, Color, Font               │
│ ○ Display, Position, Flex, Grid            │
│ ○ Animation, Transition, Transform         │
│                                              │
│ [Previous] ───────── [Next]                │
│                                              │
│ Question Navigator:                        │
│ [1][2][3][4][5][6][7][8][9][10][11][12].. │
│                                              │
└─────────────────────────────────────────────┘
```

---

## 👁️ Admin's View of Participants

```
┌───────────────────────────────────────────────────────┐
│ Live Participant Monitor                              │
├───┬──────────────┬──────────┬──────────┬──────────────┤
│ 🔵│ John Doe     │ ████░░░░ │ 75%      │ Add Time     │
│ 🟡│ Jane Smith   │ ██████░░ │ 85%      │ Add Time     │
│ 🔴│ Bob Johnson  │ ████████ │ 92%      │ Done         │
│ 🟢│ Alice Wong   │ ███░░░░░ │ 60%      │ Message      │
└───┴──────────────┴──────────┴──────────┴──────────────┘

Click avatar → Opens chat panel
Click "Add Time" → Time extension dialog
Click "Message" → Messaging panel
```

---

## 🎨 Footer Design

Every page includes:
```
© 2025 MAVIS. All rights reserved.
         ^^^^
    (Yellow→Blue gradient)
```

---

## 📂 Project Files

### Core Pages
- `/src/pages/Login.tsx` - Roster login
- `/src/pages/Quiz.tsx` - Quiz interface  
- `/src/pages/Admin.tsx` - Admin dashboard

### Utilities
- `/src/utils/sessionStore.ts` - Quiz data storage
- `/src/utils/messaging.ts` - Message storage & retrieval

### Data
- `/src/data/questions.ts` - 100 questions
- `/src/data/participants.ts` - 15 roster entries

### Documentation
- `/README.md` - Complete usage guide
- `/IMPLEMENTATION_SUMMARY.md` - Feature checklist

---

## 🔧 Development Commands

```bash
# Start development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Check types
npm run type-check
```

---

## 📊 Data Stored Locally

Browser localStorage keys:
- `quiz_sessions` - Quiz responses & timing
- `quiz_messages` - Admin-participant messages
- `time_extensions` - Time extension records

**No server required** - all data is local to browser

---

## 🚨 Important Notes

⚠️ **Violations System**
- Switching tabs = 1 violation
- 3 violations = auto-submit quiz
- Admin can see violation count

⚠️ **Session Persistence**
- Quiz auto-saves every 10 seconds
- Refreshing page recovers progress
- Data clears after submit

⚠️ **Admin Features**
- Only password `admin123` works
- Messaging needs localStorage enabled
- Dashboard updates every 3 seconds

---

## ✅ Quality Assurance

Tested Features:
- ✅ Login validation
- ✅ Timer countdown
- ✅ Question shuffling
- ✅ Answer tracking
- ✅ Time extensions
- ✅ Admin messaging
- ✅ Avatar monitoring
- ✅ Session recovery
- ✅ Violation detection
- ✅ Footer display

---

## 🆘 Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| Can't login | Check if in roster (participants.ts) |
| Timer stuck | Refresh page |
| Messages not showing | Check localStorage is enabled |
| Admin password wrong | Must be exactly `admin123` |
| Avatars not colored | Try hard refresh (Ctrl+Shift+R) |

---

## 🎯 Next Steps

To customize:

1. **Add more questions**: Edit `/src/data/questions.ts`
2. **Add more participants**: Edit `/src/data/participants.ts`
3. **Change admin password**: Edit `/src/pages/Admin.tsx` line 42
4. **Change quiz duration**: Edit `/src/pages/Quiz.tsx` line 19
5. **Change app colors**: Edit Tailwind classes in components

---

**Made with ❤️ by MAVIS** | © 2025
