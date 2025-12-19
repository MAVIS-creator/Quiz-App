# Quick Start - Question & Student Import

## Test the Import Features

### 1. Access Admin Dashboard
- Go to: `http://localhost/Quiz-App/admin_login.html`
- Username: `admin`
- Password: `admin`
- Select: **Group 1** (or Group 2)

### 2. Import Sample Questions
1. Find **"Import Questions (Markdown)"** section
2. Click file input → Select `sample_questions_group1.md`
3. Click **"Import Questions"**
4. ✅ Success: "Imported 10 questions for Group 1"

**File Format:**
```
# Group 1
## Question Text?
Option A
Option B  
Option C
~~Correct Answer~~
```

### 3. Import Sample Students
1. Find **"Import Students (CSV)"** section
2. Click file input → Select `sample_students_group1.csv`
3. Click **"Import Students"**
4. ✅ Success: "Imported 10 students for Group 1"

**File Format (CSV):**
```
Name,Matric,Phone
John Doe,M20001,08012345678
Jane Smith,M20002,08023456789
```

### 4. View Uploaded Snapshots & Audio
1. Go to **Proctor Dashboard** (link in admin header)
2. **Live Camera Snapshot** section:
   - Enter student Matric (e.g., `M20001`)
   - Click "Load Snapshot"
   - ✅ Displays image from `/uploads/snapshot_*.jpg`
3. **Audio Recordings** section:
   - Enter student Matric
   - Click "Load Recordings"
   - ✅ Displays list of audio files with duration
   - ▶️ Click play button to listen

---

## File Storage Locations

All uploaded files are saved in:
```
/Quiz-App/uploads/
├── snapshot_M20001_*.jpg
├── audio_M20001_*.wav
└── evidence/
```

These files are served directly to the proctor dashboard for viewing/listening.

---

## What's Stored in Database

### students table
- Student name, matric number, phone
- Assigned to specific group (1 or 2)

### audio_clips table  
- Filename (stored in /uploads)
- Duration in seconds
- Student identifier & timestamp

### snapshots table
- Filename (stored in /uploads)
- Student identifier & timestamp
- *(Old 'image' column now just stores filename)*

---

## Group Isolation

- **Group 1 Admin** sees only Group 1 students/questions/violations
- **Group 2 Admin** sees only Group 2 students/questions/violations  
- Import automatically assigns to current group

---

## Error Handling

| Error | Fix |
|-------|-----|
| "No file provided" | Select a file before clicking Import |
| "No valid questions found" | Check markdown format (need ~~answer~~) |
| "Duplicate entries skipped" | Student Matric already exists |
| "Invalid base64 audio" | Audio file format not recognized |

---

## Sample Files Included

✅ `sample_questions_group1.md` - 10 test questions  
✅ `sample_students_group1.csv` - 10 test students  

Use these as templates to create your own files!

---

## Next Steps

1. ✅ Import sample questions & students
2. ✅ Verify snapshot/audio display on proctor page
3. 📝 Create your own .md and .csv files
4. 📤 Import for Group 1 and Group 2
5. 🎯 Test student quiz with snapshots & audio capture
