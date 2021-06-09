<?php

declare(strict_types=1);

const BASE_ROOT = __DIR__;
const ERROR_REPORTING = E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED);
require_once BASE_ROOT . '/vendor/autoload.php'; // set up autoloading

use DI\Container;
use Mei\Controller\ErrorCtrl;
use Mei\Dispatcher;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use RunTracy\Helpers\IncludedFiles;
use RunTracy\Helpers\Profiler\Profiler;
use RunTracy\Helpers\ProfilerPanel;
use RunTracy\Helpers\XDebugHelper;
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
if ($di->get('config')['mode'] !== 'development') {
    Profiler::disable();
}

// configure pre-runtime tracy
Debugger::$maxDepth = 7;
Debugger::$showFireLogger = false;
Debugger::$maxLength = 520;
Debugger::$logSeverity = ERROR_REPORTING;
Debugger::$reservedMemorySize = 5000000; // 5 megabytes because we increase depth for bluescreen

// enable tracy
Debugger::enable(
    $di->get('config')['mode'] === 'development' ?
        Debugger::DEVELOPMENT : Debugger::PRODUCTION,
    $di->get('config')['logs_dir'] ?? (BASE_ROOT . '/logs')
);
if ($di->get('config')['mode'] !== 'development') {
    // tracy resets error_reporting to E_ALL when it's enabled, silence it on production please
    error_reporting(ERROR_REPORTING);
}

// configure runtime tracy
Debugger::getBlueScreen()->maxDepth = 7;
Debugger::getBlueScreen()->maxLength = 520;
array_push(
    Debugger::getBlueScreen()->keysToHide,
    'SERVER_ADDR',
    'PHP_AUTH_PW'
);

// setup additional panels
if ($di->get('config')['mode'] === 'development') {
    Debugger::getBar()->addPanel(new ProfilerPanel());
    Debugger::getBar()->addPanel(new IncludedFiles());
    Debugger::getBar()->addPanel(new XDebugHelper('yes'));
}

// add middleware
// note that the order is important; middleware gets executed as an onion, so
// the first middleware that gets added gets executed last as the request comes
// in and first as the response comes out.
Profiler::start('initMiddlewares');

// 'before' middleware (either stops execution flow or calls next middleware)
$app->addBodyParsingMiddleware(); // parses xml and json body

// output caching should be in the middle of the stack
$app->add(new OutputBufferingMiddleware(new StreamFactory(), OutputBufferingMiddleware::APPEND));

// 'after' middleware (calls next middleware with modified body)
if ($di->get('config')['mode'] !== 'development') {
    $contentLengthMiddleware = new ContentLengthMiddleware();
    $app->add($contentLengthMiddleware); // adds content-length but only on production
}
$app->addRoutingMiddleware();

// error handler must be added before everything else on request or it won't handle errors from middleware stack
if ($di->get('config')['mode'] !== 'development') {
    $errorHandler = $app->addErrorMiddleware(false, false, false);

    $errorHandler->setErrorHandler( // handling for built-in errors when route not found or method not allowed
        HttpException::class,
        function (Request $request, Throwable $exception) use ($di) {
            return (new ErrorCtrl($di))->handleException(
                $request,
                $di->get(ResponseFactoryInterface::class)->createResponse(),
                $exception
            );
        }
    );
    $errorHandler->setDefaultErrorHandler( // default error handler
        function (Request $request, Throwable $exception) use ($di) {
            return (new ErrorCtrl($di))->handleException(
                $request,
                $di->get(ResponseFactoryInterface::class)->createResponse(),
                $exception
            );
        }
    );
}

Profiler::finish('initMiddlewares');

$app->run();
Profiler::enable(); // enable back profiler to finish() on what it started before it might've been disabled
Profiler::finish('app');
