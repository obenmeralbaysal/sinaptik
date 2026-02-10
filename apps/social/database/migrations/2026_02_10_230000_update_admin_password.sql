-- Update admin password to 'Admin@2026'
-- Hash: $2y$10$VyAyFcUcfZkInxMna3hB0.0JWOWp.lynpbqd4x2dUwqc8NaqxHXdW

UPDATE users 
SET password = '$2y$10$VyAyFcUcfZkInxMna3hB0.0JWOWp.lynpbqd4x2dUwqc8NaqxHXdW' 
WHERE email = 'admin@sinapp.social';
