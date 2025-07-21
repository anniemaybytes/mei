<?php

declare(strict_types=1);

namespace Mei\Route;

use Mei\Controller\DebugCtrl;
use Slim\Routing\RouteCollectorProxy;

/**
 * Class Main
 *
 * @package Mei\Route
 */
final class Debug extends Base /** @formatter:off */
{
    protected function addRoutes(): void
    {
        $this->app->group('', function (RouteCollectorProxy $group) {
            $group->get('/', DebugCtrl::class . ':index')->setName('index');

            $group->get('/upload', DebugCtrl::class . ':upload')->setName('upload');
        });
    }
}
