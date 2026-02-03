-- Add is_admin column to users if not exists (or we can use role='admin')
-- ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER role;

-- Ensure role column exists and is used
-- (Assuming based on User.php:67 that role exists)

-- Update specific use to admin (Optional: Replace with your actual email)
UPDATE users SET is_admin = 1, role = 'admin' WHERE email = 'test@test.com';

-- Ensure plans table is ready for dynamic paywall
-- Ensure plans table is ready for dynamic paywall
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS plans;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    price_monthly DECIMAL(10, 2) NOT NULL,
    x_account_limit INT DEFAULT 1,
    daily_post_limit INT DEFAULT 5,
    daily_reply_limit INT DEFAULT 20,
    features TEXT, -- JSON string of features
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample English Plans for Dynamic Paywall
INSERT INTO plans (name, slug, price_monthly, x_account_limit, daily_post_limit, daily_reply_limit, features) VALUES 
('Starter', 'starter', 199.00, 1, 3, 10, '["1 Social Account", "AI Content Assistant", "Basic Analytics"]'),
('Professional', 'professional', 499.00, 5, 10, 50, '["5 Social Accounts", "Priority AI Generation", "Advanced Reply Tools", "Strategy Planning"]'),
('Business', 'business', 999.00, 999, 50, 200, '["Unlimited Accounts", "Custom AI Fine-tuning", "Team Collaboration", "24/7 Priority Support"]');
