<?php

return [
    'enabled' => env('DEMO_MODE', false),

    'accounts' => [
        'admin' => [
            'name' => 'Administrator Demo',
            'role' => 'admin',
            'password' => env('DEMO_ADMIN_PASSWORD', 'admin123'),
            'division' => 'K3LH',
        ],
        'pemantau' => [
            'name' => 'Pemantau Demo',
            'role' => 'pemantau',
            'password' => env('DEMO_PEMANTAU_PASSWORD', 'pemantau123'),
            'division' => 'TI',
        ],
        'inspektur' => [
            'name' => 'Inspektur Demo',
            'role' => 'inspektur',
            'password' => env('DEMO_INSPEKTUR_PASSWORD', 'inspektur123'),
            'division' => 'PIP',
        ],
    ],
];
