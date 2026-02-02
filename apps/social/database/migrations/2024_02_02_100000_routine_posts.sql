-- R1. Create routines table
CREATE TABLE IF NOT EXISTS routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform VARCHAR(50) DEFAULT 'x',
    name VARCHAR(255) NOT NULL,
    prompt_settings JSON,
    schedule_settings JSON,
    status ENUM('draft', 'active', 'completed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- R2. Update posts table to link to routines
ALTER TABLE posts ADD COLUMN routine_id INT NULL AFTER account_id;
ALTER TABLE posts ADD CONSTRAINT fk_posts_routine FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE SET NULL;
