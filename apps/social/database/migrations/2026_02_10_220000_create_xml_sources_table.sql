CREATE TABLE IF NOT EXISTS xml_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(512) NOT NULL,
    selector VARCHAR(255) NOT NULL,
    template TEXT NOT NULL,
    schedule_time TIME NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
