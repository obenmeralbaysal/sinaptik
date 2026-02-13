<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Account;
use App\Core\Database;

// Bootstrap app (minimal)
$config = require __DIR__ . '/../config/config.php';
// Database connection is handled by Database singleton using env/config

echo "<h1>Account Credentials Check</h1>";

$accounts = Account::allActive();

if (empty($accounts)) {
    echo "No active accounts found.<br>";
}

echo "<table border='1'><tr><th>ID</th><th>Username</th><th>API Key</th><th>API Secret</th><th>Access Token</th><th>Access Secret</th><th>Refresh Token</th></tr>";

foreach ($accounts as $account) {
    echo "<tr>";
    echo "<td>" . $account['id'] . "</td>";
    echo "<td>" . htmlspecialchars($account['username']) . "</td>";

    echo "<td>" . ($account['api_key'] ? '✅ Present (' . strlen($account['api_key']) . ')' : '❌ MISSING') . "</td>";
    echo "<td>" . ($account['api_secret'] ? '✅ Present (' . strlen($account['api_secret']) . ')' : '❌ MISSING') . "</td>";
    echo "<td>" . ($account['access_token'] ? '✅ Present (' . strlen($account['access_token']) . ')' : '❌ MISSING') . "</td>";
    echo "<td>" . ($account['access_token_secret'] ? '✅ Present (' . strlen($account['access_token_secret']) . ')' : '❌ MISSING') . "</td>";
    echo "<td>" . ($account['refresh_token'] ? '✅ Present (' . strlen($account['refresh_token']) . ')' : '❌ MISSING') . "</td>";

    echo "</tr>";
}

echo "</table>";
