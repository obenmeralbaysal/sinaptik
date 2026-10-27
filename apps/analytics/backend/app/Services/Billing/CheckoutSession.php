<?php

namespace App\Services\Billing;

class CheckoutSession
{
    public function __construct(
        public string $sessionId,
        public string $redirectUrl,
    ) {}
}
