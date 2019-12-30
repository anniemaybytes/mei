<?php /** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

define('BASE_ROOT', __DIR__);
define('ERROR_REPORTING', E_ALL & ~(E_STRICT | E_NOTICE | E_WARNING | E_DEPRECATED));
require_once BASE_ROOT . '/vendor/autoload.php'; // set up autoloading

use Mei\Dispatcher;
use RunTracy\Helpers\Profiler\Profiler;
use RunTracy\Middlewares\TracyMiddleware;
use Tracy\Debugger;

date_default_timezone_set('UTC');
error_reporting(ERROR_REPORTING);

Profiler::enable();
Profiler::start('App');
Profiler::start('initApp');
$app = Dispatcher::app();
Profiler::finish('initApp');

$di = $app->getContainer();

Debugger::$maxDepth = 5;
Debugger::$maxLength = 250;
Debugger::$logSeverity = ERROR_REPORTING;
Debugger::$reservedMemorySize = 5000000; // 5 megabytes because we increase depth for bluescreen
Debugger::enable(
    $di['config']['mode'] == 'development' ? Debugger::DEVELOPMENT : Debugger::PRODUCTION,
    BASE_ROOT . '/logs'
);
if ($di['config']['mode'] == 'production') { // tracy resets error_reporting to E_ALL when it's enabled, silence it on production please
    error_reporting(ERROR_REPORTING);
}

Debugger::getBlueScreen()->maxDepth = 7;
Debugger::getBlueScreen()->maxLength = 520;
array_push(
    Debugger::getBlueScreen()->keysToHide,
    'CSRF',
    'SERVER_ADDR',
    'REMOTE_ADDR',
    '_tracy',
    'PHP_AUTH_PW'
);

Debugger::getBlueScreen()->addPanel(
    function ($e) use ($di) {
        if ($e) {
            return null;
        }
        return [
            'tab' => 'Cache hits',
            'panel' => Debugger::dump($di['cache']->getCacheHits(), true),
        ];
    }
);
Debugger::getBlueScreen()->addPanel(
    function ($e) use ($di) {
        if ($e) {
            return null;
        }
        return [
            'tab' => 'Instrumentor',
            'panel' => Debugger::dump($di['instrumentor']->getLog(), true),
        ];
    }
);

// add middleware
// note that the order is important; middleware gets executed as an onion, so
// the first middleware that gets added gets executed last as the request comes
// in and first as the response comes out.
Profiler::start('initMiddlewares');
if ($di['config']['mode'] == 'development') {
    $app->add(new TracyMiddleware($app));
}
Profiler::finish('initMiddlewares');

$app->run();
Profiler::finish('App');

Debugger::barDump($di['cache']->getCacheHits(), 'Cache hits');
Debugger::barDump($di['instrumentor']->getLog(), 'Instrumentor');
