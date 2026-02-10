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

        $this->render('targets/create', [
            'title' => 'Add Target User',
            'active_page' => 'targets',
            'accounts' => $accounts
        ]);
    }

    public function store()
    {
        $data = [
            'platform' => 'x',
            'target_username' => $_POST['target_username'],
            'ai_tone' => $_POST['ai_tone'],
            'custom_prompt' => $_POST['custom_prompt'] ?? null,
            'account_id' => !empty($_POST['account_id']) ? (int) $_POST['account_id'] : null
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

        $this->render('targets/edit', [
            'title' => 'Edit Target User',
            'active_page' => 'targets',
            'target' => $target,
            'accounts' => $accounts
        ]);
    }

    public function update($id)
    {
        $data = [
            'target_username' => $_POST['target_username'],
            'ai_tone' => $_POST['ai_tone'],
            'custom_prompt' => $_POST['custom_prompt'] ?? null,
            'account_id' => !empty($_POST['account_id']) ? (int) $_POST['account_id'] : null
        ];

        Target::update($id, $data);
        header('Location: /targets');
    }

    public function delete($id)
    {
        Target::delete($id);
        header('Location: /targets');
    }
}
