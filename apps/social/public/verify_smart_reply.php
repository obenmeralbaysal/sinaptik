<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Providers\XProvider;
use App\Models\Account;
use App\Services\AIService;

// Simulating the environment
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

echo "Starting Verification of Smart Reply Job Flow...\n\n";

// 1. Verify XProvider::getUserLastTweet
echo "1. Testing XProvider::getUserLastTweet...\n";
try {
    // innovative_tech is a mock account for testing
    //$account = Account::findByUsername('innovative_tech'); 
    // Mocking account object for provider as it might need keys
    $account = [
        'access_token' => $_ENV['X_ACCESS_TOKEN'] ?? 'mock',
        'access_token_secret' => $_ENV['X_ACCESS_TOKEN_SECRET'] ?? 'mock'
    ];

    $provider = new XProvider();
    // In a real scenario we'd authenticate, but here we might hit the mock search
    // $provider->authenticate($account); 

    // We can't easily test the actual API call without a real authenticated session in this CLI script
    // unless we mock the response or have valid tokens in .env. 
    // For now, checking if the method exists is a good sanity check.
    if (method_exists($provider, 'getUserLastTweet')) {
        echo "✅ Method getUserLastTweet exists in XProvider.\n";
    } else {
        echo "❌ Method getUserLastTweet MISSING in XProvider.\n";
    }

} catch (Exception $e) {
    echo "⚠️  XProvider Test Warning: " . $e->getMessage() . "\n";
}

// 2. Verify TargetController::fetchLastTweet logic
echo "\n2. Testing TargetController Logic (Simulation)...\n";
// This is hard to unit test in a script without a full HTTP request, 
// but we can verify the controller file has the method.
$controllerPath = __DIR__ . '/../src/Controllers/TargetController.php';
$controllerContent = file_get_contents($controllerPath);
if (strpos($controllerContent, 'public function fetchLastTweet()') !== false) {
    echo "✅ fetchLastTweet method found in TargetController.\n";
} else {
    echo "❌ fetchLastTweet method MISSING in TargetController.\n";
}

// 3. Verify Route Registration
echo "\n3. Verifying Route Registration...\n";
$indexPath = __DIR__ . '/index.php';
$indexContent = file_get_contents($indexPath);
if (strpos($indexContent, '/targets/fetch-tweet') !== false) {
    echo "✅ Route '/targets/fetch-tweet' found in public/index.php.\n";
} else {
    echo "❌ Route '/targets/fetch-tweet' MISSING in public/index.php.\n";
}

// 4. Verify View Updates
echo "\n4. Verifying View Updates...\n";
$createView = __DIR__ . '/../views/targets/create.php';
$editView = __DIR__ . '/../views/targets/edit.php';

$createContent = file_get_contents($createView);
$editContent = file_get_contents($editView);

if (strpos($createContent, 'fetchLastTweet()') !== false && strpos($createContent, 'Generate Preview') !== false) {
    echo "✅ create.php contains fetch and preview logic.\n";
} else {
    echo "❌ create.php MISSING fetch/preview logic.\n";
}

if (strpos($editContent, 'fetchLastTweet()') !== false && strpos($editContent, 'Generate Preview') !== false) {
    echo "✅ edit.php contains fetch and preview logic.\n";
} else {
    echo "❌ edit.php MISSING fetch/preview logic.\n";
}

echo "\nVerification Complete.\n";
