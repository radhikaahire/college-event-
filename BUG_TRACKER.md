# 🐞 Bug Tracker: College Event Management System (CEMS)

**Project:** College Event Management System  
**Audit Date:** October 13, 2026  
**Status Key:** 🔴 Open (Diagnosed) | 🟡 In Progress | 🟢 Resolved & Verified  

---

## Confirmed Bug Log

### BUG-01: Plain-Text Password Storage and Comparison
* **Affected Files:** [`login.php`](login.php), [`add_student.php`](add_student.php), [`add_organizer.php`](add_organizer.php)
* **Severity:** 🔴 Critical
* **Status:** 🔴 Open (Diagnosed)
* **Description:** User passwords are stored in raw text (`12345`) and verified using literal equality in SQL queries.
* **Steps to Reproduce:**
  1. Inspect the database: `SELECT password FROM users WHERE username='admin';`
  2. Observe plain text string `'12345'`.
  3. Create a student in `add_student.php` with password `password123`.
  4. The database stores the raw string `password123`.
* **Expected Behavior:** Passwords must be hashed using a cryptographic one-way algorithm (`password_hash()`) and verified with `password_verify()`.
* **Actual Behavior:** Passwords are fully exposed in the database and logs.
* **Root Cause:** Direct string assignment without invoking `password_hash($password, PASSWORD_BCRYPT)`.
* **Planned Fix:** Implement transparent migration in `login.php` to automatically rehash legacy plaintext passwords on successful login, and use `password_hash()` for all newly created accounts.
* **Test Performed:** Inspected database records; confirmed raw text strings.
* **Test Result:** Verified Vulnerability Confirmed.

---

### BUG-02: Universal SQL Injection (SQLi) via Direct Input Concatenation
* **Affected Files:** `login.php`, `add_student.php`, `add_organizer.php`, `create_event.php`, `my_events.php`, `manage_participants.php`, `pending_requests.php`, `declare_winner.php`, `view_events.php`, `post_announcement.php`
* **Severity:** 🔴 Critical
* **Status:** 🔴 Open (Diagnosed)
* **Description:** User inputs from `$_POST` and `$_GET` are directly concatenated into SQL query strings without prepared statement binding.
* **Steps to Reproduce:**
  1. Navigate to `view_events.php?event_id=1' OR '1'='1`.
  2. The SQL query syntax breaks or executes arbitrary SQL expressions.
* **Expected Behavior:** All SQL queries must use prepared statements with parameter binding (`mysqli_prepare()`, `mysqli_stmt_bind_param()`).
* **Actual Behavior:** Vulnerable to authentication bypass, data extraction, and unauthorized record modification.
* **Root Cause:** Lack of prepared statement architecture; reliance on obsolete string concatenation.
* **Planned Fix:** Convert all SQL operations across the codebase to prepared statements.
* **Test Performed:** Static code analysis; query inspection.
* **Test Result:** Verified Vulnerability Confirmed.

---

### BUG-03: State Modification via HTTP GET Requests (Lack of CSRF Protection)
* **Affected Files:** [`my_events.php`](my_events.php), [`manage_participants.php`](manage_participants.php), [`pending_requests.php`](pending_requests.php), [`view_events.php`](view_events.php)
* **Severity:** 🟠 High
* **Status:** 🔴 Open (Diagnosed)
* **Description:** Closing events, approving/rejecting participants, and applying for events are handled via `$_GET` query parameters.
* **Steps to Reproduce:**
  1. Log in as an organizer.
  2. Visit `http://localhost/College-Event/manage_participants.php?approve=5`.
  3. The status of participant #5 is immediately changed to `approved` without confirmation or POST token.
* **Expected Behavior:** State-changing actions must use HTTP POST with a validated session CSRF token.
* **Actual Behavior:** An embedded image tag `<img src="manage_participants.php?approve=5">` on an external page could silently trigger actions.
* **Root Cause:** Using `$_GET` links (`<a href="?approve=X">`) instead of protected POST forms.
* **Planned Fix:** Convert action links to POST buttons with CSRF verification tokens.
* **Test Performed:** Directly requested action URL via browser; state modified without authorization payload.
* **Test Result:** Verified Vulnerability Confirmed.

---

