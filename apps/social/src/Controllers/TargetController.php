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
        $this->render('targets/create', [
            'title' => 'Add Target User',
            'active_page' => 'targets'
        ]);
    }

    public function store()
    {
        $data = [
            'platform' => 'x',
            'target_username' => $_POST['target_username'],
            'ai_tone' => $_POST['ai_tone']
        ];

        Target::create($data);
        header('Location: /targets');
    }
}
