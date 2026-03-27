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


-- Clean up expired sessions (run periodically)
CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 DAY
DO
DELETE FROM user_sessions WHERE expires_at < NOW();

CREATE TABLE IF NOT EXISTS user_limits (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    daily_loss      DECIMAL(10,2) DEFAULT NULL,
    weekly_loss     DECIMAL(10,2) DEFAULT NULL,
    monthly_loss    DECIMAL(10,2) DEFAULT NULL,
    session_loss    DECIMAL(10,2) DEFAULT NULL,
    max_single_bet  DECIMAL(10,2) DEFAULT NULL,
    max_daily_wager DECIMAL(10,2) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS limit_usage (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    usage_date       DATE NOT NULL,
    usage_week       VARCHAR(10) NOT NULL,
    usage_month      VARCHAR(7)  NOT NULL,
    daily_loss_used  DECIMAL(10,2) DEFAULT 0,
    weekly_loss_used DECIMAL(10,2) DEFAULT 0,
    monthly_loss_used DECIMAL(10,2) DEFAULT 0,
    session_loss_used DECIMAL(10,2) DEFAULT 0,
    daily_wager_used DECIMAL(10,2) DEFAULT 0,
    UNIQUE KEY unique_user_date (user_id, usage_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_week  (user_id, usage_week),
    INDEX idx_user_month (user_id, usage_month)
);

CREATE TABLE IF NOT EXISTS bet_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    game_type  VARCHAR(50) DEFAULT 'arcade',
    bet_amount DECIMAL(10,2) NOT NULL,
    outcome    ENUM('win','loss','pending') DEFAULT 'pending',
    pnl        DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- 1. Demo credits balance for each user
ALTER TABLE users ADD COLUMN IF NOT EXISTS demo_credits DECIMAL(10,2) DEFAULT 0.00;

-- 2. Track which content each user has completed + credits earned
CREATE TABLE IF NOT EXISTS user_content_completions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    content_type ENUM('lesson','myths','video','bonus') NOT NULL,
    content_key  VARCHAR(100) NOT NULL,   -- e.g. 'lesson_1', 'myths_complete', 'video_1'
    credits_earned DECIMAL(10,2) DEFAULT 0,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_content (user_id, content_type, content_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- 3. Credit transaction ledger (earn / spend)
CREATE TABLE IF NOT EXISTS demo_credit_transactions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    type         ENUM('earn','spend','reset','bonus') NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    description  VARCHAR(255),
    source       VARCHAR(100),   -- e.g. 'lesson_1', 'demo_spin', 'daily_reset'
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created (created_at)
);

-- 4. Daily reset tracking (so we only reset once per day)
CREATE TABLE IF NOT EXISTS demo_credit_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    reset_date DATE NOT NULL,
    credits_added DECIMAL(10,2) DEFAULT 0,
    UNIQUE KEY unique_user_date (user_id, reset_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
 
-- Tracks which achievements each user has unlocked
CREATE TABLE IF NOT EXISTS user_achievements (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    achievement_key VARCHAR(80) NOT NULL,
    unlocked_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    credits_awarded DECIMAL(10,2) DEFAULT 0,
    UNIQUE KEY unique_user_achievement (user_id, achievement_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
 
-- Add min_credits column to user_limits if not already added
ALTER TABLE user_limits
    ADD COLUMN IF NOT EXISTS min_credits DECIMAL(10,2) DEFAULT NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS security_question VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS security_answer   VARCHAR(255) DEFAULT NULL;

ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS security_question VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS security_answer VARCHAR(255) DEFAULT NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS lock_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS last_lock_date DATE DEFAULT NULL;