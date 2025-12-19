# Quiz App - Import Features Guide

## Overview
This guide explains how to import questions and students for each group using the Admin Dashboard.

---

## 1. Question Import (Markdown Format)

### How It Works
Questions are imported from a **Markdown (.md)** file format. Each group admin can import questions specific to their group.

### File Format
```
# Group 1

## Question Text Here?
Option A
Option B
Option C
~~Correct Answer~~

## Another Question?
First Option
Second Option
Third Option
~~The Answer~~
```

**Important:**
- The correct answer MUST be marked with `~~` on both sides (strikethrough)
- Each question must have exactly 4 options
- All options appear as plain lines
- Group header is optional (imports will be assigned to the admin's current group)

### Example File (sample_questions_group1.md)
```
# Group 1

## What is the capital of Nigeria?
Abuja
Lagos
Kano
~~Abuja~~

## Which of the following is a programming language?
Python
JavaScript
Java
~~Python~~
```

### Steps to Import
1. Go to **Admin Dashboard** → Logged in with Group 1 or 2
2. Find the **"Import Questions (Markdown)"** section
3. Click the file input and select your `.md` file
4. Click **"Import Questions"** button
5. A success message will show how many questions were imported

### Sample File
A sample file is provided: `sample_questions_group1.md` (10 questions)

---

## 2. Student Import (CSV Format)

### How It Works
Students are imported from a **CSV file**. Each group admin imports students for their group only.

### File Format
```
Name,Matric,Phone
John Doe,M20001,08012345678
Jane Smith,M20002,08023456789
```

**Important:**
- First row must be headers: `Name,Matric,Phone`
- Matric (Student ID) must be UNIQUE per group
- Phone number is optional
- No special characters in fields (except standard punctuation)

### Example File (sample_students_group1.csv)
```
Name,Matric,Phone
Chioma Okoro,M20001,08012345678
Emeka Nwosu,M20002,08023456789
Ngozi Eze,M20003,08034567890
```

### Steps to Import
1. Go to **Admin Dashboard** → Logged in with Group 1 or 2
2. Find the **"Import Students (CSV)"** section
3. Click the file input and select your `.csv` file
4. Click **"Import Students"** button
5. A success message will show how many students were imported
6. Duplicates (same Matric) are skipped and counted

### Sample File
A sample file is provided: `sample_students_group1.csv` (10 students)

---

## 3. Multi-Group Setup

### Group Isolation
- **Group 1 Admin**: Can only see/modify Group 1 questions and students
- **Group 2 Admin**: Can only see/modify Group 2 questions and students
- Questions and students are automatically assigned to the admin's group during import

### Admin Login
1. Go to `admin_login.html`
2. Enter Username: `admin` and Password: `admin`
3. **Select your group**: Group 1 or Group 2
4. Click Login
5. You'll see your group-specific dashboard

---

## 4. File Storage & Display

### Snapshots
- **Stored**: `/uploads/snapshot_*.jpg` files
- **Displayed**: Proctor page → Enter student ID → Click "Load Snapshot"
- Auto-refresh every 2 seconds (optional checkbox)

### Audio Recordings
- **Stored**: `/uploads/audio_*.wav` or `.webm` files
- **Displayed**: Proctor page → Enter student ID → Click "Load Recordings"
- Shows duration of each recording
- HTML5 audio player with controls (play, pause, volume)

---

## 5. Database Tables

### students
```
id: Auto-increment ID
identifier: Student Matric (UNIQUE)
name: Student Name
phone: Contact Phone (optional)
group_id: Group Assignment (1 or 2)
created_at: Import timestamp
```

### audio_clips
```
id: Auto-increment ID
identifier: Student Matric
filename: Stored filename in /uploads
duration: Recording duration in seconds
created_at: Recording timestamp
```

### snapshots (Updated)
```
id: Auto-increment ID
identifier: Student Matric
filename: Stored filename in /uploads (replaces old 'image' field)
timestamp: Snapshot capture time
```

---

## 6. API Endpoints

### Question Import
- **Endpoint**: `/Quiz-App/api/question_import.php`
- **Method**: POST (multipart/form-data)
- **Body**: File upload with form field `file`
- **Auth**: Requires admin session with group assignment

### Student Import
- **Endpoint**: `/Quiz-App/api/student_import.php`
- **Method**: POST (multipart/form-data)
- **Body**: File upload with form field `file`
- **Auth**: Requires admin session with group assignment

### Audio Save
- **Endpoint**: `/Quiz-App/api/audio_save.php`
- **Method**: POST (JSON) for upload, GET for retrieval
- **Upload Body**: `{identifier, audio (data URL), duration}`
- **GET Params**: `?identifier=student_id`

### Snapshot Save
- **Endpoint**: `/Quiz-App/api/snapshot.php`
- **Method**: POST (JSON) for upload, GET for retrieval
- **Upload Body**: `{identifier, image (data URL)}`
- **GET Params**: `?identifier=student_id`

---

## 7. Error Handling

### Common Issues

**"Invalid file format"**
- Ensure markdown format is correct
- Check answer is marked with `~~`
- Verify 4 options per question

**"File upload error"**
- File size too large (>50MB)
- Check browser/server file upload limits
- Try different file format

**"Duplicate entries skipped"**
- Student Matric already exists in database
- Review CSV for duplicate rows
- Remove duplicates manually before re-importing

**"No valid questions found"**
- File appears empty
- Wrong markdown syntax
- Check that file is not corrupted

---

## 8. Best Practices

1. **Before Importing**
   - Verify all data in CSV/MD file
   - Check for duplicates
   - Test with sample files first

2. **Naming Conventions**
   - Questions: `questions_group1.md` or `questions_group2.md`
   - Students: `students_group1.csv` or `students_group2.csv`

3. **Regular Backups**
   - Export your questions/students before major changes
   - Keep original CSV files for reference

4. **Group Management**
   - Always select correct group before importing
   - Don't mix students from different groups
   - Each question is tied to one group only

---

## 9. Sample Files Location

Both sample files are included in the project root:
- `sample_questions_group1.md` - 10 sample questions
- `sample_students_group1.csv` - 10 sample students

You can use these as templates for your own imports.

---

## 10. Support

For issues or questions:
- Check browser console (F12) for JavaScript errors
- Review server logs in XAMPP
- Verify MySQL database is running
- Ensure PHP session is active in admin dashboard
