<div align="center">

# 🎓 Quiz App - Enhanced Edition

### *Advanced Online Quiz System with Smart Proctoring*

[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

*A modern, secure quiz platform with real-time monitoring, admin controls, and anti-cheating measures.*

[Features](#-features) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [Admin Guide](#-admin-guide)

---

</div>

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Quick Start](#-quick-start)
- [Student Experience](#-student-experience)
- [Admin Guide](#-admin-guide)
- [API Endpoints](#-api-endpoints)
- [Documentation](#-documentation)
- [Security](#-security)
- [Support](#-support)

---

## 🌟 Overview

Quiz App is a comprehensive online assessment platform designed for educational institutions. It combines modern web technologies with advanced proctoring features to ensure exam integrity while providing a smooth user experience.

### Key Highlights

- 🔀 **Unique Questions** - Each student gets randomized question order
- 👁️ **Smart Proctoring** - Audio & video monitoring with intelligent triggers
- ⚡ **Real-time Control** - Dynamic time management and instant actions
- 📊 **Analytics** - Comprehensive performance metrics and accuracy tracking
- 💬 **Direct Communication** - Admin-to-student messaging during exams
- 🎨 **Modern UI** - Responsive design with smooth animations

---

## ✨ Features

### 🎯 For Students

<table>
<tr>
<td width="50%">

#### Quiz Experience
- 🔐 **Secure Login** - Authorized student access only
- 🔄 **Shuffled Questions** - Unique order per student
- ⏱️ **Live Timer** - Real-time countdown with adjustments
- 💾 **Auto-save** - Progress saved every 5 seconds
- 📊 **Progress Tracker** - See answered questions count

</td>
<td width="50%">

#### Monitoring & Safety
- 📹 **Smart Snapshots** - Triggered on anomaly detection
- 🔊 **Audio Detection** - Only logs unusual sounds
- 🚫 **Tab Protection** - Auto-submit on violations
- 💬 **Admin Messages** - Receive real-time notifications
- 📈 **Results Page** - Detailed performance analysis

</td>
</tr>
</table>

### 🛡️ For Administrators

<table>
<tr>
<td width="50%">

#### Monitoring & Control
- 📊 **Live Dashboard** - All sessions at a glance
- 👁️ **Proctor View** - Real-time violation tracking
- 📸 **Snapshot Viewer** - Camera feed monitoring
- 📉 **Accuracy Reports** - Performance metrics
- 🎯 **Violation Sorting** - Organized by student

</td>
<td width="50%">

#### Administrative Actions
- ⏰ **Time Control** - Add/subtract time per student
- 📉 **Point Deduction** - Reduce scores for violations
- 🚪 **Boot Out** - Terminate exams instantly
- ❌ **Cancel Exam** - Block student access
- 💬 **Messaging** - Direct student communication
- ⚠️ **Warnings** - Log without penalties

</td>
</tr>
</table>

---

## 🛠️ Tech Stack

### Backend
- **PHP 7.4+** - Server-side logic
- **MySQL 8.0+** - Database management
- **Apache** - Web server (XAMPP)

### Frontend
- **Tailwind CSS** - Modern styling
- **Vanilla JavaScript** - Client-side interactions
- **SweetAlert2** - Beautiful alerts
- **Chart.js** - Data visualization
- **Boxicons** - Icon library

### APIs
- **RESTful** - Clean API architecture
- **JSON** - Data exchange format
- **Web Audio API** - Sound monitoring
- **MediaDevices API** - Camera access

---

## 🚀 Quick Start

### Prerequisites

- XAMPP (Apache + MySQL)
- PHP 7.4 or higher
- Modern web browser

### Installation

```bash
# 1. Clone or download to XAMPP htdocs
cd C:\xampp\htdocs\
# Place Quiz-App folder here

# 2. Initialize database
cd Quiz-App
php scripts/init_database.php

# 3. Start XAMPP
# Open XAMPP Control Panel
# Start Apache
# Start MySQL

# 4. Access the application
# Student Portal: http://localhost/Quiz-App/
# Admin Portal: http://localhost/Quiz-App/admin.php
```

### Default Credentials

**Admin Access:**
- URL: `http://localhost/Quiz-App/admin.php`
- Password: `admin123`

**Test Student:**
- Matric: `TEST001` (or any authorized student ID)

---

## 👨‍🎓 Student Experience

### 1. Login
Students enter their authorized matric number to access the quiz.

### 2. Take Quiz
- Questions appear in unique randomized order
- Timer counts down with any admin adjustments
- Answers auto-save every 5 seconds
- Camera and microphone monitored (not invasive)

### 3. Submit & View Results
- Submit quiz manually or auto-submit when time expires
- See score percentage immediately
- Review correct/incorrect answers
- View performance charts
- Share results via WhatsApp

### 4. Monitoring (Transparent)
Students are monitored through:
- **Camera**: Snapshots on anomaly detection
- **Microphone**: Loud sound alerts
- **Tab Switches**: Tracked and limited to 3
- **Status**: Visible to admin in real-time

---

## 👨‍💼 Admin Guide

### Dashboard Overview

#### 1. Quiz Configuration
```
Set Questions: 1-100
Set Duration: 5-300 minutes
Save changes instantly
```

#### 2. Monitor Students
- View all active sessions
- See progress percentage
- Check accuracy scores
- Monitor violation counts
- Track submission status

#### 3. Proctor View
Access comprehensive monitoring:
- **Violations List** - Sorted by student name
- **Action Buttons** - Quick admin controls
- **Message Button** - Instant communication
- **Camera Snapshots** - Live feed viewer

### Administrative Actions

#### Time Management
```javascript
Add Time: +5 minutes compensation
Remove Time: -5 minutes penalty
Reason: Technical issue / Violation
```

#### Disciplinary Actions
- **Time Penalty** - Subtract from timer
- **Point Deduction** - Reduce final score by 10 points
- **Boot Out** - Terminate exam immediately
- **Cancel Exam** - Block from re-entry
- **Send Warning** - Log without penalty

#### Communication
Send messages that appear as notifications:
- Warning messages
- Instructions
- Clarifications
- Time updates

### Analytics Dashboard

**Refresh Accuracy** button calculates:
- Percentage correct answers
- Average time per question
- Violation summaries
- Performance trends

---

## 🔌 API Endpoints

### Core APIs

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/config.php` | GET/POST | Quiz configuration |
| `/api/sessions.php` | GET/POST | Student sessions |
| `/api/violations.php` | GET/POST | Violation tracking |
| `/api/messages.php` | GET/POST | Messaging system |
| `/api/snapshot.php` | GET/POST | Camera snapshots |

### Enhanced APIs

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/shuffle.php` | GET | Question randomization |
| `/api/accuracy.php` | GET | Performance metrics |
| `/api/time_control.php` | GET/POST | Time management |
| `/api/admin_actions.php` | GET/POST | Disciplinary actions |

All APIs return JSON and include proper error handling.

---

## 📚 Documentation

Comprehensive guides available in `/docs`:

| Document | Description |
|----------|-------------|
| [**Quick Start**](docs/QUICK_START.md) | Setup and installation |
| [**Final Summary**](docs/FINAL_SUMMARY.md) | Complete feature list |
| [**Enhancements Guide**](docs/ENHANCEMENTS_GUIDE.md) | Technical implementation |
| [**Implementation Checklist**](docs/IMPLEMENTATION_CHECKLIST.md) | Requirements tracking |
| [**Backend Guide**](docs/BACKEND_GUIDE.md) | API documentation |
| [**Proctor Guide**](docs/PROCTOR_GUIDE.md) | Monitoring instructions |

---

## 🔒 Security

### Implemented Measures

✅ **Authentication**
- Session-based login
- Admin password protection
- Authorized student list

✅ **Database Security**
- Prepared statements (SQL injection prevention)
- Input validation
- XSS protection with `htmlspecialchars()`

✅ **Access Control**
- Status verification (booted/cancelled)
- Admin session checking
- API authorization

✅ **Audit Trail**
- All actions logged with reasons
- Timestamp tracking
- Admin name recording

✅ **Anti-Cheating**
- Tab switch detection
- Camera monitoring
- Audio detection
- Violation limits

---

## 📁 Project Structure

```
Quiz-App/
├── 📄 index.php              # Entry point (redirects to login)
├── 🔐 login.php              # Student authentication
├── 📝 quiz_new.php           # Main quiz interface
├── 📊 result.php             # Results display
├── 👨‍💼 admin.php              # Admin dashboard
├── 👁️ proctor.php            # Proctor monitoring
├── 🔧 db.php                 # Database connection
├── 📁 scripts/              # Maintenance & setup scripts
│   ├── init_database.php
│   ├── init_db.php
│   ├── migrate.php
│   ├── migrate_students.php
│   ├── seed_students.php
│   ├── update_student_questions_group.php
│   ├── verify_schema.php
│   └── tests/
│       ├── test_all_apis.php
│       ├── test_apis.html
│       ├── test_comprehensive.html
│       ├── test_config_api.php
│       └── test_db.php
├── 📁 api/                   # API endpoints
│   ├── config.php
│   ├── sessions.php
│   ├── violations.php
│   ├── messages.php
│   ├── shuffle.php
│   ├── accuracy.php
│   ├── time_control.php
│   └── admin_actions.php
├── 📁 assets/                # CSS and static files
│   └── style.css
├── 📁 uploads/               # User uploads
│   └── evidence/
└── 📁 docs/                  # Documentation (all guides, summaries)
    ├── QUICK_START.md
    ├── FINAL_SUMMARY.md
    └── ...
```

---

## 🎨 Features Showcase

### Question Shuffling
Each student receives questions in a unique order, preventing cheating through memorization.

### Smart Proctoring
- **Audio**: Only triggers on loud sounds (threshold-based)
- **Video**: Captures snapshots when anomalies detected
- **Behavior**: Tracks tab switches with grace period

### Real-time Control
Admin can adjust quiz parameters while students are taking the exam:
- Add extra time for technical issues
- Deduct time for violations
- Send immediate messages
- Take disciplinary actions

### Analytics Dashboard
Comprehensive performance metrics:
- Accuracy percentage
- Time management analysis
- Violation summaries
- Answer breakdowns

---

## ❓ FAQ

<details>
<summary><b>How do I add new students?</b></summary>

Edit the authorized student list in `login.php`:
```php
$authorizedStudents = [
    'CSC/2021/001',
    'CSC/2021/002',
    // Add more here
];
```
</details>

<details>
<summary><b>How do I add questions?</b></summary>

Questions are stored in the `questions` table. You can:
1. Use `questions.md` as a template
2. Import via SQL INSERT statements
3. Use a CSV import tool
</details>

<details>
<summary><b>Can I change the admin password?</b></summary>

Edit `admin.php` line 8:
```php
$adminPassword = 'your-new-password';
```
</details>

<details>
<summary><b>What if camera doesn't work?</b></summary>

Camera requires:
- HTTPS connection (or localhost)
- Browser permission granted
- Working camera hardware

Students can still take quiz if camera fails.
</details>

---

## 🐛 Troubleshooting

### Database Connection Error
```bash
php scripts/init_database.php
```

### API 500 Errors
Check:
- MySQL is running
- Database `quiz_app` exists
- PHP error logs in XAMPP

### Permission Issues
Ensure XAMPP has write permissions for:
- `uploads/` folder
- Session storage

---

## 🤝 Support

Need help? Check these resources:

- 📖 [Documentation](docs/)
- 🐛 [Report Issues](https://github.com/MAVIS-creator/Quiz-App/issues)
- 💬 [Discussions](https://github.com/MAVIS-creator/Quiz-App/discussions)

---

## 📊 System Requirements

### Minimum
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+
- 512MB RAM
- Modern browser (Chrome, Firefox, Edge)

### Recommended
- PHP 8.0+
- MySQL 8.0+
- 1GB+ RAM
- SSD storage
- Chrome/Edge (best camera support)

---

## 🎯 Roadmap

Future enhancements planned:
- [ ] Face recognition with face-api.js
- [ ] Advanced ML-based audio classification
- [ ] WebSocket real-time dashboard
- [ ] PDF report generation
- [ ] Email notifications
- [ ] Mobile app version

---

## 📄 License

This project is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

---

## 👏 Credits

**Developed by:** Web Dev Group 1

**Technologies Used:**
- PHP & MySQL
- Tailwind CSS
- SweetAlert2
- Chart.js
- Boxicons

---

<div align="center">

### ⭐ If you find this project useful, please star it!

**© 2025 Web Dev Group 1. All rights reserved.**

[📖 Documentation](docs/) • [🐛 Report Bug](https://github.com/MAVIS-creator/Quiz-App/issues) • [💡 Request Feature](https://github.com/MAVIS-creator/Quiz-App/issues)

</div>
