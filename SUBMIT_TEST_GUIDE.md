# Submit API Testing Guide

## 🎯 Purpose
Test the quiz submission functionality across multiple devices and browsers to ensure compatibility and reliability.

## 📱 Access the Test Page

### Local Testing
```
http://localhost/Quiz-App/test_submit.html
```

### Network/Tunnel Testing
Replace with your tunnel URL:
```
https://your-ngrok-url.ngrok-free.app/Quiz-App/test_submit.html
https://your-pinggy-url.a.free.pinggy.link/Quiz-App/test_submit.html
```

## 🧪 What It Tests

1. **Device Compatibility**
   - Shows your device info (User Agent, Platform, Screen size)
   - Detects browser capabilities (cookies, online status)

2. **Submit Logic**
   - Tests the exact same submit flow used in quiz_new.php
   - Compatible `AbortController` timeout implementation (30s)
   - Automatic retry logic (3 attempts with 1s backoff)
   - Handles network failures gracefully

3. **API Response**
   - Validates JSON responses
   - Shows HTTP status codes
   - Displays response times
   - Reports detailed error messages

## 🔧 Test Configuration

**Adjustable Parameters:**
- Test Student ID (default: TEST_SUBMIT_001)
- Number of questions (default: 5)
- Enable/disable retry logic
- Enable/disable 30s timeout

## 📊 Results Display

The test provides:
- ✅ Success confirmation with timing
- ❌ Detailed failure logs
- ⚠️ Retry attempt tracking
- 📈 Performance metrics

## 🌐 Multi-Device Testing Steps

1. **Desktop Browser**
   - Open `http://localhost/Quiz-App/test_submit.html`
   - Run test with default settings

2. **Mobile Device (Same Network)**
   - Get your local IP: `ipconfig` (Windows) or `ifconfig` (Mac/Linux)
   - Access: `http://YOUR_LOCAL_IP/Quiz-App/test_submit.html`
   - Run test

3. **Mobile Device (Different Network via Tunnel)**
   - Start ngrok/pinggy tunnel
   - Share the tunnel URL + `/Quiz-App/test_submit.html`
   - Run test from any device/location

4. **Different Browsers**
   - Chrome, Firefox, Safari, Edge
   - Mobile browsers (Chrome Mobile, Safari iOS)
   - Test each to verify compatibility

## ✅ Expected Behavior

**Success:**
- Green checkmark ✅
- "SUCCESS! Submission completed in XXXms"
- HTTP 200 response
- `{"ok": true}` in response data

**Retry Success:**
- May show warnings on failed attempts
- Final success after 1-3 attempts
- Total time includes all retry delays

**Failure:**
- Red error ❌
- Detailed error message
- HTTP status code (if available)
- Network error details

## 🐛 Troubleshooting

**"Failed to fetch" error:**
- Check if Apache/XAMPP is running
- Verify the URL is correct
- Check browser console for CORS errors

**Timeout errors:**
- Network may be slow
- Try disabling timeout to test without limit
- Check server response time

**All attempts failed:**
- Verify database connection
- Check `api/sessions.php` is accessible
- Review server error logs

## 📝 Login Page Improvements

Added troubleshooting hint box with:
- ✅ Matric number format (no spaces)
- ✅ Phone number format (without leading 0)
- ✅ Registration reminder

## 🔗 Related Files

- `test_submit.html` - Multi-device test interface
- `quiz_new.php` - Actual quiz submission logic
- `api/sessions.php` - Backend submission endpoint
- `login.php` - Updated with troubleshooting hints

---

**Pro Tip:** Keep this test page open on multiple devices while students take the quiz. If submission issues occur, you can immediately test and diagnose the problem!
