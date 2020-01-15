<?php declare(strict_types=1);

namespace Mei\Route;

use DI\Container;
use Mei\Controller\DeleteCtrl;
use Mei\Controller\ServeCtrl;
use Mei\Controller\UploadCtrl;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteCollectorProxy;

/**
 * Class Main
 *
 * @package Mei\Route
 */
class Main extends Base
{
    protected function addRoutes()  /** @formatter:off */
    {
        $app = $this->app;

        $app->group('', function (RouteCollectorProxy $group) {
            $group->group('/upload', function (RouteCollectorProxy $group) {
                $group->post('/account', UploadCtrl::class . ':account')->setName('upload:account');
                $group->post('/screenshot/{torrentid:[0-9]+}', UploadCtrl::class . ':screenshot')->setName('upload:screenshot');
                $group->post('/api', UploadCtrl::class . ':api')->setName('upload:api');
            });
            $group->post('/delete', DeleteCtrl::class . ':delete')->setName('delete');
            $group->get('/{img:(?:[a-zA-Z0-9]{32}|[a-zA-Z0-9]{64}|[a-zA-Z0-9]{11})(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}', ServeCtrl::class . ':serve')->setName('serve');
            $group->get('/images/error.jpg', function (Request $request, Response $response, array $args) {
                return $response->withRedirect('/error.jpg')->withStatus(301);
            });
            $group->get('/images/{img:[a-zA-Z0-9]{32}(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}',
                function (Request $request, Response $response, array $args) { // legacy
                    /** @var Container $this */
                    return $response->withRedirect($this->get('router')->relativeUrlFor('serve', ['img' => $args['img']]))->withStatus(301);
                }
            )->setName('serve:legacy');
        });
    }
    /** @formatter:on */
}
