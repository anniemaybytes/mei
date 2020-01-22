<?php /** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace Mei;

use DI;
use Exception;
use Mei\Cache\IKeyStore;
use Mei\Instrumentation\Instrumentor;
use Mei\Model\FilesMap;
use Mei\Utilities\Encryption;
use Mei\Utilities\ImageUtilities;
use Mei\Utilities\Time;
use PDO;
use Psr\Container\ContainerInterface as Container;
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
     * @throws Exception
     */
    public static function setup(array $config): Container
    {
        $builder = new DI\ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->addDefinitions(
            [
                'settings' => [
                    'xdebugHelperIdeKey' => 'mei-image-server',
                ],
                'config' => $config,
                'obLevel' => ob_get_level(),
                Instrumentor::class => DI\autowire()
            ]
        );
        $di = $builder->build();

        if ($config['mode'] === 'development') {
            $di->get(Instrumentor::class)->detailedMode(true);
        }

        $di = self::setUtilities($di);
        $di = self::setModels($di);

        $di->set(
            IKeyStore::class,
            function (Instrumentor $ins) {
                $iid = $ins->start('nonpersistent:create');
                $cache = new Cache\NonPersistent('');
                $ins->end($iid);
                return $cache;
            }
        );

        $di->set(
            PDO::class,
            function (Container $di) {
                $ins = $di->get(Instrumentor::class);
                $config = $di->get('config');

                $iid = $ins->start('pdo:connect');

                $dsn = "mysql:dbname={$config['db.database']};charset=UTF8;";

                if (isset($config['db.socket'])) {
                    $dsn .= "unix_socket={$config['db.socket']};";
                } else {
                    $dsn .= "host={$config['db.hostname']};port={$config['db.port']};";
                }

                $w = new Instrumentation\PDOInstrumentationWrapper(
                    $ins, $dsn, $config['db.username'],
                    $config['db.password'],
                    [
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "set time_zone = '+00:00';",
                        PDO::ATTR_EMULATE_PREPARES => false, // emulated prepares ignore param hinting when binding
                    ]
                );

                $ins->end($iid);

                return $w;
            }
        );

        return $di;
    }

    /**
     * @param Container $di
     *
     * @return Container
     */
    private static function setUtilities(Container $di): Container /** @formatter:off */
    {
        Profiler::start('setUtilities');

        $di->set(ImageUtilities::class, DI\autowire());
        $di->set(Encryption::class, DI\autowire()->constructorParameter('encryptionKey', $di->get('config')['api.auth_key']));
        $di->set(Time::class, DI\autowire());

        Profiler::finish('setUtilities');

        return $di;
    } /** @formatter:on */

    /**
     * @param Container $di
     *
     * @return Container
     */
    private static function setModels(Container $di): Container /** @formatter:off */
    {
        Profiler::start('setModels');

        $di->set(FilesMap::class, function (IKeyStore $cache, PDO $db) {
            return new Model\FilesMap(function ($c) {
                return new Entity\FilesMap($c);
            }, $cache, $db);
        });

        Profiler::finish('setModels');

        return $di;
    }
    /** @formatter:on */
}
