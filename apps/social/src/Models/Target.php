<?php

namespace App\Models;

use App\Core\Database;

class Target
{
    public static function allActive()
    {
        $db = Database::getInstance();
        $sql = "SELECT t.*, a.username as account_username 
                FROM targets t 
                LEFT JOIN accounts a ON t.account_id = a.id 
                WHERE t.is_active = 1";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    public static function create(array $data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO targets (platform, target_username, ai_tone, custom_prompt, account_id) 
                VALUES (:platform, :target_username, :ai_tone, :custom_prompt, :account_id)";
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            'platform' => $data['platform'],
            'target_username' => $data['target_username'],
            'ai_tone' => $data['ai_tone'] ?? 'supportive',
            'custom_prompt' => $data['custom_prompt'] ?? null,
            'account_id' => $data['account_id'] ?? null
        ]);
    }
}
