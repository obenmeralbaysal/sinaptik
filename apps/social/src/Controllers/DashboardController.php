<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Post;
use App\Models\Reply;

class DashboardController extends Controller
{
    public function index()
    {
        $user = User::findById($_SESSION['user_id']);
        if (!$user || !User::isSubscriptionActive($user)) {
            header('Location: /pricing');
            exit;
        }



        $userId = $user['id'];

        // Fetch Stats
        $activeAccounts = Account::countActiveForUser($userId);
        $dailyPosts = Post::countTodayForUser($userId);
        $dailyReplies = Reply::countTodayForUser($userId);

        // Fetch Recent Activity
        $recentPosts = Post::getRecentForUser($userId, 5);
        $recentReplies = Reply::getRecentForUser($userId, 5);

        // Merge and sort by date descending
        $recentActivity = array_merge($recentPosts, $recentReplies);
        usort($recentActivity, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Limit to top 10
        $recentActivity = array_slice($recentActivity, 0, 10);

        $this->render('dashboard', [
            'title' => 'Dashboard',
            'active_page' => 'dashboard',
            'stats' => [
                'active_accounts' => $activeAccounts,
                'daily_posts' => $dailyPosts,
                'daily_replies' => $dailyReplies
            ],
            'recent_activity' => $recentActivity
        ]);
    }
}
