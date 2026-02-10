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
$username = $_GET['username'] ?? 'TwitterDev'; // Default to TwitterDev for public test
$accountId = $_GET['account_id'] ?? 1; // Default to first account

echo "<p>Trying to fetch last tweet for: <strong>$username</strong> using Account ID: <strong>$accountId</strong></p>";
echo "<p><a href='?username=TwitterDev'>Test TwitterDev</a> | <a href='?username=NASA'>Test NASA</a></p>";

try {
    // 1. Fetch Account
    echo "<li>Fetching Account... ";

    // Fallback: Get ANY account from the database if ID 1 not found
    if ($accountId == 1) {
        $pdo = \App\Core\Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM accounts LIMIT 1");
        $acc = $stmt->fetch();
        if ($acc) {
            $accountId = $acc['id'];
        }
    }

    $account = Account::find($accountId);

    if (!$account) {
        die("<span style='color:red; font-weight:bold'>No accounts found in database! Please add an account first.</span>");
    }
    echo "<span style='color:green'>Found Account: " . htmlspecialchars($account['username']) . " (ID: $accountId)</span></li>";

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
