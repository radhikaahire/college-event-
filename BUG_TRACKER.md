# 🐞 Bug Tracker: College Event Management System (CEMS)

**Project:** College Event Management System  
**Audit Date:** October 13, 2026  
**Final Verification Date:** October 13, 2026  
**Status Key:** 🟢 Resolved & Verified (15/15 Fixed)  

---

## Executive Summary
Every diagnosed issue across authentication, database integrity, access boundaries, output sanitization, and responsive UI has been systematically repaired, refactored, and verified with automated test suites (`tests/test_suite.php`) and live browser validation.

---

## Confirmed Bug Log & Resolution Status

### BUG-01: Plain-Text Password Storage and Comparison
* **Affected Files:** [`login.php`](login.php), [`add_student.php`](add_student.php), [`add_organizer.php`](add_organizer.php)
* **Severity:** 🔴 Critical
* **Status:** 🟢 Resolved & Verified
* **Description:** User passwords were stored in raw text (`12345`) and verified using literal equality in SQL queries.
* **Fix Applied:** Implemented transparent Bcrypt migration in `login.php`. When a user with an unhashed password logs in, the password is verified and automatically rehashed with `password_hash($password, PASSWORD_BCRYPT)` and saved to the database. All new user accounts created in `add_student.php` and `add_organizer.php` are salted and hashed with Bcrypt prior to insertion.
* **Test Performed:** Executed `TC-09`, `TC-11`, `TC-12`, and `TC-19` in automated test suite. Verified database hashes using MariaDB CLI (`$2y$10$...`).
* **Test Result:** Pass (100% cryptographic hashing enforced).

---

### BUG-02: Universal SQL Injection (SQLi) via Direct Input Concatenation
* **Affected Files:** All application endpoints (`login.php`, `add_student.php`, `add_organizer.php`, `create_event.php`, `my_events.php`, `manage_participants.php`, `declare_winner.php`, `view_events.php`, `post_announcement.php`)
* **Severity:** 🔴 Critical
* **Status:** 🟢 Resolved & Verified
* **Description:** User inputs from `$_POST` and `$_GET` were directly concatenated into SQL strings without parameter binding.
* **Fix Applied:** Re-engineered all SQL queries across the entire codebase to use parameterized prepared statements (`mysqli_prepare()`, `mysqli_stmt_bind_param()`, `mysqli_stmt_execute()`).
* **Test Performed:** Simulated single-quote injection payloads (`1' OR '1'='1`) across search and ID parameters. Executed automated query boundary checks.
* **Test Result:** Pass (Zero string interpolation in SQL statements).

---

### BUG-03: State Modification via HTTP GET Requests (Lack of CSRF Protection)
* **Affected Files:** [`my_events.php`](my_events.php), [`manage_participants.php`](manage_participants.php), [`pending_requests.php`](pending_requests.php), [`view_events.php`](view_events.php), [`post_announcement.php`](post_announcement.php), [`declare_winner.php`](declare_winner.php)
* **Severity:** 🟠 High
* **Status:** 🟢 Resolved & Verified
* **Description:** Status toggling, participant approvals, registrations, announcements, and winner declarations operated via GET parameters with no request origin verification.
* **Fix Applied:** Converted all state-changing actions to HTTP POST forms protected with cryptographically secure, session-tied CSRF tokens (`csrf_token()`, `csrf_field()`, `verify_csrf()`).
* **Test Performed:** Executed `TC-07` (tampered token submission rejected with HTTP 403) and verified all workflow POST forms.
* **Test Result:** Pass (All state-modifying actions strictly require validated POST CSRF tokens).

---

### BUG-04: Insecure Direct Object Reference (IDOR) on Participant Approvals
* **Affected Files:** [`manage_participants.php`](manage_participants.php), [`pending_requests.php`](pending_requests.php)
* **Severity:** 🔴 Critical
* **Status:** 🟢 Resolved & Verified
* **Description:** Any authenticated organizer could approve or reject participant applications belonging to events created by other faculty members.
* **Fix Applied:** Enforced ownership constraints in the SQL UPDATE statement: `JOIN events e ON ep.event_id = e.id WHERE ep.id = ? AND e.created_by = ?`. Organizers can only manage participants for events they own.
* **Test Performed:** Executed `TC-23` and cross-account authorization simulations.
* **Test Result:** Pass (Unauthorized modifications rejected with zero affected rows).

