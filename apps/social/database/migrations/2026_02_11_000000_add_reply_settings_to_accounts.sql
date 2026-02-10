ALTER TABLE accounts ADD COLUMN reply_frequency_minutes INT DEFAULT 10 AFTER is_active;
ALTER TABLE accounts ADD COLUMN daily_reply_limit INT DEFAULT 10 AFTER reply_frequency_minutes;
ALTER TABLE accounts ADD COLUMN last_reply_check_at DATETIME NULL AFTER daily_reply_limit;
