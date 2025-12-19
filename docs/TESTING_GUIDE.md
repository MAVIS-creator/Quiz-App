# 🧪 Complete Testing Guide

## ✅ All Syntax Errors Fixed

All API files now have **NO syntax errors**:
- ✅ accuracy.php
- ✅ admin_actions.php
- ✅ audio_clip.php
- ✅ config.php
- ✅ messages.php
- ✅ sessions.php
- ✅ shuffle.php
- ✅ snapshot.php (**FIXED** - removed extra brace)
- ✅ time_control.php
- ✅ violations.php (**FIXED** - removed extra brace)

---

## 🔧 How to Test Everything

### Option 1: Automated Test Suite (RECOMMENDED)
1. Open your browser
2. Navigate to: `http://localhost/Quiz-App/test_apis.html`
3. Watch all 14 tests run automatically
4. Review pass/fail results

### Option 2: Manual Testing

#### Step 1: Test Admin Login & Config
```
1. Go to: http://localhost/Quiz-App/admin.php
2. Enter password: admin123
3. Try changing exam duration and question count
4. Click "Save Configuration"
5. Expected: Success message, no 500 error
```

#### Step 2: Test Student Login
```
1. Go to: http://localhost/Quiz-App/login.php
2. Try matric number: TEST001
3. Try phone number: 8084343242
4. Expected: Login successful, redirect to quiz
```

#### Step 3: Test Quiz Session
```
1. After login, answer 2-3 questions
2. Wait 5 seconds (auto-save)
3. Refresh the page (F5)
4. Expected: Your answers should still be checked
```

#### Step 4: Test Audio Recording
```
1. Grant microphone permission
2. Make a loud sound
3. Check browser console for logs
4. Expected: Audio detection logged, no audio playback
```

#### Step 5: Test Submit
```
1. Answer all questions
2. Click "Submit Quiz"
3. Expected: Redirect to results page with score
```

#### Step 6: Test Admin Monitoring
```
1. Start a quiz as student (new tab)
2. Open admin.php in another tab
3. Expected: See student session in dashboard
4. Try sending a message to student
5. Expected: Student receives notification
```

---

## 🐛 Troubleshooting

### If you still see 500 errors:

#### Check Apache Error Log
```powershell
Get-Content "C:\xampp\apache\logs\error.log" -Tail 50
```

#### Check PHP Error Log
```powershell
Get-Content "C:\xampp\php\logs\php_error_log" -Tail 50
```

#### Test Specific API
```powershell
# Test config.php directly
Invoke-WebRequest -Uri "http://localhost/Quiz-App/api/config.php" -Method GET

# Test with POST
$body = @{examMinutes=60; questionCount=40} | ConvertTo-Json
Invoke-WebRequest -Uri "http://localhost/Quiz-App/api/config.php" -Method POST -Body $body -ContentType "application/json"
```

#### Verify Database
```powershell
# Check if database exists
php -r "$pdo = new PDO('mysql:host=localhost', 'root', ''); var_dump($pdo->query('SHOW DATABASES LIKE \"quiz_app\"')->fetch());"
```

---

## 🔍 API Testing Commands

### Test Each API Individually

```powershell
# 1. Config
curl http://localhost/Quiz-App/api/config.php

# 2. Sessions
curl http://localhost/Quiz-App/api/sessions.php

# 3. Violations
curl http://localhost/Quiz-App/api/violations.php

# 4. Messages
curl "http://localhost/Quiz-App/api/messages.php?a=TEST001"

# 5. Shuffle
curl "http://localhost/Quiz-App/api/shuffle.php?identifier=TEST001"

# 6. Accuracy
curl "http://localhost/Quiz-App/api/accuracy.php?identifier=TEST001"
```

---

## ✅ Expected Results

### 1. Config API
**GET:** Returns exam_minutes and question_count
```json
{"exam_minutes": 60, "question_count": 40}
```

**POST:** Updates config successfully
```json
{"ok": true, "exam_minutes": 60, "question_count": 40}
```

### 2. Sessions API
**GET:** Returns array of all sessions
```json
[{"identifier": "TEST001", "name": "Test Student", ...}]
```

