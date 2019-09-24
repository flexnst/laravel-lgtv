<?php

return [
    'devices' => [
        'tv1' => [
            'ip' => env('LGTV_TV1_IP'),
            'mac' => env('LGTV_TV1_MAC'),
            'key_path' => storage_path('lgtv_tv1.key')
        ]
    ],
    'default' => 'tv1'
];
