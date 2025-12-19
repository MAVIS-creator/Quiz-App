# 🎓 Quiz App - Complete Guide

## 🚀 Quick Start

### Step 1: Ensure XAMPP is Running
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Make sure both services are running (green indicators)

### Step 2: Database Setup

#### Option A: Automatic Setup (Recommended)
1. Open your browser and go to: `http://localhost/Quiz-App/setup.php`
2. Click "Run Database Setup" button
3. Wait for the success message
4. Click "Go to Login Page"

#### Option B: Manual Setup
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on the "SQL" tab
3. Copy all content from `setup_database.sql`
4. Paste and click "Go" to execute
5. Navigate to: `http://localhost/Quiz-App/init_db.php` to seed questions

### Step 3: Access the Application
1. Go to: `http://localhost/Quiz-App/`
2. You'll be redirected to the login page

---

## 👥 Test Account

**For Testing Purposes:**
- **Matric Number:** `TEST001`
- **Name:** Test Student

---

## 📋 Authorized Students List

The following students are authorized to take the quiz:

| S/N | Name | Matric No | Phone No |
|-----|------|-----------|----------|
| 1 | SANNI Olayinka | 2025000831 | 8084343242 |
| 2 | Shobodun Faridat Tolulope | 2025002070 | 9128823922 |
| 3 | DIPEOLU AMAL TITILOPE | 2025000776 | 8063966934 |
| 4 | Jamiu Abdullahi Olalekan | NIL | 7073247811 |
| 5 | LIGALI OLUWASEGUN OLUMAYOWA | 2025003523 | 8126479848 |
| 6 | Adekanye seyi semilore | 2025002782 | 9115660920 |
| 7 | Adepetu Peter taiwo | 2025007581 | 8077923006 |
| 8 | Taofeeq uthman Timilehin | 2025001994 | 8122069891 |
| 9 | Oluwafemi Daniel Iyiola | 2025007041 | 8128711370 |
| 10 | Alagbe Michael Kehinde | 2025003519 | 8128972860 |
| 11 | Ojeabi-Champion Praise Erinayo | 2025006425 | 9069380243 |
| 12 | Obiye Isaac Osareemen | 2025003870 | 9114220817 |
| 13 | ADEMOLA BOLUWATIFE JEREMIAH | 2025003210 | 8025073532 |
| 14 | Olatunji Testimony Israel | 2025002074 | 9015037316 |
| 15 | Test Student (TEST ONLY) | TEST001 | 1234567890 |

---

## ✨ Features Implemented

### 🎨 Modern UI/UX (React-Inspired Design)
- ✅ Beautiful gradient backgrounds
- ✅ Smooth animations and transitions
- ✅ Glass-morphism effects
- ✅ Modern card-based layouts
- ✅ Floating elements and micro-interactions

### 📱 Fully Responsive Design
- ✅ Mobile-friendly (phones)
- ✅ Tablet-optimized
- ✅ Desktop-ready
- ✅ Adaptive layouts for all screen sizes

### 🔐 Authentication System
- ✅ Student login with matric number validation
- ✅ Only authorized students can access
- ✅ Session management
- ✅ Secure logout

### 🎯 Quiz Features
- ✅ 40 randomized questions
- ✅ 60-minute timer with countdown
- ✅ Auto-save progress every 5 seconds
- ✅ Camera proctoring (snapshots every 2 seconds)
- ✅ Tab-switch detection (3 violations = auto-submit)
- ✅ Real-time answer tracking
- ✅ Progress indicator

### 🔔 SweetAlert2 Integration
- ✅ Beautiful styled alerts
- ✅ Custom purple gradient theme
- ✅ Smooth animations
- ✅ User-friendly confirmations
- ✅ Loading states

### 📊 Results Page
- ✅ Detailed score breakdown
- ✅ Visual score circle with percentage
- ✅ Correct/Wrong answer statistics
- ✅ Interactive charts (Chart.js)
- ✅ Question-by-question review
- ✅ Category-wise analysis

