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
            'ai_model' => !empty($_POST['ai_model']) ? $_POST['ai_model'] : null
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
            'ai_model' => !empty($_POST['ai_model']) ? $_POST['ai_model'] : null
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
        header('Content-Type: application/json');

        $username = $_POST['username'] ?? '';
        $accountId = $_POST['account_id'] ?? null;

        if (empty($username)) {
            echo json_encode(['error' => 'Username required']);
            exit;
        }

        // We need an authenticated account to fetch tweets
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

            $tweet = $xProvider->getUserLastTweet($username);

            if ($tweet) {
                echo json_encode(['tweet' => $tweet]);
            } else {
                echo json_encode(['error' => 'No tweets found for this user']);
            }
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
