<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Account;
use App\Models\User;
use App\Services\XService;

class AccountController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if (!User::isSubscriptionActive($user)) {
            header('Location: /pricing');
            exit;
        }

        $accounts = Account::allActiveForUser($user['id']);

        $this->render('accounts/index', [
            'title' => 'X Accounts',
            'active_page' => 'accounts',
            'accounts' => $accounts
        ]);
    }

    public function connect()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $xService = new XService();
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $forceLogin = isset($_GET['force']) && $_GET['force'] === '1';
        $authUrl = $xService->getAuthUrl($state, $forceLogin);
        header('Location: ' . $authUrl);
        exit;
    }

    public function callback()
    {
        if (!isset($_GET['code']) || !isset($_GET['state'])) {
            die('Invalid request');
        }

        if ($_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
            die('State mismatch');
        }

        $xService = new XService();
        $tokens = $xService->getTokens($_GET['code']);

        if (isset($tokens['error'])) {
            die('Error fetching tokens: ' . json_encode($tokens));
        }

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Get user info from X
        $userInfo = $xService->getUser($tokens['access_token']);

        if (isset($userInfo['errors'])) {
            die('Error fetching user: ' . json_encode($userInfo));
        }

        $xUser = $userInfo['data'];

        $data = [
            'user_id' => $_SESSION['user_id'],
            'platform' => 'x',
            'username' => $xUser['username'],
            'avatar' => $xUser['profile_image_url'] ?? null,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at' => date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 7200))
        ];

        Account::updateOrCreate($data);

        header('Location: /accounts');
        exit;
    }
}
