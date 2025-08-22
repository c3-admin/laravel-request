<?php

return [
    'default_guard' => null,

    'super_admin_role' => 'Super Admin',

    
    'pin' => [
        'digits' => 6,
        'column' => 'pin',
    ],

    'password' => [
        'column' => 'password',
        'field' => 'current_password',
        'guard' => null, // Use auth.default guard by default
    ],
];
