-- Migration: Add email verification and report frequency columns to users table
-- Date: 2026-02-03
-- Title: Email Verification and Reports

ALTER TABLE users 
ADD COLUMN verified_at TIMESTAMP NULL AFTER password,
ADD COLUMN verification_token VARCHAR(100) NULL AFTER verified_at,
ADD COLUMN report_frequency ENUM('daily', 'weekly', 'monthly', 'none') DEFAULT 'none' AFTER theme_preference;

-- Update existing users to be verified by default to avoid locking them out
UPDATE users SET verified_at = NOW() WHERE verified_at IS NULL;
