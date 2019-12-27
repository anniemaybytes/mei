<?php

namespace Mei\Route;

use Mei\Controller\DeleteCtrl;
use Mei\Controller\ServeCtrl;
use Mei\Controller\UploadCtrl;
use Slim\App;
use Slim\Container;

/**
 * Class Main
 *
 * @package Mei\Route
 */
class Main extends Base
{
    protected function addRoutes()
        /** @formatter:off */
    {
        $app = $this->app;

        $app->group('', function () {
            /** @var App $this */
            $this->group('/upload', function () {
                /** @var App $this */
                $this->post('/account', UploadCtrl::class . ':account')->setName('upload:account');
                $this->post('/screenshot/{torrentid:[0-9]+}', UploadCtrl::class . ':screenshot')->setName('upload:screenshot');
                $this->post('/api', UploadCtrl::class . ':api')->setName('upload:api');
            });
            $this->post('/delete', DeleteCtrl::class . ':delete')->setName('delete');
            $this->get('/{img:(?:[a-zA-Z0-9]{32}|[a-zA-Z0-9]{64}|[a-zA-Z0-9]{11})(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}', ServeCtrl::class . ':serve')->setName('serve');
            $this->get('/images/error.jpg', function ($request, $response, $args) {
                /** @var Container $this */
                return $response->withRedirect('/error.jpg')->withStatus(301);
            });
            $this->get('/images/{img:[a-zA-Z0-9]{32}(?:-\d{2,3}x\d{2,3}(?:-crop)?)?\.[a-zA-Z]{3,4}}', function ($request, $response, $args) { // legacy
                /** @var Container $this */
                return $response->withRedirect($this->get('router')->pathFor('serve', ['img' => $args['img']]))->withStatus(301);
            })->setName('serve:legacy');
        });
    }
    /** @formatter:on */
}
