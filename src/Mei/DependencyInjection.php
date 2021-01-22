<?php

declare(strict_types=1);

namespace Mei;

use DI;
use Exception;
use Mei\Cache\IKeyStore;
use Mei\Entity\ICacheable;
use Mei\Model\FilesMap;
use Mei\PDO\PDOTracyBarPanel;
use Mei\PDO\PDOWrapper;
use Mei\Utilities\Encryption;
use Mei\Utilities\Time;
use PDO;
use Psr\Container\ContainerInterface as Container;
use Throwable;
use Tracy\Debugger;

/**
 * Class DependencyInjection
 *
 * @package Mei
 */
final class DependencyInjection
{
    /**
     * @param array $config
     *
     * @return Container
     * @throws Exception
     */
    public static function setup(array $config): Container
    {
        $builder = new DI\ContainerBuilder();
        $builder->useAnnotations(true);
        if ($config['mode'] === 'production') {
            $builder->enableCompilation(BASE_ROOT);
        }
        $builder->addDefinitions(
            [
                // utilites
                Encryption::class => DI\autowire()->constructorParameter('config', DI\get('config')),
                Time::class => DI\autowire(),
                // runtime
                IKeyStore::class => function () {
                    return new Cache\NonPersistent('');
                },
                PDO::class => function (Container $di) {
                    $config = $di->get('config');

                    $dsn = 'mysql:dbname=' . ($config['db.database'] ?? 'mei') . ';charset=utf8;';

                    if (isset($config['db.socket'])) {
                        $dsn .= "unix_socket={$config['db.socket']};";
                    } else {
                        $dsn .= 'host=' . ($config['db.hostname'] ?? 'localhost') . ';port=' . ($config['db.port'] ?? 3306) . ';';
                    }

                    $w = new PDOWrapper(
                        $dsn,
                        $config['db.username'] ?? 'mei',
                        $config['db.password'] ?? '',
                        [
                            PDO::ATTR_PERSISTENT => false,
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
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
                // models
                FilesMap::class => DI\autowire()
                    ->constructorParameter(
                        'entityBuilder',
                        DI\value(
                            function (ICacheable $c) {
                                return new Entity\FilesMap($c);
                            }
                        )
                    ),
            ]
        );
        $di = $builder->build();

        // dynamic definitions
        $di->set('config', $config);
        $di->set('obLevel', ob_get_level());

        return $di;
    }
}
