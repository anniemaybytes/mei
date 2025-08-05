<?php

declare(strict_types=1);

const BASE_ROOT = __DIR__;
const ERROR_REPORTING = E_ALL & ~(E_NOTICE | E_DEPRECATED);
require_once BASE_ROOT . '/vendor/autoload.php';

use Mei\Application;
use Mei\Controller\ErrorCtrl;
use Mei\Dispatcher;
use Mei\Middleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use RunTracy\Helpers\IncludedFiles;
use RunTracy\Helpers\Profiler\Profiler;
use RunTracy\Helpers\ProfilerPanel;
use RunTracy\Helpers\XDebugHelper;
use Slim\Middleware\ContentLengthMiddleware;
use Tracy\Debugger;

date_default_timezone_set('UTC');
putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1');

$di = Dispatcher::di();
$app = Application::setup($di);

$isDev = $di->get('config')['mode'] === 'development';

// ---

Debugger::$maxDepth = 7;
Debugger::$maxLength = 520;
Debugger::$logSeverity = ERROR_REPORTING;
Debugger::$reservedMemorySize = 5000000; // 5 megabytes because we increase depth for bluescreen

if ($isDev) {
    Debugger::getBar()->addPanel(new ProfilerPanel());
    Debugger::getBar()->addPanel(new IncludedFiles());
    Debugger::getBar()->addPanel(new XDebugHelper('yes'));

    Profiler::enable();
}

Debugger::getBlueScreen()->maxDepth = 7;
Debugger::getBlueScreen()->maxLength = 520;
array_push(
    Debugger::getBlueScreen()->keysToHide,
    'SERVER_ADDR',
    'PHP_AUTH_PW'
);

Debugger::enable($isDev ? Debugger::Development : Debugger::Production, $di->get('config')['logs_dir']);
error_reporting($isDev ? E_ALL : ERROR_REPORTING);

/*
 * Note that the order is important; middleware gets executed as an onion, so
 * the first middleware that gets added gets executed last as the request comes
 * in and first as the response comes out.
 */

$app->addBodyParsingMiddleware();
// ---
if (!$isDev) {
    $app->add(new ContentLengthMiddleware());
}
$app->add(new Middleware\AccessControl());
$app->add(new Middleware\Cache($di));
$app->addRoutingMiddleware();
// ---
if (!$isDev) {
    $errorHandler = $app->addErrorMiddleware(false, false, false);
    $errorHandler->setDefaultErrorHandler(
        function (Request $request, Throwable $exception) use ($di) {
            return (new ErrorCtrl($di))->handleException(
                $request,
                $di->get(ResponseFactoryInterface::class)->createResponse(),
                $exception
            );
        }
    );
}

$app->run();
