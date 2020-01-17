<?php

declare(strict_types=1);

define('BASE_ROOT', __DIR__);
define('ERROR_REPORTING', E_ALL & ~(E_STRICT | E_NOTICE | E_WARNING | E_DEPRECATED));
require_once BASE_ROOT . '/vendor/autoload.php'; // set up autoloading

use DI\Container;
use Mei\Controller\ErrorCtrl;
use Mei\Dispatcher;
use Mei\Middleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use RunTracy\Helpers\Profiler\Profiler;
use RunTracy\Middlewares\TracyMiddleware;
use Slim\Exception\HttpException;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\Psr7\Factory\StreamFactory;
use Tracy\Debugger;

date_default_timezone_set('UTC');
error_reporting(ERROR_REPORTING);

Profiler::enable();
Profiler::start('app');
Profiler::start('initApp');
$app = Dispatcher::app();
Profiler::finish('initApp');

/** @var Container $di */
$di = $app->getContainer();

// disable further profiling based on run mode
if ($di->get('config')['mode'] == 'production') {
    Profiler::disable();
}

Debugger::$maxDepth = 7;
Debugger::$showFireLogger = false;
Debugger::$maxLength = 520;
Debugger::$logSeverity = ERROR_REPORTING;
Debugger::$reservedMemorySize = 5000000; // 5 megabytes because we increase depth for bluescreen
Debugger::enable(
    $di->get('config')['mode'] == 'development' ? Debugger::DEVELOPMENT : Debugger::PRODUCTION,
    $di->get('config')['logs_dir']
);
if ($di->get(
        'config'
    )['mode'] == 'production') { // tracy resets error_reporting to E_ALL when it's enabled, silence it on production please
    error_reporting(ERROR_REPORTING);
}

Debugger::getBlueScreen()->maxDepth = 7;
Debugger::getBlueScreen()->maxLength = 520;
array_push(
    Debugger::getBlueScreen()->keysToHide,
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
            'panel' => Debugger::dump($di->get('cache')->getCacheHits(), true),
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
            'panel' => Debugger::dump($di->get('instrumentor')->getLog(), true),
        ];
    }
);

// add middleware
// note that the order is important; middleware gets executed as an onion, so
// the first middleware that gets added gets executed last as the request comes
// in and first as the response comes out.
Profiler::start('initMiddlewares');

// 'before' middleware (either stops execution flow or calls next middleware)
$app->add(new Middleware\RequestHelper($di));
if ($di->get('config')['mode'] == 'development') {
    $app->add(new TracyMiddleware($app));
}

// output caching should be in the middle of the stack
$app->add(new OutputBufferingMiddleware(new StreamFactory(), OutputBufferingMiddleware::APPEND));

// 'after' middleware (calls next middleware with modified body)
if ($di->get('config')['mode'] === 'production') {
    $contentLengthMiddleware = new ContentLengthMiddleware();
    $app->add($contentLengthMiddleware); // adds content-length but only on production
}
$app->addBodyParsingMiddleware(); // parses xml and json body
$app->addRoutingMiddleware();

// error handler must be added before everything else on request or it won't handle errors from middleware stack
if ($di->get('config')['mode'] === 'production') {
    $errorHandler = $app->addErrorMiddleware(false, false, false);

    $errorHandler->setErrorHandler( // handling for built-in errors when route not found or method not allowed
        HttpException::class,
        function (Request $request, Throwable $exception) use ($di) {
            return (new ErrorCtrl($di))->handleException(
                $request,
                $di->get('response.factory')->createResponse(),
                $exception
            );
        }
    );
    $errorHandler->setDefaultErrorHandler( // default error handler
        function (Request $request, Throwable $exception) use ($di) {
            return (new ErrorCtrl($di))->handleException(
                $request,
                $di->get('response.factory')->createResponse(),
                $exception
            );
        }
    );
}

Profiler::finish('initMiddlewares');

$app->run();
Profiler::enable(); // enable back profiler to finish() on what it started before it might've been disabled
Profiler::finish('app');

Debugger::barDump($di->get('cache')->getCacheHits(), 'Cache hits');
Debugger::barDump($di->get('instrumentor')->getLog(), 'Instrumentor');
