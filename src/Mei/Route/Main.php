<?php

declare(strict_types=1);

namespace Mei\Route;

use Mei\Controller\AliveCtrl;
use Mei\Controller\DeleteCtrl;
use Mei\Controller\ServeCtrl;
use Mei\Controller\UploadCtrl;
use Slim\Routing\RouteCollectorProxy;

/**
 * Class Main
 *
 * @package Mei\Route
 */
final class Main extends Base /** @formatter:off */
{
    protected function addRoutes(): void
    {
        $app = $this->app;

        $app->group('', function (RouteCollectorProxy $group) {
            // upload
            $group->group('/upload', function (RouteCollectorProxy $group) {
                $group->post('/user', UploadCtrl::class . ':user')->setName('upload:user');
                $group->post('/api', UploadCtrl::class . ':api')->setName('upload:api');
            });

            // delete
            $group->post('/delete', DeleteCtrl::class . ':delete')->setName('delete');

            // serve
            $group->get(
                '/{image:(?:[a-zA-Z0-9]{32}|[a-zA-Z0-9]{64}|[a-zA-Z0-9]{11})(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}',
                ServeCtrl::class . ':serve'
            )->setName('serve');

            // alive check
            $group->get('/alive', AliveCtrl::class . ':check');
        });
    }
}
