<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace Mei\Route;

use Mei\Controller\DeleteCtrl;
use Mei\Controller\ServeCtrl;
use Mei\Controller\UploadCtrl;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteParser;

/**
 * Class Main
 *
 * @package Mei\Route
 */
final class Main extends Base
{
    protected function addRoutes(): void  /** @formatter:off */
    {
        $app = $this->app;

        // upload
        $app->group('', function (RouteCollectorProxy $group) {
            $group->group('/upload', function (RouteCollectorProxy $group) {
                $group->post('/account', UploadCtrl::class . ':account')
                    ->setName('upload:account');
                $group->post('/screenshot/{torrentid:[0-9]+}', UploadCtrl::class . ':screenshot')
                    ->setName('upload:screenshot');
                $group->post('/api', UploadCtrl::class . ':api')
                    ->setName('upload:api');
            });

            // delete
            $group->post('/delete', DeleteCtrl::class . ':delete')
                ->setName('delete');

            // serve
            $group->get('/images/error.jpg', function (Request $request, Response $response, array $args) {
                return $response->withRedirect('/error.jpg');
            });

            $group->get(
                '/{img:(?:[a-zA-Z0-9]{32}|[a-zA-Z0-9]{64}|[a-zA-Z0-9]{11})(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}',
                ServeCtrl::class . ':serve'
            )->setName('serve');
            $group->get(
                '/images/{img:[a-zA-Z0-9]{32}(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}',
                function (Request $request, Response $response, array $args) {
                    /** @var Container $this */
                    return $response->withStatus(301)->withRedirect(
                        $this->get(RouteParser::class)->relativeUrlFor(
                            'serve',
                            ['img' => $args['img']]
                        ) // legacy redirect
                    );
                }
            );
        });
    }
    /** @formatter:on */
}
