<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

try {
    $db = \App\Core\Database::getInstance();

    echo "Current Columns in 'targets':\n";
    $stmt = $db->query("DESCRIBE targets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }

    echo "\nLogged Migrations:\n";
    $stmt = $db->query("SELECT * FROM migrations ORDER BY id DESC LIMIT 5");
    $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($migrations as $mig) {
        echo "- " . $mig['migration'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
