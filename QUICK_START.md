# 🚀 QUICK START GUIDE

## 1️⃣ Database Setup (One Time Only)

```bash
# Navigate to Quiz-App directory
cd C:\xampp\htdocs\Quiz-App

# Run database initialization
php init_database.php
```

**Expected Output:**
```
✓ Executed
✓ Executed
...
✅ Database created successfully with all tables!
```

## 2️⃣ Start XAMPP

1. Open XAMPP Control Panel
2. Start **Apache**
3. Start **MySQL**

## 3️⃣ Access the Application

### **For Students:**
```
URL: http://localhost/Quiz-App/
```
- Enter matric number from authorized list
- Take quiz with shuffled questions
- Submit and view results

### **For Admin:**
```
URL: http://localhost/Quiz-App/admin.php
Password: admin123
```

## 4️⃣ Admin Actions

### **Configure Quiz:**
1. Set number of questions (1-100)
2. Set duration (5-300 minutes)
3. Click "Save Configuration"

### **Monitor Students:**
1. Click "Proctor View" button
2. See all violations sorted by student name
3. View live camera snapshots

### **Control Individual Students:**

**Click "Actions" button on any student:**
- ⏰ **Add Time** - Give extra 5 minutes
- ⏱️ **Time Penalty** - Remove 5 minutes
- 📉 **Deduct Points** - Remove 10 points
- ⚠️ **Send Warning** - Log warning only
- 🚪 **Boot Out** - Terminate exam now
- ❌ **Cancel Exam** - Block completely

**Click "Message" button:**
- Type message to student
- Student sees notification for 10 seconds
- Appears during quiz

### **View Analytics:**
1. Go back to Admin Dashboard
2. Click "Refresh Accuracy"
3. See accuracy % for all submitted quizzes

## 5️⃣ New Features in Action

### **Question Shuffling:**
- Each student automatically gets unique question order
- No configuration needed
- Happens on first quiz access

### **Smart Proctoring:**
- **Audio:** Only logs if volume > threshold
- **Video:** Smart snapshot on face detection
- **Tab Switches:** Auto-submits after 3 violations
- All automatic - no setup required

### **Messaging:**
- Admin sends message from Proctor View
- Student sees overlay notification
- Messages check every 5 seconds

## 📁 Important Files

| File | Purpose |
|------|---------|
| `login.php` | Student login (no monitoring hints) |
| `quiz_new.php` | Enhanced quiz with all features |
| `admin.php` | Admin dashboard with accuracy |
| `proctor.php` | Monitoring with action controls |
| `result.php` | Results display with charts |
| `init_database.php` | Database setup script |

## 🔑 Default Credentials

**Admin:**
- URL: `admin.php`
- Password: `admin123`

**Test Students (from image):**
- CSC/2021/001
- CSC/2021/002
- ... (14 students total)
- TEST001 (test account)

## 🐛 Common Issues

### "Database not found"
```bash
php init_database.php
```

### "Access denied for user"
Check MySQL is running in XAMPP

### "Headers already sent"
Check for whitespace before `<?php` tags

### "API not found"
Check .htaccess exists (should already be there)

## 📊 Quick Feature Test

1. **Test Shuffling:**
   - Login as TEST001
   - Note question order
   - Logout and login as different student
   - Questions should be in different order

2. **Test Admin Actions:**
   - Open admin.php (password: admin123)
   - Click "Proctor View"
   - Click "Actions" on any student
   - Select "Add Time"
   - Enter reason and confirm

3. **Test Messaging:**
   - From Proctor View, click "Message"
   - Type: "Test message"
   - Send
   - Check student quiz page for notification

4. **Test Accuracy:**
   - Wait for at least one student to submit
   - Go to Admin Dashboard
   - Click "Refresh Accuracy"
   - See percentage in table

## 🎯 Admin Workflow Example

```
1. Login to admin.php (admin123)
   ↓
2. Configure: 40 questions, 60 minutes
   ↓
3. Save Configuration
   ↓
4. Click "Proctor View"
   ↓
5. Monitor students in real-time
   ↓
6. If violation detected:
   - Click "Actions"
   - Choose penalty
   - Enter reason
   - Apply
   ↓
7. Send messages if needed
   ↓
8. After quiz: "Refresh Accuracy" to see results
```

## 📞 Support

**Check browser console (F12) for:**
- API errors
- Network requests
- JavaScript errors

**Check files:**
- `FINAL_SUMMARY.md` - Complete documentation
- `ENHANCEMENTS_GUIDE.md` - Feature details

---

## ✅ Quick Verification

Run these to verify everything works:

### Test Database:
```bash
php -r "require 'db.php'; $pdo = db(); echo 'Connected!';  $stmt = $pdo->query('SHOW TABLES'); while($row = $stmt->fetch()) { echo PHP_EOL . $row[0]; }"
```

### Test API:
```bash
# Windows PowerShell
Invoke-WebRequest -Uri "http://localhost/Quiz-App/api/shuffle.php?identifier=TEST001" | Select-Object -Expand Content
```

---

**© Web Dev Group 1**

*Ready to use! No further setup required.*
