<?php

return [
    'name' => env('COMPANY_NAME', 'Selvin Co'),
    'contact' => [
        'name' => env('COMPANY_CONTACT_NAME', 'Selvin Ortiz'),
        'email' => env('COMPANY_EMAIL', 'selvin@selvin.co'),
        'phone' => env('COMPANY_PHONE', '(612) 424-0013'),
    ],
    'address' => [
        'street' => env('COMPANY_STREET', '15782 Hershey Ct'),
        'city' => env('COMPANY_CITY', 'Apple Valley'),
        'state' => env('COMPANY_STATE', 'MN'),
        'zip' => env('COMPANY_ZIP', '55124'),
    ],
];
