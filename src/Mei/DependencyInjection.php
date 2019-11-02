<?php

namespace Mei;

use PDO;
use RunTracy\Helpers\Profiler\Profiler;
use Slim\Container;

class DependencyInjection
{
    public static function get($config, $args = [])
    {
        if (!$args) {
            $args = [
                'settings' => [
                    'addContentLengthHeader' => !($config['mode'] == 'development'),
                    'displayErrorDetails' => ($config['mode'] == 'development'),
                    'determineRouteBeforeAppMiddleware' => true,
                    'tracy' => [
                        'showPhpInfoPanel' => 0,
                        'showSlimRouterPanel' => 1,
                        'showSlimEnvironmentPanel' => 1,
                        'showSlimRequestPanel' => 1,
                        'showSlimResponsePanel' => 1,
                        'showSlimContainer' => 1,
                        'showTwigPanel' => 0,
                        'showProfilerPanel' => 1,
                        'showVendorVersionsPanel' => 0,
                        'showIncludedFiles' => 1,
                        'showConsolePanel' => 0,
                        'showXDebugHelper' => 0,
                        'configs' => [
                            'ProfilerPanel' => [
                                'show' => [
                                    'memoryUsageChart' => 1,
                                    'shortProfiles' => false,
                                    'timeLines' => true
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $di = new Container($args);

        $di['obLevel'] = ob_get_level();

        $di['config'] = $config;
        $di['instrumentor'] = function () {
            return new Instrumentation\Instrumentor();
        };

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

            $o = new PDO($dsn, $config['db.username'],
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

        unset($di['phpErrorHandler']); // php 7.0+ only - this will disable default Slim error handler and allow Tracy to catch PHP errors
        if ($config['mode'] != 'development') {
            $di['errorHandler'] = function ($di) {
                $ctrl = new Controller\ErrorCtrl($di);
                return [$ctrl, 'handleException'];
            };
        } else unset($di['errorHandler']);

        return $di;
    }

    private static function setUtilities($di)
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
    }

    private static function setModels($di)
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
}
