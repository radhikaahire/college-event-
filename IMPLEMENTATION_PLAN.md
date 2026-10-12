# 📋 Implementation Plan: College Event Management System (CEMS)

**Project:** College Event Management System  
**Version:** 2.0 (Modernized, Secure & Accessible Hybrid Edition)  
**Strategy:** Incremental, Safe, Non-Destructive Refactoring  

---

## 🧭 Overview & Core Principles

1. **Zero Data Loss:** Existing tables and demo records (`admin`, `teacher1`, `student1`, etc.) will be fully preserved.
2. **Transparent Security Migration:** Password hashing will automatically upgrade existing plaintext passwords upon next login without breaking existing accounts.
3. **No Unnecessary Framework Overhauls:** Maintain the clean, lightweight native PHP 8.2 + MySQLi + Bootstrap 5 stack.
4. **Distinctive Hybrid UI:**
   * **Public & Student Experience:** Vibrant, festival-inspired aesthetic with strong typography, event category badges, and smooth transitions.
   * **Admin & Organizer Experience:** High-density, professional operational interface with Chart.js analytics, clear data tables, and structured status filters.

---

## 🗓️ Phase-by-Phase Roadmap

```
Phase 1: Project Audit & Diagnostics (Complete)
   │
   ▼
Phase 2: Core Security & Architecture Hardening
   ├── Centralized Helpers & Security Utilities (`includes/helpers.php`)
   ├── Safe Bcrypt Password Migration
   ├── Prepared Statements Architecture
   └── CSRF & Session Management
   │
   ▼
Phase 3: Database Integrity & Safe Enhancements
   ├── Unique Winner Constraints Migration
   ├── Event Category & Poster Metadata Columns
   └── Safe Backward-Compatible Migration Script
   │
   ▼
Phase 4: Hybrid Design System & Prototype
   ├── Centralized CSS Design Tokens (`assets/css/theme.css`)
   ├── Public Fest Landing Page (`index.php`)
   └── Modernized Navigation & Layout Wrappers
   │
   ▼
Phase 5: Repair Existing Modules
   ├── Admin Module (User Management & Chart.js Analytics)
   ├── Organizer Module (Ownership Enforcement, Event Management & Winner Logic)
   └── Student Module (Interactive Event Discovery & Status Tracking)
   │
   ▼
Phase 6: High-Impact Features (E-Tickets & Certificates)
   ├── QR Code Digital E-Ticket Generation
   ├── Organizer Attendance Verification Scanner
   └── Downloadable Merit & Participation PDF Certificates
   │
   ▼
Phase 7: Comprehensive Verification & Test Execution
   ├── Security Testing (SQLi, CSRF, XSS, IDOR)
   ├── Functional End-to-End Workflow Testing
   └── Responsive Mobile & Cross-Browser Testing
```

---

## 🔨 Detailed Phase Breakdown

### Phase 2: Core Security & Architecture Hardening
* **Objective:** Eliminate critical vulnerabilities (Plain-text passwords, SQLi, CSRF, IDOR).
* **Key Tasks:**
  1. Create [`includes/helpers.php`](includes/helpers.php):
     * `e($string)`: Safe output escaping with `htmlspecialchars()`.
     * `generate_csrf_token()` and `verify_csrf_token()`: Form token validation.
     * `require_role($role)`: Centralized role enforcement.
     * `db_query($sql, $params)` / Prepared statement helper.
  2. Refactor [`login.php`](login.php):
     * Use prepared statements to fetch user by username.
     * Check with `password_verify($password, $hash)`. If old plaintext password matches, rehash with `password_hash()` and update database seamlessly.
     * Call `session_regenerate_id(true)`.
  3. Refactor [`logout.php`](logout.php):
     * Wipe `$_SESSION = []`, delete session cookie, destroy session.
  4. Harden [`includes/auth_check.php`](includes/auth_check.php):
     * Verify user `status === 'active'` on each authenticated request.
* **Dependencies:** None.
* **Acceptance Criteria:**
  * No plain-text passwords stored for new accounts.
  * Existing users can log in seamlessly with `12345`.
  * Passing `' OR '1'='1` in inputs does not bypass authentication or trigger SQL syntax errors.

---

