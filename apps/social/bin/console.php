<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\AutomationService;
use App\Core\Logger;

// Load .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$service = new AutomationService();
$command = $argv[1] ?? null;

switch ($command) {
    case 'posts:generate':
        Logger::log("Generating daily posts...");
        $service->generateDailyPosts();
        echo "Daily posts generated.\n";
        break;

    case 'posts:process':
        Logger::log("Processing scheduled posts...");
        $service->processScheduledPosts();
        echo "Scheduled posts processed.\n";
        break;

    case 'replies:process':
        Logger::log("Processing automated replies...");
        $service->processReplies();
        echo "Automated replies processed.\n";
        break;

    case 'migrate':
        Logger::log("Running database migrations...");
        $migrator = new \App\Core\Migrator();
        $migrator->migrate();
        break;

    default:
        echo "Usage: php bin/console.php [posts:generate|posts:process]\n";
        break;
}
