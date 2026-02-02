<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

class PaymentService
{
    public static function isPayTRActive(): bool
    {
        return Config::get('PAYTR_STATUS', 'false') === 'true';
    }

    public static function getPayTRToken(array $data): string
    {
        $merchant_id = Config::get('PAYTR_MERCHANT_ID');
        $merchant_key = Config::get('PAYTR_MERCHANT_KEY');
        $merchant_salt = Config::get('PAYTR_MERCHANT_SALT');

        $email = $data['email'];
        $payment_amount = $data['amount'] * 100; // In cents
        $merchant_oid = $data['order_id'];
        $user_name = $data['name'];
        $user_address = "Sinapp Social User";
        $user_phone = $data['phone'] ?? "0000000000";
        $merchant_ok_url = Config::get('APP_URL') . "/payment/success";
        $merchant_fail_url = Config::get('APP_URL') . "/payment/fail";
        $user_basket = base64_encode(json_encode([
            [$data['plan_name'], $data['amount'], 1]
        ]));
        $timeout_limit = "30";
        $debug_on = 1;
        $test_mode = 1;
        $no_installment = 0;
        $max_installment = 0;
        $currency = "TL";

        $hash_str = $merchant_id . $data['ip'] . $merchant_oid . $email . $payment_amount . $user_basket . $no_installment . $max_installment . $currency . $test_mode;
        $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

        return $paytr_token;
    }

    public static function createPendingPayment(int $userId, int $planId, float $amount, string $method): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO payments (user_id, plan_id, amount, payment_method, status) 
            VALUES (:user_id, :plan_id, :amount, :method, 'pending')
        ");
        $stmt->execute([
            'user_id' => $userId,
            'plan_id' => $planId,
            'amount' => $amount,
            'method' => $method
        ]);
        return (int)$db->lastInsertId();
    }
}
