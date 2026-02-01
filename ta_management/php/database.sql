-- Create database
CREATE DATABASE IF NOT EXISTS ta_management;
USE ta_management;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('manager', 'instructor', 'ta') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    students_enrolled INT NOT NULL,
    instructor_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- TA assignments table
CREATE TABLE ta_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    ta_id INT NOT NULL,
    total_hours INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (ta_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (course_id, ta_id)
);

-- Task distribution table
CREATE TABLE task_distribution (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    marking_hours INT NOT NULL DEFAULT 0,
    proctoring_hours INT NOT NULL DEFAULT 0,
    lab_supervision_hours INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES ta_assignments(id) ON DELETE CASCADE
);

-- Insert a default manager account
INSERT INTO users (username, password, fullname, email, role) VALUES 
('admin', '$2y$10$MO0s6vihI5VyhJbPZl3.7eZ4tEJJCbj9ZO8k5sRKWNLPwyfC5YOv.', 'System Admin', 'admin@example.com', 'manager');
-- Default password is 'admin123'
