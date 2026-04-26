-- ============================================================
--  MediCare Clinic Management System — Database Schema
--  Compatible with MySQL 5.7+
-- ============================================================

CREATE DATABASE IF NOT EXISTS medicare_clinic
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE medicare_clinic;

-- ── Doctors ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS doctors (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    specialty   VARCHAR(100) NOT NULL,
    phone       VARCHAR(20),
    email       VARCHAR(100) UNIQUE,
    fee         DECIMAL(8,2) DEFAULT 0.00,
    available   TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── Patients ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS patients (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    age         INT NOT NULL,
    gender      ENUM('Male','Female','Other') NOT NULL,
    phone       VARCHAR(20),
    address     TEXT,
    blood_group VARCHAR(5),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── Appointments ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    patient_id   INT NOT NULL,
    doctor_id    INT NOT NULL,
    appt_date    DATE NOT NULL,
    appt_time    TIME NOT NULL,
    reason       TEXT,
    status       ENUM('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
    notes        TEXT,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)  REFERENCES doctors(id)  ON DELETE CASCADE
);

-- ── Sample Data ───────────────────────────────────────────────
INSERT INTO doctors (name, specialty, phone, email, fee) VALUES
('Dr. Ayesha Khan',    'Cardiologist',      '0300-1111111', 'ayesha@clinic.pk',  2500.00),
('Dr. Bilal Ahmed',    'General Physician', '0301-2222222', 'bilal@clinic.pk',   1000.00),
('Dr. Sara Malik',     'Dermatologist',     '0302-3333333', 'sara@clinic.pk',    1500.00),
('Dr. Hassan Raza',    'Orthopedic',        '0303-4444444', 'hassan@clinic.pk',  2000.00),
('Dr. Nadia Hussain',  'Pediatrician',      '0304-5555555', 'nadia@clinic.pk',   1200.00);

INSERT INTO patients (name, age, gender, phone, address, blood_group) VALUES
('Ali Raza',       28, 'Male',   '0311-0000001', 'House 5, Block A, Digri', 'B+'),
('Fatima Noor',    35, 'Female', '0311-0000002', 'Street 3, Mirpurkhas',    'O+'),
('Usman Ghani',    52, 'Male',   '0311-0000003', 'Flat 7, Hyderabad',       'A-'),
('Zara Sheikh',    19, 'Female', '0311-0000004', 'Plot 12, Karachi',        'AB+'),
('Tariq Mehmood',  44, 'Male',   '0311-0000005', 'Village Tando, Sindh',    'O-');

INSERT INTO appointments (patient_id, doctor_id, appt_date, appt_time, reason, status) VALUES
(1, 2, CURDATE(), '09:00:00', 'Routine checkup',      'Scheduled'),
(2, 1, CURDATE(), '10:30:00', 'Chest pain follow-up', 'Scheduled'),
(3, 4, CURDATE(), '11:00:00', 'Knee pain',            'Completed'),
(4, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Skin rash', 'Scheduled'),
(5, 5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:30:00', 'Child fever', 'Scheduled');
