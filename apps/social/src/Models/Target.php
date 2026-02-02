<?php

namespace App\Models;

use App\Core\Database;

class Target
{
    public static function allActive()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM targets WHERE is_active = 1");
        return $stmt->fetchAll();
    }

    public static function create(array $data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO targets (platform, target_username, ai_tone) 
                VALUES (:platform, :target_username, :ai_tone)";
        $stmt = $db->prepare($sql);
        return $stmt->execute($data);
    }
}
