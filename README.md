# 🎓 College Event Management System (CEMS)

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mariadb.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Tests](https://img.shields.io/badge/Automated_Tests-27%2F27_Passed-brightgreen?style=for-the-badge)](tests/test_suite.php)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

A modern, secure, and production-grade academic web application designed to manage collegiate events, technical symposiums, student registrations, approval workflows, announcements, winner declarations, digital QR gate passes, and printable certificates.

---

## 🌟 Distinctive Hybrid Design System

The application employs a dual-experience visual architecture:
1. **Student & Public Experience**: Expressive, festival-inspired creative interface with dynamic countdown clock, aggregate stat counters, responsive event showcase cards, and instant 1-click registration.
2. **Admin & Organizer Experience**: Operational control center featuring compact KPI metric cards, Chart.js analytics, participant review queues, CSV exports, and administrative rosters.

---

## 🚀 Showstopper Features

* 📱 **Digital E-Ticket Passes (`ticket.php`)**: Generates an official gate pass with a self-contained, offline-compatible QR code (`assets/qrcode.min.js`) encoding verifiable student credentials and registration hashes.
* 🏆 **Printable Academic Certificates (`certificate.php`)**: Dual-format academic certificates (Merit for podium winners; Participation for delegates) with landscape A4 print styling (`@media print`), official signatures, and unique verification IDs.
* 📊 **Live Analytics & Visualization**: Integrated Chart.js charts on the Admin Dashboard displaying department-wise participant distributions and event status breakdowns.
* 📥 **One-Click CSV Roster Export**: Organizers can download attendance sheets in CSV format directly from the participant management console.

---

## 🔒 Security Hardening

* Passwords are stored as bcrypt hashes; legacy plaintext credentials are migrated when used.
* SQL queries in the reviewed workflows use prepared statements.
* State-changing actions use CSRF validation, and organizer/student access checks are applied to private workflows.
* Pass QR codes use random, database-backed tokens and can be checked independently at `verify_pass.php`.
* These safeguards reduce risk but do not guarantee that every vulnerability is eliminated.

---

## 👥 Role Workflows & Seed Accounts

The SQL file contains sample role records for local development, but their passwords are intentionally replaced with random bcrypt hashes. No default password is published. Set a local password before logging in:

1. Generate a bcrypt hash in your terminal: `php -r "echo password_hash('Choose-A-Local-Password', PASSWORD_DEFAULT), PHP_EOL;"`
2. Copy the generated hash and run this SQL in your local development database: `UPDATE users SET password = 'PASTE_BCRYPT_HASH_HERE' WHERE username IN ('admin', 'teacher1', 'student1');`
3. For the automated suite, set the same password in your shell before running it. PowerShell: `$env:CEMS_TEST_PASSWORD="Choose-A-Local-Password"; php tests/test_suite.php`

Use unique passwords, keep this application local while testing, and never use development accounts on a live deployment.

---

## 🛠️ Tech Stack & Directory Structure

* **Backend:** PHP 8.2 (Procedural architecture with MySQLi)
* **Database:** MariaDB 10.4.32 / MySQL Relational Database
* **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.3, Bootstrap Icons 1.11, Chart.js, qrcode.js
* **Local Server:** Apache 2.4.58 via XAMPP

```text
College-Event/
├── assets/
│   ├── css/
│   │   └── theme.css              # Hybrid design system tokens & animation styles
│   └── qrcode.min.js              # Offline-compatible QR generator (19.9 KB)
├── includes/
│   ├── auth_check.php             # Session validator & active DB status check
│   ├── db.php                     # MySQLi connection instance
│   ├── footer.php                 # Global site footer
│   ├── header.php                 # HTML <head> with Bootstrap & stylesheets
│   ├── helpers.php                # Security helpers: e(), csrf_token(), verify_csrf(), require_role()
│   └── navbar.php                 # Responsive role-aware navigation bar
├── tests/
│   └── test_suite.php             # Automated 27-point verification test suite
├── add_organizer.php              # Admin: Atomic organizer onboarding transaction
├── add_student.php                # Admin: Atomic student onboarding transaction
├── admin_dashboard.php            # Admin: Control center with KPIs and Chart.js
├── announcements.php              # Public / Student: Campus notices and bulletin feed
├── certificate.php                # Official printable Certificate of Merit / Participation
├── create_event.php               # Organizer: Event creation with prepared statements
├── declare_winner.php             # Organizer: Podium winner declaration (1st, 2nd, 3rd)
├── index.php                      # Public: Festival landing page with live countdown clock
├── login.php                      # Authentication with transparent Bcrypt migration & CSRF
├── logout.php                     # Complete session termination & cookie destruction
├── manage_participants.php        # Organizer: Participant review, approval, CSV export
├── my_events.php                  # Organizer: Event list with status toggle (open/closed)
├── organizer_dashboard.php        # Organizer: Quick action queue & event metrics
├── pending_requests.php           # Seamless redirect to manage_participants.php?status=pending
├── post_announcement.php          # Organizer: Broadcast notice with ownership check
├── student_dashboard.php          # Student: Application tracker, E-pass & certificates
├── ticket.php                     # Digital E-Ticket Pass with secure QR token
├── verify_pass.php                # Public token-based pass verification
├── migrations/
│   └── 2026-10-13-add-qr-token.sql # Existing database upgrade for QR verification
├── view_events.php                # Student: Event discovery & 1-click registration
├── winners.php                    # Public: Hall of Fame podium results
├── BUG_TRACKER.md                 # 15 confirmed bugs cataloged & verified fixed
├── PROJECT_AUDIT.md               # Technical audit of legacy codebase
├── IMPLEMENTATION_PLAN.md         # Architecture and refactoring roadmap
├── TEST_REPORT.md                 # Complete QA automated test execution report
└── college_event_management.sql   # Complete database schema and seed data
```

---

## 🔑 Database Upgrade for Secure QR Passes

For an existing database, run `migrations/2026-10-13-add-qr-token.sql` once in phpMyAdmin or the MariaDB client before opening a ticket. Fresh imports of `college_event_management.sql` already include the column. Set `CEMS_BASE_URL` to the canonical application URL in deployed environments so generated QR links point to the correct host.

## 🏃 Running the Application Locally

1. **Start XAMPP**:
   - Ensure Apache and MySQL are running.
2. **Access the Application**:
   - Open your web browser and navigate to:
     ```
     http://localhost/College-Event/
     ```
3. **Execute Automated Tests**:
   - Run the automated 27-test regression suite from your command prompt:
     ```bash
     php tests/test_suite.php
     ```

---

## 👤 Author & Academic Credits

Developed by **[Radhika Ahire](https://github.com/radhikaahire)** for academic evaluation.
All security, architectural, and design improvements verified on local environment.
