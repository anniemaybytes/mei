<?php

namespace Mei\Route;

class Main extends Base
{
    protected function addRoutes()
    {
        $app = $this->app;

        $app->group('', function () {
            /** @var \Slim\App $this */
            $this->group('/upload', function () {
                /** @var \Slim\App $this */
                $this->post('/account', \Mei\Controller\UploadCtrl::class . ':account')->setName('upload:account');
                $this->post('/screenshot/{torrentid}', \Mei\Controller\UploadCtrl::class . ':screenshot')->setName('upload:screenshot');
                $this->post('/api', \Mei\Controller\UploadCtrl::class . ':api')->setName('upload:api');
            });
            $this->post('/delete/{img}', \Mei\Controller\DeleteCtrl::class . ':delete')->setName('delete');
            $this->get('/{img}', \Mei\Controller\ServeCtrl::class . ':serve')->setName('serve');
            $this->get('/images/{img}', function($request, $response, $args) { // legacy
                /** @var \Slim\Container $this */
                return $response->withRedirect($this->get('router')->pathFor('serve', ['img' => $args['img']]))->withStatus(301);
            })->setName('serve:legacy');
        });
    }
}
