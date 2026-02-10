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

    public static function allActiveForUser(int $userId, string $platform = null)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM accounts WHERE user_id = :user_id AND is_active = 1";
        $params = ['user_id' => $userId];

        if ($platform) {
            $sql .= " AND platform = :platform";
            $params['platform'] = $platform;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
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
    public static function updateSettings(int $id, int $frequency, int $limit, string $model = 'gemini-1.5-flash')
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE accounts SET reply_frequency_minutes = ?, daily_reply_limit = ?, ai_model = ? WHERE id = ?");
        return $stmt->execute([$frequency, $limit, $model, $id]);
    }

    public static function updateLastReplyCheck(int $id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE accounts SET last_reply_check_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public static function updateTokens(int $id, string $accessToken, ?string $refreshToken, string $expiresAt)
    {
        $db = Database::getInstance();
        $sql = "UPDATE accounts SET access_token = :access_token, expires_at = :expires_at";
        $params = [
            'access_token' => $accessToken,
            'expires_at' => $expiresAt,
            'id' => $id
        ];

        if ($refreshToken) {
            $sql .= ", refresh_token = :refresh_token";
            $params['refresh_token'] = $refreshToken;
        }

        $sql .= " WHERE id = :id";

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}
