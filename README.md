# 🏥 MediCare — Clinic Management System

A complete Clinic Management System built with:
- **Frontend**: HTML5 + CSS3 + Vanilla JavaScript
- **Backend**: PHP 7.4+ with PDO (REST API)
- **Database**: MySQL (via PDO — fast, secure, prepared statements)
- **Python**: Report generator script (mysql-connector-python)

---

## 📁 Project Structure

```
clinic/
├── index.html              ← Single-page frontend (all UI)
├── php/
│   ├── config.php          ← Database connection (PDO)
│   └── api.php             ← REST API (CRUD for all entities)
├── python/
│   └── report.py           ← Python report generator
├── sql/
│   └── clinic_db.sql       ← DB schema + sample data
└── README.md
```

---

## ⚙️ Setup

### 1. Database (MySQL)
```sql
-- Import via CLI:
mysql -u root -p < sql/clinic_db.sql

-- Or open phpMyAdmin → Import → select clinic_db.sql
```

### 2. PHP Config
Edit `php/config.php`:
```php
define('DB_USER', 'root');      // your MySQL username
define('DB_PASS', 'your_pass'); // your MySQL password
```

### 3. Web Server
Place the `clinic/` folder in your web server root:
- **XAMPP**: `C:/xampp/htdocs/clinic/`
- **WAMP**:  `C:/wamp64/www/clinic/`
- **Linux**: `/var/www/html/clinic/`

Then open: `http://localhost/clinic/`

### 4. Python Report Script
```bash
pip install mysql-connector-python

# Run report for today
python python/report.py

# Report for specific date
python python/report.py --date 2025-01-15

# Save to file
python python/report.py --save
```
Update DB credentials in `python/report.py` if needed.

---

## ✅ Features

| Module       | Features                                               |
|--------------|--------------------------------------------------------|
| Dashboard    | Stats (patients, doctors, today's appointments, done) + today's schedule |
| Patients     | Add, search, delete patients; blood group, contact info |
| Doctors      | Add, delete doctors; specialty, fee, contact           |
| Appointments | Book, filter by date, update status (Scheduled/Completed/Cancelled), delete |
| Python       | Terminal report with overview stats, schedule, top doctors |

---

## 🗄️ Database Tables

| Table        | Columns                                                  |
|--------------|----------------------------------------------------------|
| patients     | id, name, age, gender, phone, address, blood_group, created_at |
| doctors      | id, name, specialty, phone, email, fee, available, created_at |
| appointments | id, patient_id, doctor_id, appt_date, appt_time, reason, status, notes, created_at |

Foreign keys: `appointments.patient_id → patients.id`, `appointments.doctor_id → doctors.id`

---

## 🔒 Security
- All DB queries use **PDO prepared statements** (SQL injection safe)
- Input is sanitized on both PHP and JS sides
- Passwords never stored; extend with PHP `password_hash()` for login
