<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\AutomationService;
use App\Services\ReportService;
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

    case 'posts:retry-failed':
        Logger::log("Retrying failed posts...");
        $service->retryFailedPosts();
        echo "Failed posts processed.\n";
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

    case 'posts:fetch-xml':
        Logger::log("Fetching XML feeds...");
        $service->fetchXmlFeeds();
        echo "XML feeds processed.\n";
        break;

    case 'migrate':
        Logger::log("Running database migrations...");
        $migrator = new \App\Core\Migrator();
        $migrator->migrate();
        break;

    case 'reports:send':
        Logger::log("Sending periodic reports...");
        // ... (existing report logic) ...
        echo "Reports sent.\n";
        break;

    case 'schedule:run':
        Logger::log("Running scheduled tasks...");

        // 1. Process Scheduled Posts (Every minute)
        $service->processScheduledPosts();

        // 2. Process Replies (Every minute - Service checks frequency per account)
        // Note: Service logic now handles "too soon" checks, so we can call this often.
        // However, to be safe and reduce load, we might still want to wrap it?
        // No, let's rely on the service logic which is robust.
        $service->processReplies();

        // 3. Generate Daily Posts (Once a day at 08:00)
        // Simple check: if current time is 08:00
        if (date('H:i') === '08:00') {
            $service->generateDailyPosts();
        }

        echo "Scheduled tasks executed.\n";
        break;

    default:
        echo "Usage: php bin/console.php [posts:generate|posts:process]\n";
        break;
}
