<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\MailService;
use App\Core\Config;

// Load environment variables manually if not using the app's bootstrap
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "Testing SendGrid Integration...\n";

$apiKey = Config::get('SENDGRID_API_KEY');
if (empty($apiKey)) {
    echo "ERROR: SENDGRID_API_KEY is not set in .env\n";
    echo "Please set it and try again.\n";
    exit(1);
}

$to = 'test@example.com';
$subject = 'Test Email from Sinapp Social';
$body = 'This is a test email sent via SendGrid integration.';

echo "Attempting to send email to $to...\n";

$result = MailService::send($to, $subject, $body);

if ($result) {
    echo "SUCCESS: Email sent successfully.\n";
} else {
    echo "FAILURE: Email failed to send. Check logs.\n";
    exit(1);
}
