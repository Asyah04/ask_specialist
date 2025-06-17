-- Create database
CREATE DATABASE IF NOT EXISTS ask_specialist;
USE ask_specialist;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(255) UNIQUE NOT NULL,
    address VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'specialist') NOT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255),
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- Answers table
CREATE TABLE IF NOT EXISTS answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Answer votes table
CREATE TABLE IF NOT EXISTS answer_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    answer_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (answer_id, user_id)
);

-- Specialist applications table
CREATE TABLE IF NOT EXISTS specialist_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    certificate_image VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES 
('Mathematics', 'Questions related to mathematics, algebra, calculus, etc.'),
('Physics', 'Questions about physics, mechanics, thermodynamics, etc.'),
('Computer Science', 'Programming, algorithms, data structures, etc.'),
('Chemistry', 'Questions about chemical reactions, elements, compounds, etc.'),
('Biology', 'Questions about living organisms, cells, genetics, etc.'),
('Engineering', 'Questions about various engineering disciplines'),
('Languages', 'Questions about different languages and linguistics'),
('History', 'Questions about historical events and periods'),
('Geography', 'Questions about countries, regions, and physical features'),
('General Knowledge', 'General questions and miscellaneous topics');

-- Insert default admin user (password: admin123)
-- Using proper bcrypt hash for 'admin123'
INSERT INTO users (username, password, email, phone, address, role) VALUES 
('admin', '$2y$12$AdMNE1JPljIYqgad3dYybusv.6KeJKLBlRDCQmhCZMI5bYs1l0ocm', 'admin@example.com', '1234567890', 'Admin Address', 'admin'); 

-- Create online_status table
CREATE TABLE IF NOT EXISTS online_status (
    user_id INT PRIMARY KEY,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_online TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add index for faster queries
CREATE INDEX idx_last_seen ON online_status(last_seen); 