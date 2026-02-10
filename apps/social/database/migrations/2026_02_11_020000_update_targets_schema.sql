ALTER TABLE targets ADD COLUMN ai_model VARCHAR(50) DEFAULT NULL AFTER account_id;
-- We are keeping ai_tone for now to avoid breaking existing data immediately, but will ignore it in logic.
-- Ideally we would migrate data: UPDATE targets SET custom_prompt = CONCAT(custom_prompt, ' Tone: ', ai_tone) WHERE ai_tone IS NOT NULL;
