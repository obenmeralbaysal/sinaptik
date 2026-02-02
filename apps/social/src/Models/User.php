<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, trial_ends_at) 
            VALUES (:name, :email, :password, :trial_ends_at)
        ");
        
        // 1 day trial
        $trialEnds = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'trial_ends_at' => $trialEnds
        ]);
        
        return (int)$db->lastInsertId();
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function setResetToken(int $id, string $token): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE users 
            SET reset_token = :token, 
                reset_token_expires_at = :expires 
            WHERE id = :id
        ");
        $stmt->execute([
            'token' => $token,
            'expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'id' => $id
        ]);
    }

    public static function isSubscriptionActive(array $user): bool
    {
        if (self::isAdmin($user)) return true;
        
        $now = time();
        $trialEnd = $user['trial_ends_at'] ? strtotime($user['trial_ends_at']) : 0;
        $subEnd = $user['subscription_ends_at'] ? strtotime($user['subscription_ends_at']) : 0;
        
        return ($now < $trialEnd) || ($now < $subEnd);
    }

    public static function isAdmin(array $user): bool
    {
        return ($user['role'] === 'admin') || (isset($user['is_admin']) && $user['is_admin'] == 1);
    }
}
