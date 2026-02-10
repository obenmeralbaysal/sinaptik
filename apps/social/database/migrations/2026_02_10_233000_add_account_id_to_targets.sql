ALTER TABLE targets ADD COLUMN account_id INT NULL AFTER id;
ALTER TABLE targets ADD CONSTRAINT fk_targets_account_id FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE;
