<?php
namespace Mei;

use PDO;
use Slim\Container;

class DependencyInjection
{
    public static function get($config, $args = array())
    {
        if (!$args) {
            $args = array(
                'settings' => array('displayErrorDetails' => ($config['mode'] == 'development'),));
        }

        $di = new Container($args);

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
                array(
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "set time_zone = '+00:00';",
                    PDO::ATTR_EMULATE_PREPARES => false, // emulated prepares ignore param hinting when binding
                )
            );
            $w = new Instrumentation\PDOInstrumentationWrapper($di['instrumentor'], $o);
            $ins->end($iid);

            return $w;
        };

        $di['cache'] = function ($di) {
            $ins = $di['instrumentor'];
            $iid = $ins->start('nonpersistent:create');
            $cache = array();
            $mycache = new Cache\NonPersistent($cache,'');
            $ins->end($iid);
            return $mycache;
        };

        $di['notFoundHandler'] = function () {
            // delegate to the error handler
            throw new Exception\NotFound('Route Not Found');
        };

        if ($config['mode'] != 'development') {
            $di['errorHandler'] = function ($di) {
                $ctrl = new Controller\ErrorCtrl($di);
                return array($ctrl, 'handleException');
            };
        }

        return $di;
    }

    private static function setUtilities($di)
    {
        $di['utility.images'] = function ($di) {
            return new Utilities\ImageUtilities($di);
        };

        $di['utility.encryption'] = function ($di) {
            return new Utilities\Encryption($di);
        };

        $di['utility.time'] = function () {
            return new Utilities\Time();
        };

        return $di;
    }

    private static function setModels($di)
    {
        $di['model.files_map'] = function($di) {
            return new Model\FilesMap($di, function($c) {
                return new Entity\FilesMap($c);
            });
        };

        return $di;
    }
}
