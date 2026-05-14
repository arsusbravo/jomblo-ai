<?php

return [
    'currency' => 'EUR',

    'plans' => [
        [
            'id'        => 'starter',
            'emoji'     => '🌱',
            'credits'   => 50,
            'amount'    => 399,       // in cents — €3.99
            'billing'   => 'one_time',
            'highlight' => false,
        ],
        [
            'id'        => 'popular',
            'emoji'     => '🔥',
            'credits'   => 200,
            'amount'    => 999,       // in cents — €9.99
            'billing'   => 'one_time',
            'highlight' => true,
        ],
        [
            'id'        => 'unlimited',
            'emoji'     => '♾️',
            'credits'   => null,      // null = unlimited
            'amount'    => 1499,      // in cents — €14.99
            'billing'   => 'monthly',
            'highlight' => false,
        ],
    ],
];
