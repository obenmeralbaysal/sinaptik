<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Post
{
    public static function create(array $data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO posts (account_id, content, status, scheduled_at, media_url) 
                VALUES (:account_id, :content, :status, :scheduled_at, :media_url)";
        $stmt = $db->prepare($sql);

        // Ensure media_url is set, default to null
        if (!array_key_exists('media_url', $data)) {
            $data['media_url'] = null;
        }

        return $stmt->execute($data);
    }

    public static function getPending()
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT p.*, 
                                     a.id as account_pk,
                                     a.username, 
                                     a.api_key, 
                                     a.api_secret, 
                                     a.access_token, 
                                     a.access_token_secret,
                                     a.refresh_token,
                                     a.expires_at
                             FROM posts p 
                             JOIN accounts a ON p.account_id = a.id 
                             WHERE p.status = 'pending' AND p.scheduled_at <= NOW()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getFailed()
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT p.*, 
                                     a.id as account_pk,
                                     a.username, 
                                     a.api_key, 
                                     a.api_secret, 
                                     a.access_token, 
                                     a.access_token_secret,
                                     a.refresh_token,
                                     a.expires_at
                             FROM posts p 
                             JOIN accounts a ON p.account_id = a.id 
                             WHERE p.status = 'failed'");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE posts SET status = ?, posted_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function findAllByUserId(int $userId)
    {
        $db = Database::getInstance();
        $sql = "SELECT p.*, a.username as account_name, a.platform 
                FROM posts p 
                JOIN accounts a ON p.account_id = a.id 
                WHERE a.is_active = 1 AND a.id IN (
                    SELECT id FROM accounts WHERE user_id = ?
                )
                ORDER BY p.scheduled_at DESC, p.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public static function findById(int $id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function delete(int $id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