**POST:** Creates/updates session
```json
{"ok": true}
```

### 3. Violations API
**GET:** Returns violations grouped by student
```json
[{"identifier": "TEST001", "count": 3}]
```

**POST:** Logs violation
```json
{"ok": true}
```

### 4. Messages API
**GET:** Returns unread count and messages
```json
{"unread_count": 2, "messages": [...]}
```

**POST:** Sends message
```json
{"ok": true}
```

### 5. Submit Quiz Flow
1. Student clicks "Submit Quiz"
2. Final save to `/api/sessions.php` with `submitted: true`
3. Response: `{"ok": true}`
4. Redirect to `result.php`
5. Result page shows score and answers

---

## 🎯 Critical Test Scenarios

### Scenario 1: New Student Takes Quiz
1. Login with phone number
2. Quiz loads with shuffled questions
3. Answer 10 questions
4. Refresh page
5. ✅ All 10 answers still selected
6. Continue and submit
7. ✅ See results page

### Scenario 2: Admin Monitors Student
1. Student starts quiz
2. Admin opens dashboard
3. ✅ Student appears in active sessions
4. Student switches tab
5. ✅ Violation logged in admin
6. Admin sends message
7. ✅ Student sees notification

### Scenario 3: Audio Detection
1. Student takes quiz
2. Makes loud sound
3. ✅ Violation logged
4. ✅ Audio clip saved (5-10 seconds)
5. ✅ No audio plays in browser

### Scenario 4: Time Management
1. Admin adds 5 minutes to student
2. ✅ Student timer increases by 5 minutes
3. Admin removes 3 minutes
4. ✅ Student timer decreases by 3 minutes

---

## 🚨 Common Issues & Fixes

### Issue: "Failed to load resource: 500"
**Cause:** PHP syntax error or database connection issue
**Fix:** 
```powershell
# Check syntax
php -l C:\xampp\htdocs\Quiz-App\api\config.php

# Test database connection
php C:\xampp\htdocs\Quiz-App\db.php
```

### Issue: "Tracking Prevention blocked"
**Cause:** Browser security blocking CDN storage
**Fix:** Not critical - doesn't affect functionality
```html
<!-- Option: Host libraries locally instead of CDN -->
```

### Issue: Sessions not appearing in admin
**Cause:** Session not being saved
**Fix:** Check browser console for fetch errors
```javascript
// Should see in Network tab:
POST /Quiz-App/api/sessions.php
Status: 200
Response: {"ok": true}
```

### Issue: Answers clearing on refresh
**Cause:** `loadSavedAnswers()` not running
**Fix:** Check browser console for errors
```javascript
// Should see in Console:
"Loaded saved answers: ..."
```

---

## 📊 Test Checklist

- [ ] Admin login works
- [ ] Config save works (no 500 error)
- [ ] Student login with matric number
- [ ] Student login with phone number
- [ ] Quiz loads with questions
- [ ] Answers persist on refresh
- [ ] Auto-save runs every 5 seconds
- [ ] Audio records silently
- [ ] Loud sound triggers detection
- [ ] Camera snapshots work
- [ ] Tab switch detected
- [ ] Admin sees student session
- [ ] Admin can send messages
- [ ] Student receives messages
- [ ] Submit quiz works
- [ ] Results page displays
- [ ] Violations logged
- [ ] Time adjustment works
- [ ] Admin actions work

---

## 🎉 Success Criteria

Your system is working correctly when:

1. ✅ **No 500 errors** in browser console
2. ✅ **Sessions appear** in admin dashboard immediately
3. ✅ **Answers persist** after page refresh
4. ✅ **Audio records** without playing
5. ✅ **Submit works** and shows results
6. ✅ **All API tests pass** in test_apis.html

---

## 🔗 Quick Links

- **Test Suite:** http://localhost/Quiz-App/test_apis.html
- **Admin Portal:** http://localhost/Quiz-App/admin.php
- **Student Portal:** http://localhost/Quiz-App/login.php
- **Proctor View:** http://localhost/Quiz-App/proctor.php

---

&copy; Web Dev Group 1
