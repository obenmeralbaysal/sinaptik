<?php
// Remote Verification Script
// Access this file via browser: http://your-domain.com/test_db_fix.php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>DB Fix Verification</title>
    <style>
        body { font-family: monospace; background: #222; color: #eee; padding: 20px; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #facc15; }
        .box { background: #333; padding: 15px; border-radius: 5px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Database Fix Verification</h1>
";

try {
    $db = \App\Core\Database::getInstance();

    echo "<div class='box'>";
    echo "<h3>1. Checking Schema</h3>";

    // Check if column exists
    echo "Checking 'account_id' column in 'targets' table... ";
    $stmt = $db->query("SHOW COLUMNS FROM targets LIKE 'account_id'");
    if ($stmt->fetch()) {
        echo "<span class='success'>✅ EXISTS</span><br>";
        echo "<small>The migration 2026_02_10_233000_add_account_id_to_targets.sql has been applied.</small>";
    } else {
        echo "<span class='error'>❌ MISSING</span><br>";
        echo "<p class='warning'>⚠️ You need to run migrations!</p>";
        echo "Run: <code>php bin/console.php migrate</code> in your terminal.";
    }
    echo "</div>";

    echo "<div class='box'>";
    echo "<h3>2. Testing Query</h3>";
    echo "Executing <code>App\Models\Target::allActive()</code>...<br><br>";

    $start = microtime(true);
    $targets = \App\Models\Target::allActive();
    $duration = round((microtime(true) - $start) * 1000, 2);

    echo "<span class='success'>✅ Query executed successfully in {$duration}ms.</span><br>";
    echo "Found <strong>" . count($targets) . "</strong> active targets.<br>";

    if (count($targets) > 0) {
        echo "<br><strong>Sample Data:</strong><br>";
        echo "<pre>" . print_r($targets[0], true) . "</pre>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='box' style='border: 1px solid #f87171'>";
    echo "<h3 class='error'>❌ ERROR</h3>";
    echo "<strong>" . get_class($e) . ":</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</body></html>";