### BUG-04: Insecure Direct Object Reference (IDOR) on Participant Approvals
* **Affected Files:** [`manage_participants.php`](manage_participants.php), [`pending_requests.php`](pending_requests.php)
* **Severity:** 🔴 Critical
* **Status:** 🔴 Open (Diagnosed)
* **Description:** An organizer can approve or reject participation requests for events created by *other* organizers.
* **Steps to Reproduce:**
  1. Log in as Organizer A (`teacher1`).
  2. Inspect participant ID #6 applying to Organizer B's event.
  3. Send request `manage_participants.php?approve=6`.
  4. The query `UPDATE event_participation SET approval_status='approved' WHERE id='6'` succeeds because there is no `WHERE created_by = '$uid'` ownership check.
* **Expected Behavior:** Only the organizer who created the event should have permission to approve or reject participants.
* **Actual Behavior:** Any authenticated organizer can manipulate registrations across all college departments.
* **Root Cause:** Missing `JOIN events e ON ep.event_id = e.id WHERE e.created_by = ?` in the UPDATE query.
* **Planned Fix:** Enforce ownership check in the UPDATE query and return a permission denied error if ownership mismatch occurs.
* **Test Performed:** Inspected UPDATE query in line 16 of `manage_participants.php`.
* **Test Result:** Verified Vulnerability Confirmed.

---

### BUG-05: Unauthorized Cross-Organizer Winner Declaration
* **Affected Files:** [`declare_winner.php`](declare_winner.php)
* **Severity:** 🟠 High
* **Status:** 🔴 Open (Diagnosed)
* **Description:** The event dropdown in `declare_winner.php` displays all open events in the entire institution, allowing any organizer to declare winners for other teachers' events.
* **Steps to Reproduce:**
  1. Log in as Organizer `organizer`.
  2. Open `declare_winner.php`.
  3. The dropdown lists events created by Organizer `teacher1`.
  4. The form allows awarding prizes on `teacher1`'s event.
* **Expected Behavior:** Organizers should only see and declare winners for events they created and managed.
* **Actual Behavior:** Unrestricted access to declare winners on any active event.
* **Root Cause:** Query in line 41 reads `SELECT * FROM events WHERE status='open'` without `WHERE created_by = '$uid'`.
* **Planned Fix:** Filter event selection to events created by the logged-in user.
* **Test Performed:** Query inspection in `declare_winner.php`.
* **Test Result:** Verified Vulnerability Confirmed.

---

### BUG-06: Illogical Block on Declaring Winners for Closed Events
* **Affected Files:** [`declare_winner.php`](declare_winner.php)
* **Severity:** 🟡 Medium
* **Status:** 🔴 Open (Diagnosed)
* **Description:** `declare_winner.php` prevents declaring winners if an event is closed (`status='closed'`).
* **Steps to Reproduce:**
  1. An organizer closes registrations for an event in `my_events.php`.
  2. The organizer goes to `declare_winner.php` to declare the winner after the competition ends.
  3. The system displays: *"This event is closed. Winner cannot be declared."*
* **Expected Behavior:** In real-world events, winners are declared *after* registrations are closed and the competition is concluded.
* **Actual Behavior:** Organizers are forced to keep registrations open while declaring winners.
* **Root Cause:** Flawed business logic condition in line 26: `if ($event['status'] === 'closed') { ... }`.
* **Planned Fix:** Allow winner declaration for both `open`, `closed`, and `completed` events managed by the organizer.
* **Test Performed:** Reviewed business logic workflow.
* **Test Result:** Verified Logic Flaw Confirmed.

---

### BUG-07: Permissive Duplicate Winner Positions
* **Affected Files:** [`declare_winner.php`](declare_winner.php), [`college_event_management.sql`](college_event_management.sql)
* **Severity:** 🟡 Medium
* **Status:** 🔴 Open (Diagnosed)
* **Description:** The database schema and application allow awarding multiple students the same position (e.g., three 1st place winners) or awarding multiple positions to the same student in the same event.
* **Steps to Reproduce:**
  1. Declare Student A as "1st" place in Event #3.
  2. Submit the form again declaring Student B as "1st" place in Event #3.
  3. Both records are inserted into `winners`.
