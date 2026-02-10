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
                ai_tone = :ai_tone, 
                custom_prompt = :custom_prompt, 
                account_id = :account_id 
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'target_username' => $data['target_username'],
            'ai_tone' => $data['ai_tone'],
            'custom_prompt' => $data['custom_prompt'] ?? null,
            'account_id' => $data['account_id'] ?? null,
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
