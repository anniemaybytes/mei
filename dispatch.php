<?php

define('BASE_ROOT', __DIR__);
require_once BASE_ROOT . '/vendor/autoload.php'; // set up autoloading

use RunTracy\Helpers\Profiler\Profiler;
use RunTracy\Middlewares\TracyMiddleware;
use Tracy\Debugger;

date_default_timezone_set('UTC');
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING));

Profiler::enable();
Profiler::start('App');
Profiler::start('initApp');
$app = \Mei\Dispatcher::app();
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
Debugger::$maxDepth = 5;
Debugger::$maxLength = 250;
Debugger::$logSeverity = E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING);

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
