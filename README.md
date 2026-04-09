# 🏠 HostelAssist System

> **A Web-Based Hostel Management Platform for Gharda Institute of Technology**

![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Status](https://img.shields.io/badge/status-Active-brightgreen.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-777bb4.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479a1.svg)

---

## 📋 Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [System Architecture](#system-architecture)
- [User Roles & Workflows](#user-roles--workflows)
- [Technology Stack](#technology-stack)
- [Installation & Setup](#installation--setup)
- [Database Schema](#database-schema)
- [Gate Pass Management](#gate-pass-management)
- [Complaint Management](#complaint-management)
- [Notification System](#notification-system)
- [Configuration](#configuration)
- [Security](#security)
- [File Structure](#file-structure)
- [Troubleshooting](#troubleshooting)
- [Version History](#version-history)
- [Contact & Support](#contact--support)

---

## 🎯 Overview

**HostelAssist System** is a web-based hostel management portal built for the **Ajinkyatara Girls Hostel, Gharda Institute of Technology**, Lavel, Khed, Maharashtra – 415708.

It digitises two core workflows that were previously manual:

| Module | Purpose |
|---|---|
| **Gate Pass** | Students request leave passes → HOD and/or Warden approve based on submission time |
| **Complaints** | Students raise hostel issues → Warden assigns and resolves them |

### 🌟 Highlights
- 🔐 **Role-Based Access Control** — Separate dashboards for students, HODs, wardens, and admins
- ⏰ **Smart Time-Based Routing** — Gate passes auto-route through HOD or directly to Warden based on college hours
- 🔔 **In-App Notification Engine** — Deep-linked notifications for every status change
- 📋 **Full Audit Trail** — All statuses visible to HOD/Warden; records never disappear after processing
- 📊 **Report Export** — Role-restricted downloadable reports
- 🖊️ **Digital Signatures** — HOD and Warden signatures printed on approved gate passes
- 🔧 **Auto Schema Migration** — Missing DB columns added automatically on startup

---

## ✨ Key Features

### 1. Gate Pass Management
- ✅ Smart initial status assignment based on submission time (working hours vs. off-hours)
- ✅ HOD approval path for working-hour submissions (Mon–Sat, 10:00–17:00)
- ✅ Direct Warden path for off-hours/weekend/holiday submissions
- ✅ Full status visibility for HOD (Pending HOD, Pending Warden, Approved, Rejected)
- ✅ Holiday detection via Tallyfy National Holidays API
- ✅ Digital signature display on approved passes
- ✅ Student PDF print/download after full approval
- ✅ Search, bulk delete, and export functionality

### 2. Complaint Management
- ✅ Students lodge complaints with an optional anonymous mode
- ✅ Status progression: Pending → Assigned → In Progress → Resolved
- ✅ Completion date tracking and warden remarks
- ✅ Read-only mode after a complaint is resolved
- ✅ Notifications at every stage
- ✅ Bulk delete and CSV/PDF report export (Warden only)

### 3. Notification System
- ✅ In-app notification inbox with unread badge counter
- ✅ Role and department-targeted notifications
- ✅ Deep-link routing — clicking a notification opens the relevant record
- ✅ Mark-as-read on click, full notification history

### 4. User Management
- ✅ Student self-registration (college email required)
- ✅ Faculty registration (HOD, Warden roles)
- ✅ Department-scoped data access for HOD
- ✅ Profile editing with photo and signature upload
- ✅ OTP-based password recovery (on-screen fallback for local server)

### 5. Administrative Features
- ✅ Admin dashboard for user management
- ✅ Role-restricted report generation (Warden → complaints, HOD → gate passes)
- ✅ Bulk record operations

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│               Frontend Layer (HTML5 / CSS3 / JS)            │
│     Bootstrap 4 · jQuery · AOS.js · Owl Carousel           │
├─────────────────────────────────────────────────────────────┤
│              PHP Application Layer (PHP 8)                   │
│  Authentication · Session Management · Business Logic        │
│  Role-Based Routing · Prepared Statements (MySQLi OOP)      │
├─────────────────────────────────────────────────────────────┤
│                  MySQL Database Layer                        │
│  students · faculty · admins · gate_pass                    │
│  complaints · notifications                                  │
└─────────────────────────────────────────────────────────────┘
                         │
                         └── External API: Tallyfy (National Holidays)
```

---

## 👥 User Roles & Workflows

### 1. Student
- Apply for gate passes (smart routing applied at submission time)
- Lodge complaints, with optional anonymous name toggle
- Track status of all own gate passes and complaints
- Download/print gate pass PDF after full warden approval
- Receive in-app notifications for every status change

**Flow:**
```
Login → Dashboard → Apply Gate Pass / Lodge Complaint → Get Notified → Track Status
```

---

### 2. HOD (Head of Department)

- **Sees:** Gate passes from own department, submitted during working hours (Mon–Sat, 10:00–17:00)
- **Sees all statuses:** Pending HOD, Pending Warden, Approved, Rejected — records persist after processing
- **Can act (Approve/Reject):** Only when status = `Pending HOD`
- After HOD approves → status changes to `Pending Warden`
- After HOD rejects → status changes to `Rejected`

**Dashboard Filter Rule:**
```sql
WHERE department = HOD's department
  AND DAYOFWEEK(issue_date) BETWEEN 2 AND 7   -- Mon–Sat
  AND TIME(issue_time) BETWEEN '10:00' AND '17:00'
```

**Flow:**
```
Login → Dashboard → Click Gatepass → View Details → Approve / Reject
```

---

### 3. Warden

- **Gate Passes:** Sees all where `status = 'Pending Warden'`
  - Includes HOD-approved (working hours route)
  - Includes direct submissions (off-hours/weekend route)
- **Complaints:** Sees all complaints from all students
- Can update complaint status, add remarks, set completion date
- Approve/Reject gate passes

**Flow:**
```
Login → Dashboard → Review Gate Pass or Complaint → Update Status → Student Notified
```

---

### 4. Admin
- Manage students, faculty, and admin accounts
- View all records across the system
- Generate and export reports

---

## 🛠️ Technology Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, CSS3, Bootstrap **4**, JavaScript, jQuery |
| **Backend** | PHP 8 (procedural + OOP MySQLi) |
| **Database** | MySQL 5.7+ |
| **Server** | Apache (XAMPP recommended) |
| **Animations** | AOS.js, Owl Carousel |
| **Icons** | Font Awesome 4 |
| **Sessions** | PHP `$_SESSION` |
| **External API** | Tallyfy National Holidays API (holiday detection) |

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache web server (XAMPP recommended for local setup)

### Step 1: Place Project Files
```
Place the HostelAssistSystem/ folder inside:
  XAMPP → htdocs/hostelAssistSystem/HostelAssistSystem/
```

### Step 2: Create the Database
```sql
CREATE DATABASE hostel_assist;
USE hostel_assist;
```

The system uses **auto-schema migration** — tables and missing columns are created automatically on first run via `system_helpers.php`. You do not need to import a SQL file manually.

### Step 3: Configure Database Connection

Edit `HostelAssistSystem/config.php`:
```php
<?php
date_default_timezone_set('Asia/Kolkata');

$conn = mysqli_connect("localhost", "root", "", "hostel_assist");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

### Step 4: Run the Application
```
http://localhost/hostelAssistSystem/HostelAssistSystem/index.html
```

### Step 5: Register Users
1. Register student accounts via `register.php`
2. Register faculty (HOD/Warden) via `faculty_register.html`
3. Admin accounts are created directly in the `admins` table

---

## 📊 Database Schema

### `students`
```sql
CREATE TABLE students (
    student_id    INT PRIMARY KEY AUTO_INCREMENT,
    name          VARCHAR(100),
    email         VARCHAR(100) UNIQUE,
    mobile        VARCHAR(15),
    department    VARCHAR(50),
    semester      INT,
    room_number   VARCHAR(20),
    parent_mobile1 VARCHAR(15),
    parent_mobile2 VARCHAR(15),
    password      VARCHAR(255),       -- plain text (local/college project)
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `faculty` (HOD, Warden, Admin)
```sql
CREATE TABLE faculty (
    staff_id    INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(100),
    email       VARCHAR(100) UNIQUE,
    role        VARCHAR(50),          -- 'HOD', 'Warden', or 'Admin'
    department  VARCHAR(100),         -- used for HOD dept-scoping
    password    VARCHAR(255),         -- plain text (local/college project)
    photo       VARCHAR(255),
    signature   VARCHAR(255),         -- auto-added column via ALTER TABLE
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `gate_pass`
```sql
CREATE TABLE gate_pass (
    gatepass_id     INT PRIMARY KEY AUTO_INCREMENT,
    student_id      INT,
    location        VARCHAR(255),
    reason          TEXT,
    date_going      DATE,
    time_going      TIME,
    date_return     DATE,
    time_return     TIME,
    issue_date      DATE,             -- date gatepass was submitted
    issue_time      TIME,             -- time gatepass was submitted (used for routing)
    status          VARCHAR(50),      -- 'Pending HOD' | 'Pending Warden' | 'Approved' | 'Rejected'
    hod_approved    TINYINT(1) DEFAULT 0,
    warden_approved TINYINT(1) DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);
```

### `complaints`
```sql
CREATE TABLE complaints (
    complaint_id      INT PRIMARY KEY AUTO_INCREMENT,
    student_id        INT,
    title             VARCHAR(255),
    description       TEXT,
    status            VARCHAR(100) DEFAULT 'Pending',  -- auto-added column
    remark            TEXT,                            -- auto-added column
    completion_date   DATE,                            -- auto-added column
    show_name         VARCHAR(5) DEFAULT 'yes',        -- 'yes' or 'no' (anonymous toggle)
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);
```

### `notifications`
```sql
CREATE TABLE notifications (
    notification_id  INT PRIMARY KEY AUTO_INCREMENT,
    recipient_role   VARCHAR(50) NOT NULL,  -- 'student' | 'hod' | 'warden'
    recipient_id     INT NOT NULL,
    actor_role       VARCHAR(50),
    actor_id         INT,
    title            VARCHAR(255) NOT NULL,
    message          TEXT NOT NULL,
    entity_type      VARCHAR(50) NOT NULL,  -- 'gatepass' | 'complaint'
    entity_id        INT NOT NULL,
    target_url       VARCHAR(255) NOT NULL, -- deep link to the record
    is_read          TINYINT(1) DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient_role, recipient_id, is_read),
    INDEX idx_entity    (entity_type, entity_id)
);
```

### `admins`
```sql
CREATE TABLE admins (
    admin_id   INT PRIMARY KEY AUTO_INCREMENT,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

> **Note:** Missing columns (`status`, `remark`, `completion_date` on complaints; `signature` on faculty) are auto-added on first run via `ensureColumnExists()` in `system_helpers.php`. You never need to manually run `ALTER TABLE`.

---

## 🎫 Gate Pass Management

### Smart Status Assignment at Submission

When a student submits a gate pass, `apply_gatepass.php` checks the **exact date and time of submission**:

```php
define('COLLEGE_HOUR_START', '10:00');
define('COLLEGE_HOUR_END',   '17:00');

$needsHodApproval = gatepassNeedsHodApprovalAtIssueTime($issueDate, $issueTime);
$status = $needsHodApproval ? "Pending HOD" : "Pending Warden";
```

| Submitted During | Initial Status | Approval Route |
|---|---|---|
| Mon–Sat, 10:00–17:00 (working hours) | `Pending HOD` | HOD → Warden → Approved |
| Sunday | `Pending Warden` | Warden → Approved (direct) |
| National Holiday | `Pending Warden` | Warden → Approved (direct) |
| Before 10:00 or after 17:00 on weekday | `Pending Warden` | Warden → Approved (direct) |

> Holidays are detected via the Tallyfy API: `https://tallyfy.com/national-holidays/api/IN/YYYY.json`

---

### Full Approval Flow

```
WORKING HOURS ROUTE (Mon–Sat, 10:00–17:00):

  Student Submits
       │
  status = "Pending HOD"
       │
  HOD Reviews (from hod_home.php)
       ├── Approve ──→ status = "Pending Warden" (hod_approved = 1)
       │                   │
       │              Warden Reviews (from warden_home.php)
       │                   ├── Approve ──→ status = "Approved" (warden_approved = 1)
       │                   └── Reject  ──→ status = "Rejected"
       └── Reject  ──→ status = "Rejected"


NON-WORKING HOURS / WEEKEND / HOLIDAY ROUTE:

  Student Submits
       │
  status = "Pending Warden"  (HOD skipped entirely)
       │
  Warden Reviews (from warden_home.php)
       ├── Approve ──→ status = "Approved" (warden_approved = 1)
       └── Reject  ──→ status = "Rejected"
```

---

### Status Reference

| Status | Meaning | Who Sees It |
|---|---|---|
| `Pending HOD` | Awaiting HOD review | HOD (can act) + Student |
| `Pending Warden` | Awaiting Warden review | Warden (can act) + Student |
| `Approved` | Fully approved — student can print | All roles |
| `Rejected` | Rejected at HOD or Warden level | All roles |

> **HOD now sees ALL statuses** for their department's working-hours submissions. Records are never hidden after processing — this provides a complete audit trail.

---

### Gate Pass Detail Page (`view_gatepass.php`)

| Role | Can View | Action Available | Print/Download |
|---|---|---|---|
| Student | Own records only | Delete (any status) | Only when `Approved` + `warden_approved=1` |
| HOD | Own dept, all statuses | Approve/Reject only if `Pending HOD` | No |
| Warden | All records | Approve/Reject only if `Pending Warden` | No |
| Admin | All records | View only | No |

Signatures (uploaded images) for HOD and Warden appear on the printed gate pass slip when `hod_approved = 1` and `warden_approved = 1` respectively.

---

## 📝 Complaint Management

### Status Flow
```
[Student Submits]
      │
 status = 'Pending'
      │
 Warden Updates
      ├─→ 'Assigned'       (assigned to a team)
      ├─→ 'In Progress'    (being worked on)
      └─→ 'Resolved'       ← final; becomes read-only
```

### Key Fields Tracked
- Title and description
- Status with timestamps
- Warden remark/response
- Completion date (when resolved)
- Anonymous mode (`show_name = 'no'` displays "Anonymous" instead of student name)

---

## 🔔 Notification System

All notifications are stored in the `notifications` table and surfaced via `notification.php`.

### Trigger Points

| Event | Recipients |
|---|---|
| Student submits gate pass (working hours) | HOD (dept-filtered) + Warden (info: waiting for HOD) |
| Student submits gate pass (off-hours) | Warden (direct) |
| HOD approves gate pass | Student + Warden |
| HOD rejects gate pass | Student + Warden |
| Warden approves gate pass | Student |
| Warden rejects gate pass | Student |
| Complaint status updated | Student |

### Key Functions (`notification_helpers.php`)

| Function | Purpose |
|---|---|
| `createNotification()` | Insert one notification for a specific user |
| `notifyFacultyByRole()` | Notify all faculty of a role (optional dept. filter) |
| `getNotificationCount()` | Unread count for navbar badge |
| `getNotificationsForUser()` | Fetch all for inbox display |
| `markNotificationAsRead()` | Mark read + return `target_url` for redirect |
| `buildNotificationTargetUrl()` | Generate deep-link URL for gatepass/complaint |

---

## ⚙️ Configuration

### `config.php` — Database Connection
```php
<?php
date_default_timezone_set('Asia/Kolkata');
$conn = mysqli_connect("localhost", "root", "", "hostel_assist");
?>
```

### `gatepass_workflow.php` — Working Hours
```php
define('COLLEGE_HOUR_START', '10:00');
define('COLLEGE_HOUR_END',   '17:00');
```
Change these constants to adjust working hours. The system will automatically reroute gate passes based on the new values.

### `system_helpers.php` — Auto Schema Migration
Every page calls `ensureComplaintWorkflowSchema()` and `ensureFacultySchema()` which use `SHOW COLUMNS` to auto-add any missing columns — no manual DB migrations needed.

---

## 🔒 Security

| Feature | Status | Notes |
|---|---|---|
| Role-Based Access Control | ✅ Active | Every page checks `$_SESSION['role']` |
| Prepared Statements (MySQLi) | ✅ Active | All DB queries use `bind_param()` |
| Input Validation | ✅ Active | Server-side validation on all forms |
| Student Email Validation | ✅ Active | Only `dse/en` college emails accepted |
| Department Isolation (HOD) | ✅ Active | HOD can only access own dept data |
| Session Management | ✅ Active | PHP sessions with role enforcement |
| Password Hashing | ⚠️ Plain Text | Passwords are stored as-is — acceptable for a local/college intranet deployment but should be upgraded for production |
| CSRF Protection | ❌ Not Implemented | No CSRF tokens — acceptable for local use |
| HTTPS | ❌ Local Only | Running on localhost/XAMPP — enable HTTPS for production |

---

## 📁 File Structure

```
hostelAssistSystem/
│
├── README.md
│
└── HostelAssistSystem/
    │
    ├── 📄 index.html                   # Public landing page
    ├── 📄 login.html                   # Login form (POST → login.php)
    ├── 📄 contact.html                 # Contact page
    ├── 📄 new-complaint.html           # Complaint form (static)
    ├── 📄 faculty_register.html        # Faculty registration form
    │
    ├── 🔧 config.php                   # Database connection + timezone
    ├── 🔧 login.php                    # Auth logic (Student / Faculty / Admin)
    ├── 🔧 logout.php                   # Session destroy + redirect
    ├── 🔧 register.php                 # Student self-registration
    ├── 🔧 faculty_register.php         # Faculty registration handler
    ├── 🔧 success.php                  # Generic success page
    │
    ├── 📊 Student_home.php             # Student dashboard
    ├── 📊 hod_home.php                 # HOD dashboard (dept gate passes)
    ├── 📊 warden_home.php              # Warden dashboard (complaints + passes)
    ├── 📊 admin_home.php               # Admin dashboard
    │
    ├── 🎫 apply_gatepass.php           # Gate pass form + smart status assignment
    ├── 🎫 view_gatepass.php            # Gate pass detail + role-aware actions + print
    ├── 🎫 hod_approve.php              # HOD approve → status: Pending Warden
    ├── 🎫 hod_reject.php               # HOD reject → status: Rejected
    ├── 🎫 warden_approve.php           # Warden approve → status: Approved
    ├── 🎫 warden_reject.php            # Warden reject → status: Rejected
    ├── 🎫 gatepass_workflow.php        # Working hours/days logic + routing function
    ├── 🎫 delete_gatepass.php          # Delete a single gate pass
    │
    ├── 💬 submit_complaint.php         # Complaint submission handler
    ├── 💬 complaint_info.php           # Complaint detail + warden status editor
    ├── 💬 delete_complaint.php         # Delete a single complaint
    │
    ├── 🗑️ bulk_delete_records.php      # Batch delete complaints or gate passes
    ├── 📤 export_report.php            # Report export (role-restricted)
    │
    ├── 🔔 notification.php             # Notification inbox UI
    ├── 🔔 notification_helpers.php     # Full notification engine
    │
    ├── 👤 profile.php                  # Profile view
    ├── 👤 profile_common.php           # Shared profile render helpers
    ├── 👤 edit_profile.php             # Edit profile + upload photo/signature
    │
    ├── 🔑 forgot_password.php          # OTP-based password reset
    │
    ├── 🛠️ system_helpers.php           # Auto-schema migration, role/signature helpers
    │
    ├── 📦 css/
    │   ├── bootstrap.min.css           # Bootstrap 4
    │   ├── templatemo-digital-trend.css # Theme styling
    │   ├── font-awesome.min.css        # Icons (Font Awesome 4)
    │   ├── aos.css                     # Scroll animation styles
    │   ├── owl.carousel.min.css        # Carousel styles
    │   └── owl.theme.default.min.css
    │
    ├── 🎨 js/
    │   ├── jquery.min.js
    │   ├── bootstrap.min.js
    │   ├── custom.js                   # UI helpers (delete mode, search)
    │   ├── aos.js                      # Scroll animations
    │   ├── owl.carousel.min.js
    │   └── smoothscroll.js
    │
    ├── 🖼️ images/                      # Static images
    ├── 📤 uploads/                     # User uploads (profile photos, signatures)
    └── 🔤 fonts/                       # Custom fonts
```

---

## 🐛 Troubleshooting

### 1. "Connection failed" Error
**Cause:** MySQL not running or wrong credentials.
```php
// config.php — verify these match your XAMPP setup:
$conn = mysqli_connect("localhost", "root", "", "hostel_assist");
```
- Start MySQL in the XAMPP Control Panel
- Confirm the database name is `hostel_assist`

---

### 2. Gate Pass Not Appearing on HOD Dashboard
**Checklist:**
- ✅ Gate pass was submitted between **10:00 AM – 5:00 PM**
- ✅ Gate pass was submitted on a **Monday–Saturday** (not Sunday)
- ✅ Student's `department` matches the HOD's `department` in the `faculty` table
- ✅ Server timezone is set to `Asia/Kolkata` in `config.php`
- ✅ The `gate_pass` table has `issue_date` and `issue_time` columns (auto-added on first run)

---

### 3. Gate Pass Not Appearing on Warden Dashboard
**Checklist:**
- ✅ Gate pass `status` must be exactly `Pending Warden` (case-sensitive in DB)
- ✅ If submitted during working hours: HOD must approve first

---

### 4. HOD Cannot View Gate Pass Details
**Cause (fixed in v1.1.0):** An old restriction blocked HOD from viewing any gate pass that wasn't `Pending HOD`.  
**Current behaviour:** HOD can view all statuses for their department. Approve/Reject buttons only appear for `Pending HOD`.

---

### 5. "You are not allowed to view this gatepass"
- **Student**: Trying to view another student's gate pass
- **HOD**: Trying to view a gate pass from a different department

---

### 6. Login Not Working
```
1. Verify the user exists in the correct table (students / faculty / admins)
2. Password is stored as plain text — check it matches exactly (case-sensitive)
3. For students: email must match pattern dseXXXX@git-india.edu.in or enXXXX@git-india.edu.in
4. Clear browser cookies/session and retry
5. Verify PHP session save path has write permissions
```

---

### 7. Notifications Not Appearing
- Check that the `notifications` table exists (auto-created on first page load)
- Verify `recipient_role` and `recipient_id` match the logged-in user's session

---

## 📋 Version History

| Version | Date | Changes |
|---|---|---|
| **1.1.0** | 2026-04-01 | HOD dashboard now shows **all statuses** (Pending HOD, Pending Warden, Approved, Rejected) for complete audit trail |
| | | HOD dashboard filtered to working-hours submissions using `issue_date` + `issue_time` SQL filter |
| | | Fixed: HOD could not view gate pass details for non-Pending-HOD records |
| | | Removed hard `die()` block in `view_gatepass.php` that blocked HOD from viewing processed records |
| **1.0.0** | 2026-03-31 | Initial release |
| | | Gate Pass Management with smart time-based routing |
| | | Complaint system with status progression |
| | | In-app notification engine with deep-links |
| | | Role-based access control (Student, HOD, Warden, Admin) |
| | | Digital signature integration on printed gate passes |
| | | Auto DB schema migration via `system_helpers.php` |
| | | OTP-based password recovery with on-screen fallback |

---

## 📞 Contact & Support

| | |
|---|---|
| 📧 **Email** | thshinge@git-india.edu.in |
| 📞 **Phone** | +91 97651 71034 |
| 🏫 **Institution** | Gharda Institute of Technology |
| 📍 **Address** | Ajinkyatara Girls Hostel, Lavel, Khed, Maharashtra – 415708 |

---

## 🙏 Acknowledgments

- [Bootstrap 4](https://getbootstrap.com/docs/4.6/) — Responsive UI framework
- [Tallyfy National Holidays API](https://tallyfy.com/national-holidays/) — India holiday detection
- [AOS.js](https://michalsnik.github.io/aos/) — Scroll animations
- [Font Awesome 4](https://fontawesome.com/v4/) — Icons
- [TemplateMo Digital Trend](https://templatemo.com/) — Base HTML theme

---

<div align="center">

### Made with ❤️ for Better Hostel Management

[**Go to Top**](#-hostelassist-system) | [**File Structure**](#-file-structure) | [**Troubleshooting**](#-troubleshooting)

---

**HostelAssist System** © 2026 — Gharda Institute of Technology. All rights reserved.

</div>
