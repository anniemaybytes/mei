<?php

declare(strict_types=1);

namespace Mei\Environment;

use Mei\Cache\IKeyStore;
use Mei\Cache\NonPersistent;
use Mei\Utilities\Encryption;
use Mei\Utilities\Time;
use PDO;
use Psr\Container\ContainerInterface as Container;

use function DI\autowire;
use function DI\get;

/**
 * Class CLI
 *
 * @package Mei\Environment
 */
final class CLI
{
    public static function definitions(): array
    {
        return [
            PDO::class => function (Container $di) {
                $config = $di->get('config');
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
                        PDO::MYSQL_ATTR_INIT_COMMAND => "set time_zone = '+00:00';",
                        PDO::ATTR_EMULATE_PREPARES => false, // emulated prepares ignore param hinting when binding
                    ]
                );
            },
            IKeyStore::class => function () {
                return new NonPersistent();
            },
            Encryption::class => autowire()->constructorParameter('config', get('config')),
            Time::class => autowire(),
        ];
    }
}
