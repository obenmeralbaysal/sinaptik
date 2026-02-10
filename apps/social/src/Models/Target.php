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
        $sql = "INSERT INTO targets (platform, target_username, ai_tone, custom_prompt, account_id, ai_model, user_id, daily_limit, reply_frequency_minutes) 
                VALUES (:platform, :target_username, :ai_tone, :custom_prompt, :account_id, :ai_model, :user_id, :daily_limit, :reply_frequency_minutes)";
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            'platform' => $data['platform'],
            'target_username' => $data['target_username'],
            'ai_tone' => 'supportive', // Fixed: Use valid ENUM value even if ignored logic-wise
            'custom_prompt' => $data['custom_prompt'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'ai_model' => $data['ai_model'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'daily_limit' => $data['daily_limit'] ?? 10,
            'reply_frequency_minutes' => $data['reply_frequency_minutes'] ?? 60
        ]);
    }
    public static function find(int $id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM targets WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function update(int $id, array $data)
    {
        $db = Database::getInstance();
        $sql = "UPDATE targets SET 
                target_username = :target_username, 
                custom_prompt = :custom_prompt, 
                account_id = :account_id,
                ai_model = :ai_model,
                user_id = :user_id,
                daily_limit = :daily_limit,
                reply_frequency_minutes = :reply_frequency_minutes
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'target_username' => $data['target_username'],
            'custom_prompt' => $data['custom_prompt'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'ai_model' => $data['ai_model'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'daily_limit' => $data['daily_limit'] ?? 10,
            'reply_frequency_minutes' => $data['reply_frequency_minutes'] ?? 60,
            'id' => $id
        ]);
    }

    public static function delete(int $id)
    {
        $db = Database::getInstance();
        // Soft delete? For now hard delete as requested
        $stmt = $db->prepare("DELETE FROM targets WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
