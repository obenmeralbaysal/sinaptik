<?php
// Standalone debugger for Fetch Tweet Logic
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load env
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Models\Account;
use App\Providers\XProvider;

echo "<h1>Debug Fetch Tweet</h1>";

// Hardcode or get params
$username = $_GET['username'] ?? 'elonmusk';
$accountId = $_GET['account_id'] ?? 1; // Default to first account

echo "<p>Trying to fetch last tweet for: <strong>$username</strong> using Account ID: <strong>$accountId</strong></p>";

try {
    // 1. Fetch Account
    echo "<li>Fetching Account... ";
    $account = Account::find($accountId);

    if (!$account) {
        die("<span style='color:red'>Account not found!</span>");
    }
    echo "<span style='color:green'>Found Account: " . htmlspecialchars($account['username']) . "</span></li>";

    // 2. Init Provider
    echo "<li>Initializing XProvider... ";
    $xProvider = new XProvider();
    $xProvider->authenticate($account);
    echo "<span style='color:green'>Authenticated.</span></li>";

    // 3. Fetch Tweet
    echo "<li>Fetching User Last Tweet... <br/>";
    $tweet = $xProvider->getUserLastTweet($username);

    if ($tweet) {
        echo "<pre style='background:#eee;padding:10px;border:1px solid #ccc'>";
        print_r($tweet);
        echo "</pre>";
    } else {
        echo "<span style='color:orange'>No tweets found or API error.</span>";
    }
    echo "</li>";

} catch (Throwable $e) {
    echo "<h2>Error!</h2>";
    echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}
