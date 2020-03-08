<?php

declare(strict_types=1);

return [
    'modules'                 => [
        'Laminas\\Db',
        'Laminas\\Router',
        'Autowp\\TextStorage',
    ],
    'module_listener_options' => [
        'module_paths'      => [
            './vendor',
        ],
        'config_glob_paths' => [
            'test/_files/config/autoload/local.php',
        ],
    ],
];
