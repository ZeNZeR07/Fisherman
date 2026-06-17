CREATE DATABASE IF NOT EXISTS fisherman_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fisherman_db;

CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status ENUM('pending', 'live', 'stopped') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    min_weight DECIMAL(5,2) DEFAULT 0.00,
    prize_quota INT DEFAULT 3,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    sequence_number INT NOT NULL,
    team_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS catch_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    category_id INT NOT NULL,
    team_id INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    caught_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admin_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