---

### BUG-05: Unauthorized Cross-Organizer Winner Declaration
* **Affected Files:** [`declare_winner.php`](declare_winner.php)
* **Severity:** 🟠 High
* **Status:** 🟢 Resolved & Verified
* **Description:** The event selection list in `declare_winner.php` showed events from all organizers, allowing coordinators to declare podium winners for other teachers' competitions.
* **Fix Applied:** Restricted event selection and submission queries with `WHERE created_by = ?`. Attempting to submit for another organizer's event triggers a permission denied error.
* **Test Performed:** Verified event dropdown only lists events owned by the logged-in session user.
* **Test Result:** Pass (Cross-organizer winner declaration strictly prohibited).

---

### BUG-06: Illogical Block on Declaring Winners for Closed Events
* **Affected Files:** [`declare_winner.php`](declare_winner.php)
* **Severity:** 🟡 Medium
* **Status:** 🟢 Resolved & Verified
* **Description:** The system blocked winner declarations if an event was closed (`status = 'closed'`), defying real-world workflow where winners are declared after registration closes.
* **Fix Applied:** Removed the artificial `closed` block. Coordinators can now declare winners for open, closed, or completed events as long as the event belongs to them.
* **Test Performed:** Verified podium declaration form for closed event #3 and #4.
* **Test Result:** Pass (Coordinators can award prizes post-registration closure).

---

### BUG-07: Permissive Duplicate Winner Positions
* **Affected Files:** [`declare_winner.php`](declare_winner.php)
* **Severity:** 🟡 Medium
* **Status:** 🟢 Resolved & Verified
* **Description:** Multiple students could be awarded the same position (e.g. three 1st place winners), or the same student could be awarded multiple medals in one event.
* **Fix Applied:** Added pre-insert verification queries in `declare_winner.php`:
  1. Checks if the requested rank (`1st`, `2nd`, `3rd`) has already been awarded.
  2. Checks if the student already holds another podium placement in the event.
  3. Added a secure Revoke / Delete action to reassign positions cleanly.
* **Test Performed:** Tested duplicate rank assignment; system cleanly rejects with actionable validation message.
* **Test Result:** Pass (Strict 1st, 2nd, and 3rd place podium uniqueness maintained).

---

### BUG-08: Non-Transactional User Onboarding (Orphan Records)
* **Affected Files:** [`add_student.php`](add_student.php), [`add_organizer.php`](add_organizer.php)
* **Severity:** 🟡 Medium
* **Status:** 🟢 Resolved & Verified
* **Description:** Creating students or organizers performed two separate SQL inserts without a transaction. If the secondary insert failed, orphaned user accounts were permanently left behind.
* **Fix Applied:** Wrapped onboarding operations inside atomic database transactions (`mysqli_begin_transaction($conn)`, `mysqli_commit($conn)`, `mysqli_rollback($conn)`).
* **Test Performed:** Executed `TC-11` and `TC-12` in automated test suite.
* **Test Result:** Pass (100% atomic onboarding integrity).

---

### BUG-09: Unchecked Registration for Inactive or Closed Events
* **Affected Files:** [`view_events.php`](view_events.php)
* **Severity:** 🟡 Medium
* **Status:** 🟢 Resolved & Verified
* **Description:** Students could submit registration requests for closed competitions.
* **Fix Applied:** Verified `status = 'open'` in `view_events.php` prior to inserting into `event_participation`. Closed events display a disabled "Registration Closed" badge.
* **Test Performed:** Attempted registration on closed event; rejected with clear flash notification.
* **Test Result:** Pass (Registrations strictly limited to active, open events).

---

### BUG-10: Stored Cross-Site Scripting (XSS) via Unescaped Output
* **Affected Files:** All `.php` presentation templates
* **Severity:** 🟠 High
* **Status:** 🟢 Resolved & Verified
* **Description:** User-supplied strings (event titles, descriptions, student names, departments) were output directly via `echo $row[...]` without entity escaping.
* **Fix Applied:** Built and deployed the global helper function `e($string)` utilizing `htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`. Replaced all raw database echoes with `echo e(...)`.
* **Test Performed:** Injected `<script>alert(1)</script>` into announcements; rendered harmlessly as escaped text.
* **Test Result:** Pass (Stored XSS neutralized across all views).