* **Expected Behavior:** An event should have at most one official 1st, 2nd, and 3rd place winner, and a student can win at most one podium rank per event.
* **Actual Behavior:** Redundant and conflicting winner assignments pollute the hall of fame.
* **Root Cause:** Missing `UNIQUE KEY (event_id, position)` constraint and missing form validation.
* **Planned Fix:** Add validation before insertion and apply unique constraint handling.
* **Test Performed:** Evaluated schema definition in `college_event_management.sql`.
* **Test Result:** Verified Data Integrity Flaw Confirmed.

---

### BUG-08: Non-Transactional User Onboarding (Orphan Records)
* **Affected Files:** [`add_student.php`](add_student.php), [`add_organizer.php`](add_organizer.php)
* **Severity:** 🟡 Medium
* **Status:** 🔴 Open (Diagnosed)
* **Description:** When onboarding a student or organizer, the user account is inserted first. If the subsequent insert into `students` or `organizers` fails, the `users` account is permanently orphaned without a corresponding profile.
* **Steps to Reproduce:**
  1. Simulate failure on second query (e.g. invalid character or disconnect).
  2. `users` table contains a student user with no record in `students`.
  3. Student logs in and encounters `die("Student profile not found. Please contact admin.")` in `view_events.php`.
* **Expected Behavior:** Both inserts must execute within an atomic transaction (`mysqli_begin_transaction`), rolling back if either fails.
* **Actual Behavior:** Partial database writes result in corrupted state.
* **Root Cause:** Lack of transaction wrapping (`mysqli_commit` / `mysqli_rollback`).
* **Planned Fix:** Wrap onboarding queries in database transactions.
* **Test Performed:** Source code review of lines 20-35 in `add_student.php`.
* **Test Result:** Verified Architectural Flaw Confirmed.

---

### BUG-09: Unchecked Registration for Inactive or Closed Events
* **Affected Files:** [`view_events.php`](view_events.php)
* **Severity:** 🟡 Medium
* **Status:** 🔴 Open (Diagnosed)
* **Description:** When a student applies (`?event_id=X`), the system inserts into `event_participation` without verifying whether event `X` is currently `open`.
* **Steps to Reproduce:**
  1. Note the ID of a closed event (e.g. `event_id=3`).
  2. Student navigates to `view_events.php?event_id=3`.
  3. Registration is created despite the event being closed.
* **Expected Behavior:** The server must verify that the event exists and has `status = 'open'` before accepting an application.
* **Actual Behavior:** Students can register for closed or expired competitions.
* **Root Cause:** Line 24 executes an immediate `INSERT IGNORE` without validating the event's status.
* **Planned Fix:** Check event status in a prepared statement before processing registration.
* **Test Performed:** Examined registration logic in lines 21-28.
* **Test Result:** Verified Logic Flaw Confirmed.

---

### BUG-10: Stored Cross-Site Scripting (XSS) via Unescaped Output
* **Affected Files:** All `.php` presentation views
* **Severity:** 🟠 High
* **Status:** 🔴 Open (Diagnosed)
* **Description:** User-supplied strings like event titles, announcements, student names, and departments are rendered directly via `echo $row[...]` without HTML entity encoding.
* **Steps to Reproduce:**
  1. Create an announcement containing `<script>alert('XSS')</script>`.
  2. Visit `student_dashboard.php` or `announcements.php`.
  3. The raw HTML is executed by the browser.
