<?php

declare(strict_types=1);

namespace Mei\Environment;

use Mei\Cache\IKeyStore;
use Mei\Cache\NonPersistent;
use Mei\PDO\PDOLogger;
use Mei\PDO\PDOTracyBarPanel;
use Mei\PDO\PDOWrapper;
use Mei\Utilities\Encryption;
use Mei\Utilities\Time;
use PDO;
use Psr\Container\ContainerInterface as Container;
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
                return new PDOWrapper(
                    $di,
                    [
                        PDO::MYSQL_ATTR_INIT_COMMAND => "set time_zone = '+00:00';",
                        PDO::ATTR_EMULATE_PREPARES => false, // emulated prepares ignore param hinting when binding
                    ]
                );
            },
            PDOLogger::class => function () {
                $logger = new PDOLogger(PDO::class);

                $bar = new PDOTracyBarPanel($logger);
                Debugger::getBar()->addPanel($bar);
                Debugger::getBlueScreen()->addPanel(
                    function (?Throwable $e) use ($bar, $logger) {
                        if ($e) {
                            return null;
                        }
                        return [
                            'tab' => $logger->getProvider() . ' queries',
                            'panel' => $bar->getPanel()
                        ];
                    }
                );

                return $logger;
            },
            IKeyStore::class => function () {
                return new NonPersistent();
            },
        ];
    }
}
