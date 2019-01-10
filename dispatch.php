<?php
define('BASE_ROOT', __DIR__);
require_once BASE_ROOT . '/vendor/autoload.php'; // set up autoloading

date_default_timezone_set('UTC');
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING));

\RunTracy\Helpers\Profiler\Profiler::enable();
\RunTracy\Helpers\Profiler\Profiler::start('App');
\RunTracy\Helpers\Profiler\Profiler::start('initApp');
$app = \Mei\Dispatcher::app();
\RunTracy\Helpers\Profiler\Profiler::finish('initApp');

$di = $app->getContainer();

// this will take care of internal proxy for file_get_contents however it is recommended to use \Tentacles\Utilities\Curl
if($di['config']['proxy']) {
    stream_context_set_default([
        'http' => [
            'proxy' => $di['config']['proxy']
        ]
    ]);
}

\Tracy\Debugger::enable($di['config']['mode'] == 'development' ? \Tracy\Debugger::DEVELOPMENT : \Tracy\Debugger::PRODUCTION, BASE_ROOT . '/logs');
\Tracy\Debugger::$maxDepth = 5;
\Tracy\Debugger::$maxLength = 250;
\Tracy\Debugger::$logSeverity = E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING);

// add middleware
// note that the order is important; middleware gets executed as an onion, so
// the first middleware that gets added gets executed last as the request comes
// in and first as the response comes out.
\RunTracy\Helpers\Profiler\Profiler::start('initMiddlewares');
if ($di['config']['mode'] == 'development') {
    $app->add(new \RunTracy\Middlewares\TracyMiddleware($app));
}
\RunTracy\Helpers\Profiler\Profiler::finish('initMiddlewares');

$app->run();
\RunTracy\Helpers\Profiler\Profiler::finish('App');

\Tracy\Debugger::barDump($di['cache']->getCacheHits(), 'Cache hits');
\Tracy\Debugger::barDump($di['instrumentor']->getLog(), 'Instrumentor');
