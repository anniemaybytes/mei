<?php

declare(strict_types=1);

const BASE_ROOT = __DIR__;
require_once BASE_ROOT . '/vendor/autoload.php';

use Mei\Config;

$config = new Config\Config();

return [
    'paths' => [
        'migrations' => BASE_ROOT . '/migrations',
        'seeds' => BASE_ROOT . '/seeds',
    ],
    'environments' => [
        'default_environment' => 'development',
        'default_migration_table' => 'migrations',
        $config['mode'] => [
            'adapter' => 'mysql',
            'charset' => 'utf8',
            'host' => $config['db.hostname'],
            'port' => $config['db.port'],
            'unix_socket' => $config['db.socket'],
            'name' => $config['db.database'],
            'user' => $config['db.username'],
            'pass' => $config['db.password']
        ]
    ]
];
