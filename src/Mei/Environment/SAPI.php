<?php

declare(strict_types=1);

namespace Mei\Environment;

use Mei\Cache\IKeyStore;
use Mei\Cache\NonPersistent;
use Mei\PDO\PDOTracyBarPanel;
use Mei\PDO\PDOWrapper;
use Mei\Utilities\Encryption;
use Mei\Utilities\Time;
use PDO;
use Psr\Container\ContainerInterface as Container;
use RuntimeException;
use Slim\HttpCache\CacheProvider;
use Throwable;
use Tracy\Debugger;

use function DI\autowire;
use function DI\get;

/**
 * Class SAPI
 *
 * @package Mei\Environment
 */
final class SAPI
{
    public static function definitions(): array
    {
        return [
            // utilites
            Encryption::class => autowire()->constructorParameter('config', get('config')),
            Time::class => autowire(),
            // slim
            CacheProvider::class => autowire(),
            // runtime
            PDO::class => function (Container $di) {
                $config = $di->get('config');

                $dsn = "mysql:dbname={$config['db.database']};charset=utf8;";
                if ($config['db.socket']) {
                    $dsn .= "unix_socket={$config['db.socket']};";
                } elseif ($config['db.hostname'] && $config['db.port']) {
                    $dsn .= "host={$config['db.hostname']};port={$config['db.port']};";
                } else {
                    throw new RuntimeException('Either db.socket or both db.hostname and db.port must be configured');
                }

                $w = new PDOWrapper(
                    $dsn,
                    $config['db.username'],
                    $config['db.password'],
                    [
                        PDO::MYSQL_ATTR_INIT_COMMAND => "set time_zone = '+00:00';",
                        PDO::ATTR_EMULATE_PREPARES => false, // emulated prepares ignore param hinting when binding
                    ]
                );

                $bar = new PDOTracyBarPanel($w);
                Debugger::getBar()->addPanel($bar);
                Debugger::getBlueScreen()->addPanel(
                    function (?Throwable $e) use ($bar) {
                        if ($e) {
                            return null;
                        }
                        return [
                            'tab' => 'PDO',
                            'panel' => $bar->getPanel()
                        ];
                    }
                );

                return $w;
            },
            IKeyStore::class => function () {
                return new NonPersistent();
            },
        ];
    }
}
