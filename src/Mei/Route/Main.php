<?php

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
final class Main extends Base /** @formatter:off */
{
    protected function addRoutes(): void
    {
        $app = $this->app;

        $app->group('', function (RouteCollectorProxy $group) {
            // upload
            $group->group('/upload', function (RouteCollectorProxy $group) {
                $group->post('/user', UploadCtrl::class . ':user')
                    ->setName('upload:user');
                $group->post('/api', UploadCtrl::class . ':api')
                    ->setName('upload:api');
            });

            // delete
            $group->post('/delete', DeleteCtrl::class . ':delete')
                ->setName('delete');

            // serve
            $group->get(
                '/{image:(?:[a-zA-Z0-9]{32}|[a-zA-Z0-9]{64}|[a-zA-Z0-9]{11})(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}',
                ServeCtrl::class . ':serve'
            )->setName('serve');

            // legacy redirects
            $group->group('/images', function (RouteCollectorProxy $group) {
                $group->get('/error.jpg', function (Request $request, Response $response, array $args) {
                    return $response->withRedirect('/error.jpg');
                });
                $group->get(
                    '/{image:[a-zA-Z0-9]{32}(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}',
                    function (Request $request, Response $response, array $args) {
                        /** @var Container $this */
                        return $response->withStatus(301)->withRedirect(
                            $this->get(RouteParser::class)->relativeUrlFor(
                                'serve',
                                ['image' => $args['image']]
                            )
                        );
                    }
                );
            });
        });
    }
}
