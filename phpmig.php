<?php

declare(strict_types=1);

const BASE_ROOT = __DIR__;
require_once BASE_ROOT . '/vendor/autoload.php'; // set up autoloading

use Mei\ConfigLoader;
use Phpmig\Adapter;
use Pimple\Container;

return new Container(
    [
        PDO::class => static function (Container $di) {
            $config = $di['config'];

            $dsn = "mysql:dbname={$config['db.database']};charset=utf8;";
            if (isset($config['db.socket'])) {
                $dsn .= "unix_socket={$config['db.socket']};";
            } else {
                $dsn .= "host={$config['db.hostname']};port={$config['db.port']};";
            }

            return new PDO(
                $dsn,
                $config['db.username'],
                $config['db.password'],
                [
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "set time_zone = '+00:00';",
                    PDO::ATTR_EMULATE_PREPARES => false, // emulated prepares ignore param hinting when binding
                ]
            );
        },
        'phpmig.adapter' => static function (Container $di) {
            return new Adapter\PDO\Sql($di[PDO::class], 'migrations');
        },
        'config' => ConfigLoader::load(),
        'phpmig.migrations_path' => BASE_ROOT . '/migrations',
    ]
);
