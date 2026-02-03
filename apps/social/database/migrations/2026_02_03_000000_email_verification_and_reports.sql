-- Migration: Add email verification and report frequency columns to users table
-- Date: 2026-02-03
-- Title: Email Verification and Reports
-- Description: Uses PREPARE statements to safely add columns only if they don't exist (Idempotent DDL).

-- 1. Add verified_at
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "verified_at";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE users ADD COLUMN verified_at TIMESTAMP NULL AFTER password"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Add verification_token
SET @columnname = "verification_token";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE users ADD COLUMN verification_token VARCHAR(100) NULL AFTER verified_at"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Add report_frequency
SET @columnname = "report_frequency";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE users ADD COLUMN report_frequency ENUM('daily', 'weekly', 'monthly', 'none') DEFAULT 'none' AFTER theme_preference"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 4. Update existing users safely
UPDATE users SET verified_at = NOW() WHERE verified_at IS NULL;
