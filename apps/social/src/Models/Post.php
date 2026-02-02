<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Post
{
    public static function create(array $data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO posts (account_id, content, status, scheduled_at) 
                VALUES (:account_id, :content, :status, :scheduled_at)";
        $stmt = $db->prepare($sql);
        return $stmt->execute($data);
    }

    public static function getPending()
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT p.*, a.username, a.api_key, a.api_secret, a.access_token, a.access_token_secret 
                             FROM posts p 
                             JOIN accounts a ON p.account_id = a.id 
                             WHERE p.status = 'pending' AND p.scheduled_at <= NOW()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE posts SET status = ?, posted_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}
