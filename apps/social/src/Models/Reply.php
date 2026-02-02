<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Reply
{
    public static function create(array $data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO replies (account_id, target_id, original_post_id, reply_content, status) 
                VALUES (:account_id, :target_id, :original_post_id, :reply_content, :status)";
        $stmt = $db->prepare($sql);
        return $stmt->execute($data);
    }

    public static function exists(string $originalPostId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM replies WHERE original_post_id = ?");
        $stmt->execute([$originalPostId]);
        return $stmt->fetch();
    }

    public static function countToday(int $accountId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM replies WHERE account_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$accountId]);
        return $stmt->fetchColumn();
    }
}
