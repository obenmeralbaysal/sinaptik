<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PaymentService;
use App\Core\Database;

class PaymentController extends Controller
{
    public function index()
    {
        $db = Database::getInstance();
        $plans = $db->query("SELECT * FROM plans WHERE is_active = 1")->fetchAll();

        $this->render('payments/index', [
            'title' => 'Subscription Plans',
            'active_page' => 'pricing',
            'plans' => $plans
        ]);
    }

    public function checkout()
    {
        $planId = $_GET['plan_id'] ?? null;
        if (!$planId) {
            header('Location: /pricing');
            exit;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM plans WHERE id = :id");
        $stmt->execute(['id' => $planId]);
        $plan = $stmt->fetch();

        if (!$plan) {
            header('Location: /pricing');
            exit;
        }

        $paytrActive = PaymentService::isPayTRActive();

        $this->render('payments/checkout', [
            'title' => 'Checkout',
            'active_page' => 'pricing',
            'plan' => $plan,
            'paytr_active' => $paytrActive
        ]);
    }
}
