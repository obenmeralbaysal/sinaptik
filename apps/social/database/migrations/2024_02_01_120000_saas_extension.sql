-- Add SaaS extensions to Sinapp Social

-- 1. Plans table for subscription tiers
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    price_monthly DECIMAL(10, 2) NOT NULL,
    x_account_limit INT NOT NULL,
    daily_post_limit INT NOT NULL,
    daily_reply_limit INT NOT NULL,
    features JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Update users table (Assuming it was minimal or non-existent in initial schema)
-- Initial schema only had account management tables. Let's create a robust users table.
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    plan_id INT,
    trial_ends_at TIMESTAMP NULL,
    subscription_ends_at TIMESTAMP NULL,
    theme_preference ENUM('dark', 'light') DEFAULT 'dark',
    reset_token VARCHAR(100) NULL,
    reset_token_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TRY',
    payment_method ENUM('bank_transfer', 'paytr') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100) NULL, -- For PayTR reference
    payment_proof VARCHAR(255) NULL,   -- For bank transfer receipt upload
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Update existing tables to link with users
ALTER TABLE accounts ADD COLUMN user_id INT AFTER id;
-- For existing data, we might need a default user or handle NULLs.
-- Since it's a fresh start for SaaS, we'll assume a clean state.

-- 5. Insert default plans
INSERT INTO plans (name, slug, price_monthly, x_account_limit, daily_post_limit, daily_reply_limit, features) VALUES
('Starter', 'starter', 199.00, 1, 3, 10, '{"ai_reply": true, "basic_support": true}'),
('Pro', 'pro', 499.00, 5, 10, 50, '{"ai_reply": true, "advanced_support": true, "scheduling": true}'),
('Agency', 'agency', 999.00, 20, 50, 200, '{"ai_reply": true, "priority_support": true, "team_access": true}');

-- 6. Insert default admin (password: admin123 hashed)
INSERT INTO users (name, email, password, role, theme_preference) VALUES
('Admin', 'admin@sinapp.social', '$2y$10$U2T7.x3T0hC6N7Zk.p9hE.s/kP1.Dqf9m3pG3l5n2v1x9y3z.2w6', 'admin', 'dark');
