<?php
// Standalone debugger for RAW Fetch Tweet
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load env
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Models\Account;

echo "<h1>Debug RAW Fetch Tweet</h1>";

// 1. Fetch Account and Token
$accountId = $_GET['account_id'] ?? 1;

// Fallback: Get ANY account from the database if ID 1 not found
if ($accountId == 1) {
    try {
        $pdo = \App\Core\Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM accounts LIMIT 1");
        $acc = $stmt->fetch();
        if ($acc) {
            $accountId = $acc['id'];
        }
    } catch (\Throwable $e) {
        die("<h3>Database Error: " . $e->getMessage() . "</h3>");
    }
}

$account = Account::find($accountId);

if (!$account) {
    die("<h3 style='color:red'>No account found!</h3>");
}

$accessToken = $account['access_token'];
$userId = $_GET['user_id'] ?? '11348282'; // Default NASA ID

echo "<li>Using Account: " . htmlspecialchars($account['username']) . " (ID: $accountId)</li>";
echo "<li>Target User ID: $userId</li>";
echo "<li>Access Token Prefix: " . substr($accessToken, 0, 10) . "...</li>";

// 2. Perform RAW cURL Request
$url = "https://api.x.com/2/users/$userId/tweets?max_results=5&tweet.fields=created_at&exclude=retweets,replies";

echo "<h3>Executing Request:</h3>";
echo "<pre>GET $url</pre>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $accessToken,
    "Content-Type: application/json"
]);

// Capture headers
$headers = [];
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
    $len = strlen($header);
    $header = explode(':', $header, 2);
    if (count($header) < 2)
        return $len;
    $headers[trim($header[0])] = trim($header[1]);
    return $len;
});

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 3. Show Result
echo "<h3>HTTP Status: $httpCode</h3>";

if ($curlError) {
    echo "<h3 style='color:red'>Curl Error: $curlError</h3>";
}

// Show Rate Limits
if (isset($headers['x-rate-limit-remaining'])) {
    echo "<li>Rate Limit: " . $headers['x-rate-limit-remaining'] . " / " . $headers['x-rate-limit-limit'] . "</li>";
    echo "<li>Reset Time: " . date('H:i:s', $headers['x-rate-limit-reset']) . "</li>";
} else {
    echo "<li>No Rate Limit Headers found (Possible Auth/Plan Issue)</li>";
}

echo "<h3>Response Body:</h3>";
echo "<pre style='background:#eee;padding:10px;border:1px solid #ccc'>";
echo htmlspecialchars($response);
echo "</pre>";

// Pretty JSON if possible
$json = json_decode($response, true);
if ($json) {
    echo "<h3>Pretty JSON:</h3>";
    echo "<pre>";
    print_r($json);
    echo "</pre>";
}
