<?php
// Test script to verify database fix
require_once __DIR__ . '/vendor/autoload.php';

// Load env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

try {
    echo "Testing App\Models\Target::allActive()...\n";
    $targets = App\Models\Target::allActive();
    echo "Success! Query executed without error.\n";
    echo "Found " . count($targets) . " active targets.\n";
} catch (PDOException $e) {
    echo "PDOException: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(1);
}
