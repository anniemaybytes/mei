<?php

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

// this will take care of internal proxy for file_get_contents however it is recommended to use \Tentacles\Utilities\Curl
if($di['config']['proxy']) {
    stream_context_set_default([
        'http' => [
            'proxy' => $di['config']['proxy']
        ]
    ]);
}

Debugger::enable($di['config']['mode'] == 'development' ? Debugger::DEVELOPMENT : Debugger::PRODUCTION, BASE_ROOT . '/logs');
if ($di['config']['mode'] == 'production') { // tracy resets error_reporting to E_ALL when it's enabled, silence it on production please
    error_reporting(ERROR_REPORTING);
}
Debugger::$maxDepth = 5;
Debugger::$maxLength = 250;
Debugger::$logSeverity = ERROR_REPORTING;
Debugger::$reservedMemorySize = 5000000; // 5 megabytes because we increase depth for bluescreen
Debugger::getBlueScreen()->maxDepth = 7;
Debugger::getBlueScreen()->maxLength = 520;

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
