ALTER TABLE accounts ADD COLUMN ai_model VARCHAR(50) DEFAULT 'gemini-1.5-flash' AFTER daily_reply_limit;
