<?php declare(strict_types=1);

namespace Mei;

use DI\Container;
use DI\ContainerBuilder;
use PDO;
use RunTracy\Helpers\Profiler\Exception\ProfilerException;
use RunTracy\Helpers\Profiler\Profiler;

/**
 * Class DependencyInjection
 *
 * @package Mei
 */
class DependencyInjection
{
    /**
     * @param array $config
     *
     * @return Container
     * @throws ProfilerException
     */
    public static function setup(array $config): Container
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(false);
        $builder->useAnnotations(false);
        $builder->addDefinitions(
            [
                'settings' => [
                    'xdebugHelperIdeKey' => 'mei-image-server',
                ]
            ]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $di = $builder->build();

        $di->set('config', $config);
        $di->set('obLevel', ob_get_level());
        $di->set(
            'instrumentor',
            function () {
                return new Instrumentation\Instrumentor();
            }
        );
        if ($config['mode'] === 'development') {
            $di->get('instrumentor')->detailedMode(true);
        }

        $di = self::setUtilities($di);
        $di = self::setModels($di);

        $di->set(
            'db',
            function ($di) {
                $ins = $di->get('instrumentor');
                $iid = $ins->start('pdo:connect');
                $config = $di->get('config');

                $dsn = "mysql:dbname={$config['db.database']};charset=UTF8;";

                if (isset($config['db.socket'])) {
                    $dsn .= "unix_socket={$config['db.socket']};";
                } else {
                    $dsn .= "host={$config['db.hostname']};port={$config['db.port']};";
                }

                $o = new PDO(
                    $dsn, $config['db.username'],
                    $config['db.password'],
                    [
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "set time_zone = '+00:00';",
                        PDO::ATTR_EMULATE_PREPARES => false, // emulated prepares ignore param hinting when binding
                    ]
                );
                $w = new Instrumentation\PDOInstrumentationWrapper($di->get('instrumentor'), $o);
                $ins->end($iid);

                return $w;
            }
        );

        $di->set(
            'cache',
            function ($di) {
                $ins = $di->get('instrumentor');
                $iid = $ins->start('nonpersistent:create');
                $cache = [];
                $mycache = new Cache\NonPersistent($cache, '');
                $ins->end($iid);
                return $mycache;
            }
        );

        return $di;
    }

    /**
     * @param Container $di
     *
     * @return Container
     * @throws ProfilerException
     */
    private static function setUtilities(Container $di): Container /** @formatter:off */
    {
        Profiler::start('setUtilities');

        $di->set('utility.images', function ($di) {
            return new Utilities\ImageUtilities($di);
        });

        $di->set('utility.encryption', function ($di) {
            return new Utilities\Encryption($di);
        });

        $di->set('utility.time', function () {
            return new Utilities\Time();
        });

        Profiler::finish('setUtilities');

        return $di;
    } /** @formatter:on */

    /**
     * @param Container $di
     *
     * @return Container
     * @throws ProfilerException
     */
    private static function setModels(Container $di): Container /** @formatter:off */
    {
        Profiler::start('setModels');

        $di->set('model.files_map', function ($di) {
            return new Model\FilesMap($di, function ($c) {
                return new Entity\FilesMap($c);
            });
        });

        Profiler::finish('setModels');

        return $di;
    }
    /** @formatter:on */
}
