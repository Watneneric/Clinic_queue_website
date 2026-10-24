CREATE DATABASE IF NOT EXISTS clinic_queue
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinic_queue;

CREATE TABLE IF NOT EXISTS staff_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patients_name (full_name),
    INDEX idx_patients_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(160) NULL,
    status ENUM('BOOKED','CONFIRMED','CANCELLED') NOT NULL DEFAULT 'BOOKED',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id)
      REFERENCES patients(id) ON DELETE RESTRICT,
    INDEX idx_appointments_datetime (appointment_date, appointment_time),
    INDEX idx_appointments_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS queue_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED NULL,
    queue_date DATE NOT NULL,
    queue_number INT UNSIGNED NOT NULL,
    status ENUM('WAITING','CALLED','IN_CONSULTATION','COMPLETED','NO_SHOW') NOT NULL DEFAULT 'WAITING',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_queue_patient FOREIGN KEY (patient_id)
      REFERENCES patients(id) ON DELETE RESTRICT,
    CONSTRAINT fk_queue_appointment FOREIGN KEY (appointment_id)
      REFERENCES appointments(id) ON DELETE SET NULL,
    CONSTRAINT uq_queue_date_number UNIQUE (queue_date, queue_number),
    CONSTRAINT uq_queue_appointment_date UNIQUE (appointment_id, queue_date),
    INDEX idx_queue_date_status (queue_date, status)
) ENGINE=InnoDB;
