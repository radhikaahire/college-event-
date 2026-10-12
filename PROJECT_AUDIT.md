# 🔍 Comprehensive Project Audit: College Event Management System (CEMS)

**Date of Audit:** October 13, 2026  
**Auditor:** Senior Full-Stack Engineering & Security Team  
**Scope:** Complete Codebase, Database Schema, Workflows, UI/UX, and Security Review  
**Stack Inspected:** PHP 8.2.12, MariaDB 10.4.32 / MySQL, Apache HTTP Server, Bootstrap 5.3  

---

## 1. Actual Project Structure

```
c:\Users\radhika\Downloads\projects\College Event-20261012T171641Z-1-001/
├── includes/
│   ├── auth_check.php        # Session existence validation check
│   ├── db.php                # MySQL database connection (mysqli_connect)
│   ├── header.php            # HTML <head>, Bootstrap 5 CSS, Bootstrap Icons
│   ├── navbar.php            # Top navigation bar with Logout button
│   └── footer.php            # Bootstrap JS bundle & HTML closing tags
├── College Event/            # Original reference screenshot captures
├── add_organizer.php         # Admin: Create faculty/coordinator account
├── add_student.php           # Admin: Create student user and profile
├── admin_dashboard.php       # Admin: Aggregate system metrics & navigation
├── announcements.php         # Student: Read circulars and announcements
├── college_event_management.sql # MariaDB database schema & seed data
├── create_event.php          # Organizer: Schedule a new event
├── declare_winner.php        # Organizer: Select student for 1st/2nd/3rd prize
├── login.php                 # Shared: Role-based authentication endpoint
├── logout.php                # Shared: Session termination
├── manage_participants.php   # Organizer: Approve/reject student applications
├── my_events.php             # Organizer: List events created by current organizer
├── organizer_dashboard.php   # Organizer: Metrics & action cards
├── pending_requests.php      # Organizer: Duplicate view of pending applications
├── post_announcement.php     # Organizer: Broadcast notices for an event
├── student_dashboard.php     # Student: Action cards, podium table, announcements
├── view_events.php           # Student: Browse open events & apply
├── winners.php               # Student: View list of all declared winners
├── start.bat                 # Development utility: Start XAMPP services & site
├── stop.bat                  # Development utility: Stop XAMPP services
├── Login-To-GitHub.bat       # Version control helper: GitHub authentication
├── Push-To-GitHub.bat        # Version control helper: 1-click git push
├── README.md                 # Public repository documentation
└── SQL.txt                   # Baseline SQL reference copy
```

---

## 2. Purpose & Status of Key Modules

| Module / File | Primary Purpose | Current Operational Status |
| :--- | :--- | :--- |
| **`login.php`** | Authenticates users against `users` table and redirects to role dashboard. | ⚠️ **Partially Working:** Authenticates, but uses plain-text passwords and lacks session regeneration. |
| **`auth_check.php`** | Protects routes from unauthenticated guests. | ⚠️ **Partially Working:** Checks `user_id`, but does not verify account `status` or enforce CSRF tokens. |
| **`admin_dashboard.php`** | Displays metrics (total users, students, events, open events, participations). | ✅ **Working:** Real SQL queries calculate stats accurately. Lacks visual charts and user management. |
| **`add_student.php`** | Onboards student account (`users` + `students` tables). | ⚠️ **Partially Working:** Works on happy path, but has no DB transaction, unhashed passwords, and SQL injection. |
| **`add_organizer.php`** | Onboards organizer account (`users` + `organizers` tables). | ⚠️ **Partially Working:** Works on happy path, but has no DB transaction, unhashed passwords, and SQL injection. |
| **`organizer_dashboard.php`**| Summarizes events created and pending approval queue. | ✅ **Working:** Correctly counts events and pending requests for the logged-in organizer. |
| **`create_event.php`** | Allows organizers to schedule a new event. | ⚠️ **Partially Working:** Creates events, but lacks category, image upload, date validation, and prepared statements. |
| **`my_events.php`** | Lists organizer's events and provides "Close Event" button. | ⚠️ **Partially Working:** Allows closing events, but uses an unsafe GET request (`?close=X`) with no CSRF defense. |
| **`manage_participants.php`**| Displays pending applicants with Approve/Reject actions. | ❌ **Broken / Vulnerable:** Approve/Reject operations via GET with **NO authorization check** (IDOR vulnerability). |
| **`pending_requests.php`** | Duplicate view of pending applicants. | ❌ **Redundant:** Identical logic to `manage_participants.php` with the same security flaws. |
| **`declare_winner.php`** | Declares 1st/2nd/3rd place winners for events. | ❌ **Broken Logic:** Organizer can declare winners on *any* organizer's events; blocks declaring winners if event is closed! |
| **`post_announcement.php`** | Posts notice tied to an organizer's event. | ⚠️ **Partially Working:** Works, but SQL is unparameterized and notices cannot be college-wide. |
| **`student_dashboard.php`** | Shows quick links, announced winners, and announcements. | ✅ **Working:** Displays database data, but layout is plain and lacks personal event tracking. |
| **`view_events.php`** | Allows students to browse open events and click "Participate". | ⚠️ **Partially Working:** Registration works, but uses GET request with no check if the event is actually open. |
| **`winners.php`** | Hall of fame displaying winner podium rankings. | ✅ **Working:** Accurately renders database records with color-coded badges. |
| **`announcements.php`** | Displays recent notices sorted by timestamp. | ✅ **Working:** Accurately renders circulars with formatted dates. |

