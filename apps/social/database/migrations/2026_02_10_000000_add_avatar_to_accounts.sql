-- Add avatar column to accounts table
ALTER TABLE accounts ADD COLUMN avatar VARCHAR(255) NULL AFTER username;
