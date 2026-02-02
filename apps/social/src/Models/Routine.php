<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Routine
{
    public static function create(array $data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO routines (user_id, platform, name, prompt_settings, schedule_settings, status) 
                VALUES (:user_id, :platform, :name, :prompt_settings, :schedule_settings, :status)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'platform' => $data['platform'] ?? 'x',
            'name' => $data['name'],
            'prompt_settings' => $data['prompt_settings'], // JSON string
            'schedule_settings' => $data['schedule_settings'], // JSON string
            'status' => $data['status'] ?? 'draft'
        ]);
        
        return $db->lastInsertId();
    }

    public static function find(int $id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM routines WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function findAllByUser(int $userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM routines WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