### 🎓 Dashboard
- ✅ Student information display
- ✅ Quiz instructions
- ✅ Easy navigation
- ✅ Modern card-based layout

---

## 📂 File Structure

```
Quiz-App/
├── login.php              # Login page with student authentication
├── dashboard.php          # Student dashboard
├── quiz_new.php           # Main quiz interface (NEW)
├── result.php             # Results display page
├── logout.php             # Logout handler
├── setup.php              # Database setup interface
├── setup_db_ajax.php      # Automatic database setup
├── index.php              # Redirects to login
├── db.php                 # Database connection
├── init_db.php            # Question seeding script
├── setup_database.sql     # Database schema
├── questions.md           # Question bank
├── .htaccess              # Apache rewrite rules
├── api/
│   ├── sessions.php       # Session management API
│   ├── violations.php     # Violation tracking API
│   ├── snapshot.php       # Camera snapshot API
│   └── ...
└── uploads/
    └── evidence/          # Camera snapshots storage
```

---

## 🎯 How to Use

### For Students:

1. **Login**
   - Go to `http://localhost/Quiz-App/`
   - Enter your matriculation number
   - Click "Login to Quiz"

2. **Dashboard**
   - Review your information
   - Read quiz instructions carefully
   - Click "Start Quiz Now" when ready

3. **Taking the Quiz**
   - Allow camera access when prompted
   - Answer all questions within 60 minutes
   - Don't switch tabs (max 3 violations)
   - Your progress is auto-saved
   - Click "Submit Quiz" when done

4. **View Results**
   - Automatic redirect after submission
   - See your score and statistics
   - Review detailed answers
   - Check performance charts

---

## 🛠️ Technical Details

### Technologies Used:
- **Backend:** PHP 7.4+, MySQL
- **Frontend:** HTML5, Tailwind CSS, JavaScript
- **Alerts:** SweetAlert2
- **Charts:** Chart.js
- **Server:** Apache (XAMPP)

### Database Tables:
- `config` - Quiz configuration
- `questions` - Question bank
- `sessions` - Student quiz sessions
- `violations` - Tab-switch violations
- `snapshots` - Camera captures
- `messages` - Proctor messaging

### Security Features:
- Session-based authentication
- SQL injection prevention (prepared statements)
- XSS protection
- Camera monitoring
- Tab-switch detection
- Right-click and copy prevention

---

## 🐛 Troubleshooting

### Database Connection Error:
1. Check if MySQL is running in XAMPP
2. Verify database credentials in `db.php`
3. Run setup again: `http://localhost/Quiz-App/setup.php`

### 404 Not Found Error:
1. Ensure you're using the correct URL: `http://localhost/Quiz-App/`
2. Check if Apache is running
3. Verify `.htaccess` file exists

### Camera Not Working:
1. Grant camera permissions in browser
2. Use HTTPS or localhost (browsers block camera on HTTP)
3. Check if camera is available and not used by another app

### Questions Not Loading:
1. Run `init_db.php` to seed questions
2. Check if `questions.md` exists
3. Verify database connection

---

## 📞 Support

**Tutor:** Akintunde Dolapo Elisha - 07082184560

For technical issues, check:
1. XAMPP services are running
2. Database is properly set up
3. All files are in the correct directory
4. Browser console for JavaScript errors

---

## 🎉 Features Highlight

### ✨ What Makes This Special:

1. **Modern Design** - React-inspired UI with smooth animations
2. **Fully Responsive** - Works on all devices seamlessly
3. **SweetAlert Integration** - Beautiful, styled alerts throughout
4. **Comprehensive Results** - Detailed analytics with charts
5. **Secure Proctoring** - Camera monitoring and violation tracking
6. **Auto-Save** - Never lose your progress
7. **Easy Setup** - One-click database installation

---

## 📝 Notes

- Test account (TEST001) is for testing purposes only
- Students must be on the authorized list to access the quiz
- Quiz auto-submits when time expires or after 3 violations
- All quiz sessions are automatically saved and can be reviewed
- Camera snapshots are stored for proctoring verification

---

**Built with ❤️ for Web Development Students 100 Level**
