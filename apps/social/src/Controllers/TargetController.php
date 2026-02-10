<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Target;
use App\Models\User;

class TargetController extends Controller
{
    public function index()
    {
        $user = User::findById($_SESSION['user_id']);
        if (!$user || !User::isSubscriptionActive($user)) {
            header('Location: /pricing');
            exit;
        }

        $targets = Target::allActive();

        $this->render('targets/index', [
            'title' => 'Target Users',
            'active_page' => 'targets',
            'targets' => $targets
        ]);
    }

    public function create()
    {
        $user = User::findById($_SESSION['user_id']);
        $accounts = \App\Models\Account::allActiveForUser($user['id'], 'x');

        $aiService = new \App\Services\AIService();
        $availableModels = $aiService->getAvailableModels();

        $this->render('targets/create', [
            'title' => 'Add Target User',
            'active_page' => 'targets',
            'accounts' => $accounts,
            'available_models' => $availableModels
        ]);
    }

    public function store()
    {
        $data = [
            'platform' => 'x',
            'target_username' => $_POST['target_username'],
            'ai_tone' => 'custom', // Deprecated
            'custom_prompt' => $_POST['custom_prompt'] ?? null,
            'account_id' => !empty($_POST['account_id']) ? (int) $_POST['account_id'] : null,
            'ai_model' => !empty($_POST['ai_model']) ? $_POST['ai_model'] : null,
            'user_id' => !empty($_POST['user_id']) ? $_POST['user_id'] : null,
            'daily_limit' => !empty($_POST['daily_limit']) ? (int) $_POST['daily_limit'] : 10,
            'reply_frequency_minutes' => !empty($_POST['reply_frequency_minutes']) ? (int) $_POST['reply_frequency_minutes'] : 60
        ];

        Target::create($data);
        header('Location: /targets');
    }

    public function edit($id)
    {
        $user = User::findById($_SESSION['user_id']);
        $target = Target::find($id);

        if (!$target) {
            header('Location: /targets');
            exit;
        }

        $accounts = \App\Models\Account::allActiveForUser($user['id'], 'x');

        $aiService = new \App\Services\AIService();
        $availableModels = $aiService->getAvailableModels();

        $this->render('targets/edit', [
            'title' => 'Edit Target User',
            'active_page' => 'targets',
            'target' => $target,
            'accounts' => $accounts,
            'available_models' => $availableModels
        ]);
    }

    public function update($id)
    {
        $data = [
            'target_username' => $_POST['target_username'],
            'custom_prompt' => $_POST['custom_prompt'] ?? null,
            'account_id' => !empty($_POST['account_id']) ? (int) $_POST['account_id'] : null,
            'ai_model' => !empty($_POST['ai_model']) ? $_POST['ai_model'] : null,
            'user_id' => !empty($_POST['user_id']) ? $_POST['user_id'] : null,
            'daily_limit' => !empty($_POST['daily_limit']) ? (int) $_POST['daily_limit'] : 10,
            'reply_frequency_minutes' => !empty($_POST['reply_frequency_minutes']) ? (int) $_POST['reply_frequency_minutes'] : 60
        ];

        Target::update($id, $data);
        header('Location: /targets');
    }

    public function delete($id)
    {
        Target::delete($id);
        header('Location: /targets');
    }

    public function fetchLastTweet()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        // Log to file as well
        error_log("TargetController::fetchLastTweet START");

        header('Content-Type: application/json');

        $username = $_POST['username'] ?? '';
        $accountId = $_POST['account_id'] ?? null;

        error_log("Params: username=$username, accountId=$accountId");

        if (empty($username)) {
            echo json_encode(['error' => 'Username required']);
            exit;
        }

        if (!$accountId) {
            echo json_encode(['error' => 'Please select an account first']);
            exit;
        }

        try {
            $account = \App\Models\Account::find($accountId);
            if (!$account) {
                throw new \Exception("Account not found");
            }

            $xProvider = new \App\Providers\XProvider();
            $xProvider->authenticate($account);

            error_log("Authenticating and fetching tweet...");

            $tweet = $xProvider->getUserLastTweet($username);

            error_log("Tweet Result: " . print_r($tweet, true));

            if ($tweet) {
                $json = json_encode(['tweet' => $tweet]);
                if ($json === false) {
                    error_log("JSON Encode Error: " . json_last_error_msg());
                    echo json_encode(['error' => 'JSON Encode Error: ' . json_last_error_msg()]);
                } else {
                    echo $json;
                }
            } else {
                echo json_encode(['error' => 'No tweets found for this user']);
            }
        } catch (\Throwable $e) {
            error_log("Exception/Error: " . $e->getMessage());
            error_log($e->getTraceAsString());
            echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
        }
    }
    public function verifyUser()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        header('Content-Type: application/json');

        $username = $_POST['username'] ?? '';
        $accountId = $_POST['account_id'] ?? null;

        if (empty($username)) {
            echo json_encode(['error' => 'Username required']);
            exit;
        }

        if (!$accountId) {
            echo json_encode(['error' => 'Please select an account first']);
            exit;
        }

        try {
            $account = \App\Models\Account::find($accountId);
            if (!$account) {
                throw new \Exception("Account not found");
            }

            $xProvider = new \App\Providers\XProvider();
            $xProvider->authenticate($account);

            // Use getUserId which caches and returns the ID
            $userId = $xProvider->getUserId($username, $account['access_token']);

            if ($userId) {
                echo json_encode(['user_id' => $userId, 'message' => "Verified: ID $userId"]);
            } else {
                echo json_encode(['error' => 'Could not resolve User User ID (Check username or Rate Limit)']);
            }
        } catch (\Throwable $e) {
            echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
        }
    }
}
