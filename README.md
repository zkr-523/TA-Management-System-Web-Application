# 🎓 TA Management System – Web Application

A role-based web application for managing Teaching Assistant assignments, workload distribution, and course oversight within a university setting.

---

## 🧠 Overview

Built for Alfaisal University's software engineering program, this system replaces manual TA coordination with a structured platform where managers assign TAs to courses, instructors distribute task hours, and TAs track their workload — all with secure role-based access control.

---

## 👥 User Roles

| Role | Capabilities |
|------|-------------|
| **Manager** | Create/manage users, create courses, assign TAs to courses, view reports |
| **Instructor** | View assigned courses, distribute TA hours across task types |
| **TA** | View course assignments and allocated hours |

---

## ✨ Features

- Secure login with hashed passwords (bcrypt)
- Role-based access control — each role sees only their relevant views
- Course creation and TA assignment management
- Task hour distribution (marking, proctoring, lab supervision)
- Reports dashboard for workload overview
- University-branded UI (Alfaisal University)

---

## 🏗️ Project Structure

```
ta_management/php/
├── index.php              # Login page
├── register.php           # User registration
├── logout.php
├── config.php             # DB connection
├── database.sql           # Full schema + seed data
├── manager/
│   ├── dashboard.php
│   ├── manage_users.php
│   ├── add_user.php / edit_user.php
│   ├── manage_courses.php
│   ├── create_course.php / edit_course.php
│   ├── assign_ta.php
│   └── reports.php
├── instructor/
│   ├── dashboard.php
│   ├── view_course.php
│   └── distribute_hours.php
├── ta/
│   └── dashboard.php
├── includes/
│   ├── header.php
│   └── footer.php
└── assets/
    ├── style.css
    └── script.js
```

---

## 🚀 Getting Started

### Prerequisites

- PHP 7.4+
- MySQL 5.7+
- Apache or Nginx (or use XAMPP/WAMP locally)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/zkr-523/TA-Management-System-Web-Application.git
   ```

2. Place the project folder in your web server's root (e.g., `htdocs/` for XAMPP)

3. Import the database:
   ```bash
   mysql -u root -p < ta_management/php/database.sql
   ```

4. Configure your database connection in `config.php`:
   ```php
   $host = 'localhost';
   $dbname = 'ta_management';
   $username = 'root';
   $password = 'your_password';
   ```

5. Visit `http://localhost/ta_management/php/` in your browser

### Default Login

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | Manager |

---

## 🗄️ Database Schema

```
users           → id, username, password (bcrypt), fullname, email, role
courses         → id, course_code, course_name, students_enrolled, instructor_id
ta_assignments  → id, course_id, ta_id, total_hours
task_distribution → id, assignment_id, marking_hours, proctoring_hours, lab_supervision_hours
```

---

## 🛠️ Tech Stack

- **PHP** – server-side logic
- **MySQL** – relational database
- **HTML / CSS / JavaScript** – frontend
- **Bootstrap 5** – responsive UI
- **Font Awesome** – icons

---

## 👤 Author

**Zakariya Ba Alawi**  
Software Engineering Student — Alfaisal University  
[LinkedIn](https://linkedin.com/in/zakariya-s-ba-alawi-a17977276) · [GitHub](https://github.com/zkr-523)