### Phase 3: Database Integrity & Safe Schema Enhancements
* **Objective:** Protect data integrity without dropping tables or losing existing records.
* **Key Tasks:**
  1. Add non-breaking columns to `events`:
     * `category` (`ENUM('Technical','Cultural','Sports','Workshop','Hackathon') DEFAULT 'Technical'`).
     * `poster` (`VARCHAR(255) DEFAULT NULL`).
     * `max_participants` (`INT DEFAULT 0` for unlimited).
  2. Add unique constraint to `winners`:
     * Prevent duplicate podium positions for the same event: `UNIQUE (event_id, position)`.
     * Prevent the same student winning multiple ranks in one event: `UNIQUE (event_id, student_id)`.
  3. Create versioned migration script: `migrations/001_safe_schema_enhancements.sql`.
* **Dependencies:** Phase 2.
* **Acceptance Criteria:**
  * Existing tables remain intact with 0 data loss.
  * Duplicate winner entries are safely rejected by MySQL.

---

### Phase 4: Hybrid Visual Design System & Public Experience
* **Objective:** Build a distinctive identity combining a campus-fest theme for students and a high-efficiency dashboard for administrators.
* **Key Tasks:**
  1. Create [`assets/css/theme.css`](assets/css/theme.css):
     * Color tokens: Indigo/Violet primary accents, amber badge highlights, crisp neutral surfaces.
     * Typography, card shadows, button micro-interactions, responsive utility classes.
     * Full support for `prefers-reduced-motion`.
  2. Build Public Landing Page ([`index.php`](index.php)):
     * High-impact hero section with live countdown timer to the flagship college fest.
     * Filterable event showcase grid with category pills.
     * Quick stats counter (`500+ Students`, `15+ Events`, `3 Categories`).
     * Clean header with "Login" and "Explore" CTAs.
  3. Update [`includes/header.php`](includes/header.php), [`includes/navbar.php`](includes/navbar.php), and [`includes/footer.php`](includes/footer.php).
* **Dependencies:** Phase 2, 3.
* **Acceptance Criteria:**
  * Public visitors see a polished fest landing page.
  * Layout is responsive on mobile (375px), tablet (768px), and desktop (1440px).

---

### Phase 5: Complete Functional Repair of Existing Modules

#### A. Admin Module (`admin_dashboard.php`, `add_student.php`, `add_organizer.php`)
* Wrap user onboarding in atomic database transactions (`mysqli_begin_transaction`).
* Hash newly generated passwords.
* Add interactive **Chart.js** visualizations:
  * Department participation distribution (Doughnut chart).
  * Event capacity utilization (Bar chart).
* Add a searchable user roster table with active/inactive status toggle.

#### B. Organizer Module (`organizer_dashboard.php`, `create_event.php`, `my_events.php`, `manage_participants.php`, `declare_winner.php`, `post_announcement.php`)
* **Ownership Enforcement:** Restrict all management queries to `WHERE e.created_by = ?`.
* **Participant Actions via POST:** Replace dangerous GET links with secure POST forms protected by CSRF tokens.
* **Logical Winner Declaration:** Allow declaring winners for concluding/closed events; restrict event selection to the organizer's own events; prevent duplicate positions.
* **Event Creation Upgrades:** Allow selecting event category and entering flyer image path.
* **Export to CSV:** 1-click download of participant lists for attendance marking.

#### C. Student Module (`student_dashboard.php`, `view_events.php`, `winners.php`, `announcements.php`)
* **Safe 1-Click Registration:** Validate event status is `open` before registering.
* **Personalized Status Tracker:** "My Events" section showing progress from `Applied` ➔ `Approved` ➔ `Attended` ➔ `Winner`.
* **Category Search & Filter:** Filter events by name, date, and category.

---

### Phase 6: High-Impact "Showstopper" Features
1. **🎟️ Digital QR Code E-Ticket:**
   * Generate a verifiable ticket for approved students with a scannable QR code.
2. **📱 Organizer QR Check-in:**
   * Rapid attendance verification to mark participants "Attended".
3. **📜 PDF Certificate of Merit / Participation:**
   * Downloadable, styled certificate for winners and verified attendees.

---

### Phase 7: Testing & Quality Assurance
* **Functional Test Suite:** 25+ step-by-step verification tests across all roles.
* **Security Audit:** Re-test SQLi, CSRF, IDOR, and XSS vectors.
* **Cross-Browser & Mobile Verification:** Verify layout rendering on Chrome, Edge, and Firefox.
* **Deliverables:** `TEST_REPORT.md` and updated `README.md`.
