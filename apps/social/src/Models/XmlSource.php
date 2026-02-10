<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class XmlSource
{
    public static function create(array $data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO xml_sources (user_id, name, url, selector, template, schedule_time, status) 
                VALUES (:user_id, :name, :url, :selector, :template, :schedule_time, :status)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'url' => $data['url'],
            'selector' => $data['selector'],
            'template' => $data['template'],
            'schedule_time' => $data['schedule_time'],
            'status' => $data['status'] ?? 'active'
        ]);
    }

    public static function findAllByUser(int $userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM xml_sources WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM xml_sources WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getDueSources()
    {
        $db = Database::getInstance();
        // Check if current time matches schedule_time (ignoring seconds for loose matching)
        // This is a simple check; a robust system might track 'last_run_at'
        // For now, let's select all active sources. The Command will filter by time or we rely on the Cron running hourly.
        // Better: Select active sources.
        $stmt = $db->prepare("SELECT * FROM xml_sources WHERE status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
