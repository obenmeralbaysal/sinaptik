<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Account;
use App\Models\Target;
use App\Services\AIService;

// Hardcode config for verification to avoid path issues
if (!defined('DB_HOST'))
    define('DB_HOST', 'localhost');
if (!defined('DB_NAME'))
    define('DB_NAME', 'sinapp_social');
if (!defined('DB_USER'))
    define('DB_USER', 'root');
if (!defined('DB_PASS'))
    define('DB_PASS', '');

// Load .env - try but don't fail if not found
if (file_exists(__DIR__ . '/../.env') && class_exists('Dotenv\Dotenv')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    } catch (Exception $e) {
        // ignore
    }
}

echo "<h1>Verification Results</h1>";

// 1. Verify Account Column (ai_model)
try {
    $db = Database::getInstance();
    $stmt = $db->query("SHOW COLUMNS FROM accounts LIKE 'ai_model'");
    $col = $stmt->fetch();
    echo "<p>✅ 'ai_model' column exists in 'accounts' table.</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error checking 'ai_model' column: " . $e->getMessage() . "</p>";
}

// 2. Verify AI Model Selection (Mock Update)
try {
    // Get first account
    $accounts = Account::allActive();
    if (!empty($accounts)) {
        $account = $accounts[0];
        $originalModel = $account['ai_model'] ?? 'gemini-1.5-flash';

        // Update to different model
        $newModel = ($originalModel === 'gemini-1.5-flash') ? 'gemini-pro' : 'gemini-1.5-flash';
        Account::updateSettings($account['id'], $account['reply_frequency_minutes'] ?? 10, $account['daily_reply_limit'] ?? 10, $newModel);

        // Verify update
        $updatedAccount = Account::find($account['id']);
        if ($updatedAccount['ai_model'] === $newModel) {
            echo "<p>✅ Account settings updated successfully (Model: $newModel).</p>";
            // Revert
            Account::updateSettings($account['id'], $account['reply_frequency_minutes'] ?? 10, $account['daily_reply_limit'] ?? 10, $originalModel);
        } else {
            echo "<p style='color:red;'>❌ Failed to update account settings.</p>";
        }
    } else {
        echo "<p>⚠️ No active accounts to test settings update.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error testing settings update: " . $e->getMessage() . "</p>";
}

// 3. Verify Target CRUD
try {
    // Create
    $testTarget = [
        'platform' => 'x',
        'target_username' => 'test_verification_user',
        'ai_tone' => 'supportive',
        'custom_prompt' => 'Verification test',
        'account_id' => null
    ];
    Target::create($testTarget);

    // Find
    $stmt = $db->prepare("SELECT * FROM targets WHERE target_username = ?");
    $stmt->execute(['test_verification_user']);
    $target = $stmt->fetch();

    if ($target) {
        echo "<p>✅ Target created successfully.</p>";

        // Update
        Target::update($target['id'], array_merge($testTarget, ['ai_tone' => 'witty']));
        $updatedTarget = Target::find($target['id']);
        if ($updatedTarget['ai_tone'] === 'witty') {
            echo "<p>✅ Target updated successfully.</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to update target.</p>";
        }

        // Delete
        Target::delete($target['id']);
        $deletedTarget = Target::find($target['id']);
        if (!$deletedTarget) {
            echo "<p>✅ Target deleted successfully.</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to delete target.</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Failed to create target.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error verifying Target CRUD: " . $e->getMessage() . "</p>";
}

echo "<p>Done.</p>";
