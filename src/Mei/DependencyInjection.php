<?php declare(strict_types=1);

namespace Mei;

use PDO;
use RunTracy\Helpers\Profiler\Exception\ProfilerException;
use RunTracy\Helpers\Profiler\Profiler;
use Slim\Container;

/**
 * Class DependencyInjection
 *
 * @package Mei
 */
class DependencyInjection
{
    /**
     * @param array $config
     * @param array $args
     *
     * @return Container
     * @throws ProfilerException
     */
    public static function get(array $config, array $args = []): Container
    {
        if (!$args) {
            $args = [
                'settings' => [
                    'addContentLengthHeader' => !($config['mode'] == 'development'),
                    'displayErrorDetails' => ($config['mode'] == 'development'),
                    'determineRouteBeforeAppMiddleware' => true,
                    'xdebugHelperIdeKey' => 'mei-image-server',
                ]
            ];
        }

        $di = new Container($args);

        $di['obLevel'] = ob_get_level();

        $di['config'] = $config;
        $di['instrumentor'] = function () {
            return new Instrumentation\Instrumentor();
        };
        if ($config['mode'] === 'development') {
            $di['instrumentor']->detailedMode(true);
        }

        $di = self::setUtilities($di);
        $di = self::setModels($di);

        $di['db'] = function ($di) {
            $ins = $di['instrumentor'];
            $iid = $ins->start('pdo:connect');
            $config = $di['config'];

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
            $w = new Instrumentation\PDOInstrumentationWrapper($di['instrumentor'], $o);
            $ins->end($iid);

            return $w;
        };

        $di['cache'] = function ($di) {
            $ins = $di['instrumentor'];
            $iid = $ins->start('nonpersistent:create');
            $cache = [];
            $mycache = new Cache\NonPersistent($cache, '');
            $ins->end($iid);
            return $mycache;
        };

        $di['notFoundHandler'] = function () {
            // delegate to the error handler
            throw new Exception\NotFound('Route Not Found');
        };
        $di['notAllowedHandler'] = function ($di) {
            // let's pretend it doesn't exist
            throw new Exception\NotFound('Route Not Found');
        };

        if ($config['mode'] != 'development') {
            $di['errorHandler'] = function ($di) {
                $ctrl = new Controller\ErrorCtrl($di);
                return [$ctrl, 'handleException'];
            };
            $di['phpErrorHandler'] = function ($di) {
                $ctrl = new Controller\FatalErrorCtrl($di);
                return [$ctrl, 'handleError'];
            };
        } else {
            unset($di['errorHandler']);
            unset($di['phpErrorHandler']);
        }

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

        $di['utility.images'] = function ($di) {
            return new Utilities\ImageUtilities($di);
        };

        $di['utility.encryption'] = function ($di) {
            return new Utilities\Encryption($di);
        };

        $di['utility.time'] = function () {
            return new Utilities\Time();
        };

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

        $di['model.files_map'] = function ($di) {
            return new Model\FilesMap($di, function ($c) {
                return new Entity\FilesMap($c);
            });
        };

        Profiler::finish('setModels');

        return $di;
    }
    /** @formatter:on */
}