---

## 3. Working vs. Broken Modules Summary

### ✅ Fully Working Modules
1. **Role Redirection:** Admin, Organizer, and Student accounts correctly navigate to their specific dashboard pages.
2. **Dashboard Metric Queries:** Admin and Organizer dashboards calculate accurate counts directly from real database records.
3. **Database Referential Integrity:** Deleting a user or event cascades properly according to MySQL foreign key constraints.
4. **Student View Endpoints:** `winners.php` and `announcements.php` correctly query and render formatted tabular information.

### ⚠️ Partially Working Modules
1. **User Onboarding (`add_student.php`, `add_organizer.php`):** Successfully creates accounts, but failure during the second table insert causes orphan records.
2. **Event Creation (`create_event.php`):** Creates records, but lacks past-date validation, categories, flyers, or participant capacity limits.
3. **Event Participation (`view_events.php`):** Allows 1-click registration, but does not collect team members, phone numbers, or provide an e-ticket.

### ❌ Broken / Critically Vulnerable Modules
1. **Participant Approvals (`manage_participants.php` & `pending_requests.php`):**
   * Actions are triggered via HTTP GET (`?approve=1`, `?reject=1`).
   * No record ownership check: Any organizer can approve/reject participants for another organizer's event.
2. **Winner Declaration (`declare_winner.php`):**
   * Organizers see and can declare winners for events belonging to *other* faculty members.
   * Flawed logic: Winner declaration is prohibited if an event is closed (`status='closed'`), which contradicts real-world workflows.
   * No uniqueness check: An organizer can award "1st place" to multiple students for the same competition.
3. **Event Closure (`my_events.php`):**
   * Event status change is triggered via an unauthenticated GET request (`my_events.php?close=3`).

---

## 4. Confirmed Security Problems

| Issue ID | Vulnerability Type | Severity | Affected Locations | Impact |
| :--- | :--- | :--- | :--- | :--- |
| **SEC-01** | **Plain-Text Passwords** | 🔴 Critical | `login.php`, `add_student.php`, `add_organizer.php` | Passwords stored without hashing (`12345`). Exposed if database is accessed. |
| **SEC-02** | **SQL Injection (SQLi)** | 🔴 Critical | All 10 form/GET processing files | Direct string interpolation into queries allows arbitrary SQL execution. |
| **SEC-03** | **Broken Object Level Auth (IDOR)** | 🔴 Critical | `manage_participants.php`, `declare_winner.php` | Organizers can modify participants and declare winners for other organizers' events. |
| **SEC-04** | **Cross-Site Request Forgery (CSRF)** | 🟠 High | `my_events.php`, `manage_participants.php`, `view_events.php` | Critical state-changing operations occur over GET requests without CSRF tokens. |
| **SEC-05** | **Cross-Site Scripting (XSS)** | 🟠 High | All display views (`echo $row['title']`) | Unescaped output rendering allows script injection via event titles/announcements. |
| **SEC-06** | **Session Fixation / Incomplete Logout** | 🟡 Medium | `login.php`, `logout.php` | Missing `session_regenerate_id()` on login; `logout.php` fails to clear cookie. |
| **SEC-07** | **Inactive Account Bypass** | 🟡 Medium | `includes/auth_check.php` | Deactivated users with active sessions can continue accessing protected pages. |

---

## 5. Database Schema Evaluation

The database `college_event_management` is fundamentally well-structured with normalized tables, but has several architectural constraints that need non-destructive attention:

1. **Missing Unique Constraints on Winners:**
   * Table `winners` only indexes `event_id` and `student_id`.
   * Lacks `UNIQUE KEY unique_event_position (event_id, position)`: Allows multiple 1st places in a single event.
   * Lacks `UNIQUE KEY unique_event_student (event_id, student_id)`: Allows the same student to win 1st and 2nd place simultaneously.
2. **Missing Event Fields for Modern Platforms:**
   * Events lack a `category` (e.g., Technical, Cultural, Sports, Workshop).
   * Events lack an `image_url` or `poster` column for visual engagement.
   * Events lack a `max_participants` capacity limit.
3. **Missing Registration Fields:**
   * `event_participation` lacks attendance tracking (`attended` vs `absent`).
   * Lacks ticket token (`ticket_code`) for verifiable check-ins.

---

## 6. UI/UX Problems & Deficiencies

1. **No Public Entry Page:** Visiting `http://localhost/College-Event/` immediately renders an isolated login card. There is no public landing page showcasing the college fest, schedule, or guidelines.
2. **Visual Monotony:** The interface relies on default Bootstrap blue buttons and plain tables without custom typography, card depth, or visual hierarchy.
3. **No Responsive Table Handling:** Tables overflow horizontally on mobile screens because `.table-responsive` containers are omitted.
4. **Lack of Feedback Transitions:** Forms submit with harsh page reloads. Alerts are plain Bootstrap boxes without toast notifications or micro-animations.
5. **No Visual Analytics:** Dashboards display plain text numbers with no visual distribution graphs or participation charts.

---

## 7. Compatibility & Environment Verification

* **PHP Version:** PHP 8.2.12 (CLI & Apache module confirmed active).
* **Database:** MariaDB 10.4.32 on port 3306 (Confirmed operational).
* **Web Server:** Apache 2.4.58 on port 80 (Confirmed responding with HTTP 200).
* **Browsers Supported:** Chrome, Edge, Firefox, Safari (Modern desktop & mobile).
