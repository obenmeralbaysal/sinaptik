<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Account
{
    public static function allActive()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM accounts WHERE is_active = 1");
        return $stmt->fetchAll();
    }

    public static function allActiveForUser(int $userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM accounts WHERE user_id = :user_id AND is_active = 1");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function updateOrCreate(array $data)
    {
        $db = Database::getInstance();

        // Check if account already exists for this user and platform
        $stmt = $db->prepare("SELECT id FROM accounts WHERE user_id = :user_id AND platform = :platform AND username = :username");
        $stmt->execute([
            'user_id' => $data['user_id'],
            'platform' => $data['platform'],
            'username' => $data['username']
        ]);
        $existing = $stmt->fetch();

        if ($existing) {
            $sql = "UPDATE accounts SET 
                    access_token = :access_token, 
                    refresh_token = :refresh_token, 
                    expires_at = :expires_at, 
                    avatar = :avatar,
                    is_active = 1 
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => $data['expires_at'],
                'avatar' => $data['avatar'],
                'id' => $existing['id']
            ]);
        } else {
            $sql = "INSERT INTO accounts (user_id, platform, username, avatar, access_token, refresh_token, expires_at) 
                    VALUES (:user_id, :platform, :username, :avatar, :access_token, :refresh_token, :expires_at)";
            $stmt = $db->prepare($sql);
            return $stmt->execute($data);
        }
    }
}
