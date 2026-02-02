<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Core\Database;

class AdminController extends Controller
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById($_SESSION['user_id']);
        if (!$user || !User::isAdmin($user)) {
            header('Location: /dashboard');
            exit;
        }
    }

    public function index()
    {
        $db = Database::getInstance();
        
        $stats = [
            'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_plans' => $db->query("SELECT COUNT(*) FROM plans")->fetchColumn(),
            'total_accounts' => $db->query("SELECT COUNT(*) FROM accounts")->fetchColumn(),
        ];

        $this->render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'active_page' => 'admin',
            'stats' => $stats
        ]);
    }

    public function plans()
    {
        $plans = Plan::allActive();
        $this->render('admin/plans', [
            'title' => 'Manage Plans',
            'active_page' => 'admin',
            'plans' => $plans
        ]);
    }

    public function users()
    {
        $db = Database::getInstance();
        $users = $db->query("
            SELECT u.*, 
            (SELECT COUNT(*) FROM accounts WHERE user_id = u.id) as accounts_count 
            FROM users u 
            ORDER BY u.created_at DESC
        ")->fetchAll();

        $this->render('admin/users', [
            'title' => 'Manage Users',
            'active_page' => 'admin',
            'users' => $users
        ]);
    }

    public function storePlan()
    {
        $features = explode("\n", str_replace("\r", "", $_POST['features']));
        $features = array_filter(array_map('trim', $features));

        $data = [
            'name' => $_POST['name'],
            'price_monthly' => $_POST['price'],
            'x_account_limit' => $_POST['x_account_limit'] ?? 1,
            'daily_post_limit' => $_POST['daily_post_limit'] ?? 5,
            'daily_reply_limit' => $_POST['daily_reply_limit'] ?? 20,
            'features' => json_encode(array_values($features))
        ];

        Plan::create($data);
        header('Location: /admin/plans');
    }
}
