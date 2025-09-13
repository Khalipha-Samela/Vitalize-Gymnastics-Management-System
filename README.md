# Vitalize-Gymnastics-Management-System

# Vitalize – Gymnastics Management System

Vitalize is a **PHP-based Gymnastics Management System** that allows fitness coaches to manage training programs and gymnasts to enroll, track attendance, and monitor skill development.  
It provides a dashboard to **add, edit, delete, and view gymnastics programs**, along with enrolments and attendance tracking.

---

## 🚀 Features

- **Program Management**
  - Add, edit, delete, and view gymnastics programs.
  - Columns include: Program Name, Coach, Duration, Skill Level, and Enrolled Gymnasts.
  - Search and filter options.

- **Enrollment Management**
  - Gymnasts can enroll in training programs.
  - Coaches can track enrollment numbers.

- **Attendance Tracking**
  - Track gymnast attendance across sessions.
  - Generate attendance summaries.

- **Responsive Dashboard**
  - User-friendly interface for coaches and gymnasts.
  - Mobile-friendly design.

---

## 📂 Project Structure
vitalize/
│── acrobat.png
│── config.php
│── gymanstics_db.sql
│── index.php
│── login.php
│── logout.php
│── style.css
└── README.md # Project documentation

---
## 🛠️ Installation & Setup

1. **Clone this repository:**
   ```bash
   git clone https://github.com/your-username/vitalize.git
   cd vitalize

2. **Setup database:**
  -Open phpMyAdmin (or MySQL CLI).
  -Create a new database:
    CREATE DATABASE gymanstics_db;
  -Import the provided SQL file:
  -Using phpMyAdmin: go to the new database → Import → select database/gymanstics_db.sql.

3. **Update database connection:**
  -Open db_config.php and update it with your database details:
    $host = "localhost";
    $user = "root";       // your MySQL username
    $pass = "";           // your MySQL password
    $dbname = "gymanstics_db"; // database name

4. **Run the project:**
  -Place the project folder in your local server (htdocs for XAMPP or www for WAMP).
  -Start Apache and MySQL from your local server control panel.
  -Open in your browser:
   http://localhost/vitalize/index.php

💻 Tech Stack
•Backend: PHP 8+
•Frontend: HTML5, CSS3, JavaScript
•Database: MySQL
•Server: Apache (XAMPP/WAMP/LAMP)

📸 Screenshots
<img width="940" height="411" alt="Image" src="https://github.com/user-attachments/assets/ddbadb60-c3b0-43bb-ba42-076ef72e5e90" />

<img width="940" height="412" alt="Image" src="https://github.com/user-attachments/assets/d55333dd-60b0-4e2c-862d-3d09138c9ffe" />

<img width="940" height="412" alt="Image" src="https://github.com/user-attachments/assets/f6cd107f-5d67-487e-bfd8-43b402e7b813" />

<img width="940" height="303" alt="Image" src="https://github.com/user-attachments/assets/14003d8e-1f83-4b78-ba4d-742c5c7d7822" />

<img width="940" height="410" alt="Image" src="https://github.com/user-attachments/assets/49860d02-f413-42aa-87ed-42503d213276" />

<img width="940" height="412" alt="Image" src="https://github.com/user-attachments/assets/7a7f127e-e6c2-4edd-84bc-cd0b676d25f3" />

👨‍💻 Author
Developed by Khalipha Samela for a school project.✨
Feel free to reach out with feedback or contributions!




