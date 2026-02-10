<?php
// Standalone debugger for Posting Tweet Logic
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load env
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Models\Account;
use App\Providers\XProvider;

echo "<h1>Debug Post Tweet</h1>";

// Hardcode or get params
$accountId = $_GET['account_id'] ?? 1; // Default to first account
$message = "Test Tweet from Debug Script " . date('Y-m-d H:i:s');

echo "<p>Trying to post tweet: <strong>$message</strong> using Account ID: <strong>$accountId</strong></p>";

try {
    // 1. Fetch Account
    echo "<li>Fetching Account... ";
    $account = Account::find($accountId);

    if (!$account) {
        // Try getting the first active account if ID 1 fails
        $accounts = Account::allActiveForUser(1, 'x'); // Assuming user_id 1 exists
        if (!empty($accounts)) {
            $account = $accounts[0];
            $accountId = $account['id'];
            echo "<span style='color:orange'>Account ID 1 not found, using ID $accountId ({$account['username']})</span>";
        } else {
            die("<span style='color:red'>Account not found! Please add an account first.</span>");
        }
    } else {
        echo "<span style='color:green'>Found Account: " . htmlspecialchars($account['username']) . "</span></li>";
    }

    // 2. Init Provider
    echo "<li>Initializing XProvider... ";
    $xProvider = new XProvider();
    $xProvider->authenticate($account);
    echo "<span style='color:green'>Authenticated.</span></li>";

    // 3. Post Tweet
    echo "<li>Posting Tweet... <br/>";
    $result = $xProvider->post($message);

    if ($result) {
        echo "<span style='color:green; font-weight:bold'>SUCCESS: Tweet posted!</span>";
    } else {
        echo "<span style='color:red; font-weight:bold'>FAILURE: Posted failed. Check logs.</span>";
    }
    echo "</li>";

} catch (Throwable $e) {
    echo "<h2>Error!</h2>";
    echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}
