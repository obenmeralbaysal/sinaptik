-- Translate plans and default data to English

UPDATE plans SET 
    name = 'Starter', 
    features = '{"ai_reply": true, "basic_support": true, "automated_social": true}' 
WHERE slug = 'starter';

UPDATE plans SET 
    name = 'Professional', 
    features = '{"ai_reply": true, "advanced_support": true, "scheduling": true, "growth_analytics": true}' 
WHERE slug = 'pro';

UPDATE plans SET 
    name = 'Business', 
    features = '{"ai_reply": true, "priority_support": true, "team_access": true, "api_access": true}' 
WHERE slug = 'agency';

-- Update sample user name if needed (optional)
UPDATE users SET name = 'System Administrator' WHERE email = 'admin@sinapp.social';
