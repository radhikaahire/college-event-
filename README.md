# 🎓 College Event Management System

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mariadb.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

A full-featured web application designed to streamline collegiate event coordination, student participation workflows, announcements, and winner announcements across departments.

---

## 🌟 Key Features

### 👨‍💼 1. Admin Portal
- **Centralized Dashboard**: Live metrics tracking total registered students, faculty organizers, open events, and participations.
- **User Management**: Onboard new students and event organizers with department mappings.
- **System Governance**: Full administrative oversight of events and announcements.

### 🧑‍🏫 2. Organizer Portal
- **Event Lifecycle Management**: Create, schedule, update, and manage college events with venue and capacity constraints.
- **Registration Approvals**: Real-time review and approval/rejection workflow for student participation requests.
- **Winner Declarations**: Record official 1st, 2nd, and 3rd place winners per event.
- **Announcements**: Broadcast urgent event alerts and scheduling updates directly to students.

### 🎓 3. Student Portal
- **Event Discovery**: Browse upcoming open events across college departments.
- **1-Click Registration**: Submit event participation requests with live status tracking (`Pending`, `Approved`, `Rejected`).
- **Hall of Fame & Winners**: View verified results and podium placements.
- **Campus Bulletin**: Access real-time event circulars and notifications.

---

## 🛠️ Tech Stack & Architecture

- **Backend:** PHP 8.2 (Procedural architecture with MySQLi)
- **Database:** MariaDB / MySQL Relational Database
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.3, Bootstrap Icons
- **Local Server Stack:** Apache HTTP Server & MySQL via XAMPP

---

## 🚀 Quick Setup & Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.2+ and MariaDB/MySQL)
- Web Browser (Chrome, Edge, Firefox)

### Steps
1. **Clone the Repository**:
   ```bash
   git clone https://github.com/radhikaahire/college-event-.git
   ```
2. **Move to Server Root**:
   Move the project folder into your XAMPP web root:
   ```
   C:\xampp\htdocs\College-Event
   ```
3. **Database Configuration**:
   - Start **Apache** and **MySQL** in XAMPP.
   - Open phpMyAdmin at [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
   - Create a new database named `college_event_management`.
   - Import the included [`college_event_management.sql`](college_event_management.sql) file.
4. **Launch the Application**:
   Navigate to [http://localhost/College-Event/login.php](http://localhost/College-Event/login.php).

---

## 🔑 Default Demo Accounts

| Role | Username | Password | Access Rights |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `12345` | Complete administrative system control |
| **Organizer** | `teacher1` | `12345` | Event management & participant approvals |
| **Student** | `student1` | `12345` | Event participation & bulletin access |

---

## 👤 Author

Developed by **[Radhika Ahire](https://github.com/radhikaahire)**.
