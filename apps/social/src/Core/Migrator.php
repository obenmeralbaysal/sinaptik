<?php

namespace App\Core;

use PDO;
use Exception;

class Migrator
{
    private $db;
    private $migrationsPath;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = __DIR__ . '/../../database/migrations';
    }

    public function migrate()
    {
        $this->createMigrationsTable();
        
        $ranMigrations = $this->getRanMigrations();
        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);

        $executed = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $ranMigrations)) {
                $this->runMigration($file);
                $executed++;
                echo "Executed migration: $filename\n";
            }
        }

        if ($executed === 0) {
            echo "No pending migrations.\n";
        } else {
            echo "Total $executed migrations executed.\n";
        }
    }

    private function createMigrationsTable()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");
    }

    private function getRanMigrations()
    {
        $stmt = $this->db->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function runMigration($file)
    {
        $sql = file_get_contents($file);
        
        try {
            $this->db->beginTransaction();
            
            // Execute the SQL. Note: PDO exec might not handle multiple statements well depending on driver.
            // But for simple migrations it usually works if buffered.
            $this->db->exec($sql);
            
            $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([basename($file)]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Migration failed for file " . basename($file) . ": " . $e->getMessage());
        }
    }
}
