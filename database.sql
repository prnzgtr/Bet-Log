-- Create Database
CREATE DATABASE IF NOT EXISTS bet_log_casino;
USE bet_log_casino;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    country VARCHAR(50),
    region VARCHAR(50),
    city VARCHAR(50),
    suffix VARCHAR(10),
    permanent_address TEXT,
    sex VARCHAR(10),
    id_type VARCHAR(50),
    profile_image VARCHAR(255),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'suspended', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Responsible Gambling Progress Table
CREATE TABLE IF NOT EXISTS gambling_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lessons_completed BOOLEAN DEFAULT FALSE,
    myths_completed BOOLEAN DEFAULT FALSE,
    videos_completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Session Table for remember me functionality
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample user (password: password123)
INSERT INTO users (username, email, password, phone, first_name, last_name) 
VALUES ('johnnyd_great', 'johnnyseens69@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+63 917 123 4567', 'Johnny', 'Seens');

-- Create Database
CREATE DATABASE IF NOT EXISTS bet_log_casino;
USE bet_log_casino;

-- Users Table - MDTM Assign2: Module 7-8 (Image Upload & Dates)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    country VARCHAR(50),
    region VARCHAR(50),
    city VARCHAR(50),
    suffix VARCHAR(10),
    permanent_address TEXT,
    sex ENUM('Male', 'Female', 'Other', 'Prefer not to say'),
    id_type VARCHAR(50),
    profile_image VARCHAR(255),
    date_of_birth DATE,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    last_profile_update TIMESTAMP NULL,
    status ENUM('active', 'suspended', 'pending') DEFAULT 'active',
    failed_login_attempts INT DEFAULT 0,
    account_locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_status (status)
);

-- Responsible Gambling Progress Table
CREATE TABLE IF NOT EXISTS gambling_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lessons_completed BOOLEAN DEFAULT FALSE,
    myths_completed BOOLEAN DEFAULT FALSE,
    videos_completed BOOLEAN DEFAULT FALSE,
    completed_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Session Table for remember me functionality - MDTM Assign3: Module 9
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
);

-- Login History Table - MDTM Assign1: Module 6 (Security Tracking)
CREATE TABLE IF NOT EXISTS login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    login_status ENUM('success', 'failed') NOT NULL,
    failure_reason VARCHAR(100),
    login_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (login_timestamp),
    INDEX idx_status (login_status)
);

-- Profile Image History Table - MDTM Assign2: Module 7-8
CREATE TABLE IF NOT EXISTS profile_image_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_size INT,
    mime_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_current BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_current (is_current)
);

-- Security Events Table - MDTM Assign1: Module 6
CREATE TABLE IF NOT EXISTS security_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_type ENUM('csrf_violation', 'invalid_session', 'suspicious_activity', 'password_change', 'account_locked') NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    event_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_timestamp (event_timestamp)
);

-- Insert sample user (password: password123)
INSERT INTO users (username, email, password, phone, first_name, last_name, date_of_birth, sex) 
VALUES ('johnnyd_great', 'johnnyseens69@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+63 917 123 4567', 'Johnny', 'Seens', '1995-05-15', 'Male');

-- Clean up expired sessions (run periodically)
CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 DAY
DO
DELETE FROM user_sessions WHERE expires_at < NOW();