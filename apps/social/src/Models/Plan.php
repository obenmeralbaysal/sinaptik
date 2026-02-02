<?php

namespace App\Models;

use App\Core\Database;

class Plan
{
    public static function allActive()
    {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM plans WHERE is_active = 1")->fetchAll();
    }

    public static function findById($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM plans WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $db = Database::getInstance();
        // slug is required by schema
        if (!isset($data['slug'])) {
            $data['slug'] = strtolower(str_replace(' ', '-', $data['name']));
        }
        
        $stmt = $db->prepare("
            INSERT INTO plans (name, slug, price_monthly, x_account_limit, daily_post_limit, daily_reply_limit, features) 
            VALUES (:name, :slug, :price_monthly, :x_account_limit, :daily_post_limit, :daily_reply_limit, :features)
        ");
        
        return $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'price_monthly' => $data['price_monthly'],
            'x_account_limit' => $data['x_account_limit'] ?? 1,
            'daily_post_limit' => $data['daily_post_limit'] ?? 5,
            'daily_reply_limit' => $data['daily_reply_limit'] ?? 20,
            'features' => $data['features'] // Should be JSON string
        ]);
    }
}