* **Expected Behavior:** All rendered dynamic strings must be escaped with `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
* **Actual Behavior:** Vulnerable to script injection and session cookie theft.
* **Root Cause:** Lack of an output sanitation wrapper.
* **Planned Fix:** Implement a global helper function `e()` for consistent output escaping.
* **Test Performed:** Static code inspection of all echo statements.
* **Test Result:** Verified Vulnerability Confirmed.

---

### BUG-11: Session Fixation & Incomplete Logout Cleanup
* **Affected Files:** [`login.php`](login.php), [`logout.php`](logout.php)
* **Severity:** 🟡 Medium
* **Status:** 🔴 Open (Diagnosed)
* **Description:** `login.php` does not regenerate session IDs on successful login, and `logout.php` does not wipe `$_SESSION` memory or unset the session cookie.
* **Steps to Reproduce:**
  1. Inspect session cookie `PHPSESSID` before and after login; ID remains unchanged.
  2. Call `logout.php`; server only calls `session_destroy()`.
* **Expected Behavior:** Invoke `session_regenerate_id(true)` upon authentication. During logout, clear `$_SESSION = []` and delete the session cookie.
* **Actual Behavior:** Session fixation vulnerability and dirty session state.
* **Root Cause:** Standard beginner PHP session oversight.
* **Planned Fix:** Modernize login and logout session lifecycles.
* **Test Performed:** Code review of `login.php` line 21 and `logout.php`.
* **Test Result:** Verified Vulnerability Confirmed.

---

### BUG-12: Deactivated User Session Persistence
* **Affected Files:** [`includes/auth_check.php`](includes/auth_check.php)
* **Severity:** 🟡 Medium
* **Status:** 🔴 Open (Diagnosed)
* **Description:** If an administrator marks an account as `inactive` in the database, the user can continue performing actions until their session expires or they log out.
* **Steps to Reproduce:**
  1. Student logs in.
  2. Admin updates user record to `status='inactive'`.
  3. Student refreshes page; `auth_check.php` only checks `isset($_SESSION['user_id'])`, so the student remains logged in.
* **Expected Behavior:** Active session checks should verify that the account remains active in the database.
* **Actual Behavior:** Banned or deactivated users retain full operational access.
* **Root Cause:** Session cache does not validate against user status.
* **Planned Fix:** Add status check in `auth_check.php` (cached per request).
* **Test Performed:** Logic flow inspection.
* **Test Result:** Verified Vulnerability Confirmed.

---

### BUG-13: Redundant Duplicate File (`pending_requests.php` vs `manage_participants.php`)
* **Affected Files:** [`manage_participants.php`](manage_participants.php), [`pending_requests.php`](pending_requests.php)
* **Severity:** 🟢 Low (Code Quality)
* **Status:** 🔴 Open (Diagnosed)
* **Description:** Both files contain identical logic and markup for approving and rejecting pending requests.
* **Steps to Reproduce:** Compare the two files line-by-line; they perform the identical query and actions.
* **Expected Behavior:** Maintain a single, robust participant management module with filter tabs (`Pending`, `Approved`, `Rejected`, `All`).
* **Actual Behavior:** Two redundant files maintaining identical duplicate bugs.
* **Planned Fix:** Unify functionality into an enhanced `manage_participants.php` and redirect `pending_requests.php` smoothly.
* **Test Performed:** Diff comparison between both files.
* **Test Result:** Confirmed Duplication.

---

### BUG-14: Table Viewport Overflow on Mobile Devices
* **Affected Files:** `admin_dashboard.php`, `student_dashboard.php`, `view_events.php`, `winners.php`, `my_events.php`
* **Severity:** 🟢 Low (UI/UX)
* **Status:** 🔴 Open (Diagnosed)
* **Description:** HTML `<table>` elements are not wrapped inside `<div class="table-responsive">`, causing horizontal page blowout on mobile screens.
* **Steps to Reproduce:**
  1. Open `winners.php` in mobile viewport (375px width).
  2. The table exceeds viewport width, pushing page container margins out of alignment.
* **Expected Behavior:** Tables should scroll smoothly inside a responsive container.
* **Actual Behavior:** Clunky horizontal scroll affecting the entire webpage.
* **Planned Fix:** Wrap all tables in `.table-responsive` and apply mobile card formatting.
* **Test Performed:** Responsive layout audit in browser.
* **Test Result:** Verified UI Defect Confirmed.

---

### BUG-15: Missing Public Entry Page (`index.php`)
* **Affected Files:** Root directory
* **Severity:** 🟡 Medium (UX / Functional)
* **Status:** 🔴 Open (Diagnosed)
* **Description:** Accessing `http://localhost/College-Event/` defaults to directory listing or 403 if index is missing, forcing users directly into `login.php`.
* **Expected Behavior:** A public landing page displaying college fest identity, upcoming events, and login/register calls to action.
* **Actual Behavior:** Non-logged-in visitors see only a raw login box.
* **Root Cause:** Lack of an `index.php` entry point.
* **Planned Fix:** Build a festival-themed public landing page with live countdown timer and event showcase cards.
* **Test Performed:** Checked file system for `index.php`; confirmed absent.
* **Test Result:** Verified Missing Feature Confirmed.
