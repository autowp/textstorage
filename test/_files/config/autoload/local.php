<?php

namespace Autowp\TextStorage;

use Zend_Application_Resource_Db;
use Zend_Db_Adapter_Abstract;

return [
    'textstorage' => [
        'textTableName'     => 'textstorage_text',
        'revisionTableName' => 'textstorage_revision',
        'dbAdapter'         => Zend_Db_Adapter_Abstract::class
    ],
    'db' => [
        'adapter' => 'PDO_MYSQL',
        'params' => [
            'host'     => 'localhost',
            'username' => 'autowp_test',
            'password' => 'test',
            'dbname'   => 'autowp_test',
        ],
        'isDefaultTableAdapter' => true,
        'defaultMetadataCache' => null,
    ],
    'service_manager' => [
        'factories' => [
            Zend_Db_Adapter_Abstract::class => function($serviceManager) {
                $config = $serviceManager->get('Config');
                $resource = new Zend_Application_Resource_Db($config['db']);
                return $resource->init();
            },
        ]
    ]
];
