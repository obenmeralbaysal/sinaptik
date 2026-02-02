CREATE DATABASE IF NOT EXISTS x_poster;
USE x_poster;

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform ENUM('x', 'linkedin', 'instagram', 'facebook') NOT NULL,
    username VARCHAR(255) NOT NULL,
    api_key TEXT,
    api_secret TEXT,
    access_token TEXT,
    access_token_secret TEXT,
    bearer_token TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform ENUM('x', 'linkedin', 'instagram', 'facebook') NOT NULL,
    target_username VARCHAR(255) NOT NULL,
    ai_tone ENUM('supportive', 'ironic', 'critical') DEFAULT 'supportive',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'posted', 'failed') DEFAULT 'pending',
    scheduled_at DATETIME,
    posted_at DATETIME,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    target_id INT NOT NULL,
    original_post_id VARCHAR(255),
    reply_content TEXT NOT NULL,
    status ENUM('pending', 'posted', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (target_id) REFERENCES targets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
