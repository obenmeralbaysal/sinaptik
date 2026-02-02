<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = User::findById($_SESSION['user_id']);
        if (!$user || !User::isSubscriptionActive($user)) {
            header('Location: /pricing');
            exit;
        }

        $this->render('dashboard', [
            'title' => 'Dashboard',
            'active_page' => 'dashboard',
            'stats' => [
                'active_accounts' => 0,
                'daily_posts' => 0,
                'pending_replies' => 0
            ]
        ]);
    }
}
