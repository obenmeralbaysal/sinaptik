-- Add is_admin column to users if not exists (or we can use role='admin')
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER role;

-- Ensure role column exists and is used
-- (Assuming based on User.php:67 that role exists)

-- Update specific use to admin (Optional: Replace with your actual email)
UPDATE users SET is_admin = 1, role = 'admin' WHERE email = 'test@test.com';

-- Ensure plans table is ready for dynamic paywall
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    interval_unit ENUM('month', 'year') DEFAULT 'month',
    features TEXT, -- JSON string of features
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample English Plans for Dynamic Paywall
INSERT INTO plans (name, price, interval_unit, features) VALUES 
('Starter', 199.00, 'month', '["1 Social Account", "AI Content Assistant", "Basic Analytics"]'),
('Professional', 499.00, 'month', '["5 Social Accounts", "Priority AI Generation", "Advanced Reply Tools", "Strategy Planning"]'),
('Business', 999.00, 'month', '["Unlimited Accounts", "Custom AI Fine-tuning", "Team Collaboration", "24/7 Priority Support"]');
