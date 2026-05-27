<?php

return [
    'currency'      => 'EUR',
    'image_credits' => 5,

    // Free message allowance for guest (no-account) users — smaller than the
    // registered default (20) to limit cookie-reset farming.
    'guest_credits' => (int) env('GUEST_CREDITS', 10),

    // Max new guest accounts per IP per hour (anti-farming backstop).
    'guest_register_per_hour' => (int) env('GUEST_REGISTER_PER_HOUR', 5),

    // Inactive-account pruning (days). Guests: no activity for N days.
    // Free registered accounts: never chatted AND older than N days.
    'guest_prune_days' => (int) env('GUEST_PRUNE_DAYS', 14),
    'free_prune_days'  => (int) env('FREE_PRUNE_DAYS', 30),

    'plans' => [
        [
            'id'              => 'starter',
            'emoji'           => '🌱',
            'credits'         => 50,
            'unlimited_days'  => null,
            'amount'          => 399,       // in cents — €3.99
            'billing'         => 'one_time',
            'highlight'       => false,
            'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
        ],
        [
            'id'              => 'popular',
            'emoji'           => '🔥',
            'credits'         => 200,
            'unlimited_days'  => null,
            'amount'          => 999,       // in cents — €9.99
            'billing'         => 'one_time',
            'highlight'       => true,
            'stripe_price_id' => env('STRIPE_PRICE_POPULAR'),
        ],
        [
            'id'              => 'unlimited',
            'emoji'           => '♾️',
            'credits'         => null,      // null = unlimited access (not credit-based)
            'unlimited_days'  => 30,        // grants 30 days of unlimited sending
            'amount'          => 1499,      // in cents — €14.99
            'billing'         => 'one_time', // one-time purchase, NOT a recurring subscription
            'highlight'       => false,
            'stripe_price_id' => env('STRIPE_PRICE_UNLIMITED'),
        ],
    ],
];
