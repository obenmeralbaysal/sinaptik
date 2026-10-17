<?php

return [
    /*
    | Phase 3 will call X. These figures are the prepaid credit prices
    | (USD cents) used by the ledger primitive today.
    */
    'prices_usd_cents' => [
        'post' => 2, // $0.015 rounded to 2 cents for integer ledger; exact milli-cents below
        'post_with_link' => 20,
        'read' => 1,
    ],
    'prices_milli_cents' => [
        'post' => 1500,
        'post_with_link' => 20000,
        'read' => 500,
    ],
    'credit_milli_cents' => 1000,
];