---

### BUG-11: Session Fixation & Incomplete Logout Cleanup
* **Affected Files:** [`login.php`](login.php), [`logout.php`](logout.php)
* **Severity:** 🟡 Medium
* **Status:** 🟢 Resolved & Verified
* **Description:** Session IDs were not regenerated on login, and `logout.php` only invoked `session_destroy()`, failing to invalidate cookies or clear session memory.
* **Fix Applied:** Added `session_regenerate_id(true)` upon successful authentication in `login.php`. Upgraded `logout.php` to clear `$_SESSION = []`, delete the session cookie with past expiration time, and destroy server-side session state.
* **Test Performed:** Executed `TC-09` (regenerates ID) and `TC-27` (logout cookie invalidation).
* **Test Result:** Pass (Session fixation and residual session leakage resolved).

---

### BUG-12: Deactivated User Session Persistence
* **Affected Files:** [`includes/auth_check.php`](includes/auth_check.php)
* **Severity:** 🟡 Medium
* **Status:** 🟢 Resolved & Verified
* **Description:** If an administrator marked a user account as `inactive`, an active session would stay logged in indefinitely.
* **Fix Applied:** Added per-request database status verification in `includes/auth_check.php`. If an account's database status is no longer `active`, the session is immediately destroyed and the user is redirected to `login.php?error=deactivated`.
* **Test Performed:** Changed user record status to inactive; subsequent request immediately blocked.
* **Test Result:** Pass (Deactivated accounts lose access immediately).

---

### BUG-13: Redundant Duplicate File (`pending_requests.php` vs `manage_participants.php`)
* **Affected Files:** [`manage_participants.php`](manage_participants.php), [`pending_requests.php`](pending_requests.php)
* **Severity:** 🟢 Low (Code Quality)
* **Status:** 🟢 Resolved & Verified
* **Description:** Two identical files maintained duplicated, conflicting logic.
* **Fix Applied:** Unified participant management in `manage_participants.php` with filter tabs (`Pending`, `Approved`, `Rejected`, `All`), event filters, and CSV export. Converted `pending_requests.php` into a seamless permanent redirect to `manage_participants.php?status=pending`.
* **Test Performed:** Verified navigation from organizer dashboard and direct URLs.
* **Test Result:** Pass (Single source of truth established).

---

### BUG-14: Table Viewport Overflow on Mobile Devices
* **Affected Files:** `admin_dashboard.php`, `student_dashboard.php`, `view_events.php`, `winners.php`, `my_events.php`
* **Severity:** 🟢 Low (UI/UX)
* **Status:** 🟢 Resolved & Verified
* **Description:** Tables blew out horizontal layouts on mobile screens (375px width).
* **Fix Applied:** Wrapped all data tables in `<div class="table-responsive">` with custom `.table-cems` typography and responsive padding.
* **Test Performed:** Verified responsive layout across viewports from 375px to 1920px.
* **Test Result:** Pass (Smooth responsive scrolling with zero horizontal blowout).

---

### BUG-15: Missing Public Entry Page (`index.php`)
* **Affected Files:** Root directory
* **Severity:** 🟡 Medium (UX / Functional)
* **Status:** 🟢 Resolved & Verified
* **Description:** Accessing the root URL returned an empty directory listing or forced users into `login.php`.
* **Fix Applied:** Created a responsive, festival-themed landing page `index.php` featuring a live countdown clock, dynamic aggregate metric counters (Total Events, Active Delegates, Participating Departments), a featured event card grid, and role-based action buttons.
* **Test Performed:** Executed `TC-01` in automated test suite.
* **Test Result:** Pass (Modern, attractive campus festival entry experience).

---

## Final Verification Summary
* **Total Confirmed Bugs:** 15
* **Resolved:** 15 (100%)
* **Automated Test Coverage:** 27 Test Cases (`tests/test_suite.php`)
* **Pass Rate:** 27/27 (100.0%)
