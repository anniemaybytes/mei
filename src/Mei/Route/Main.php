<?php
namespace Mei\Route;

use Mei\Controller\DeleteCtrl;
use Mei\Controller\ServeCtrl;
use Mei\Controller\UploadCtrl;

class Main extends Base
{
    protected function addRoutes()
    {
        $app = $this->app;

        $app->group('', function () {
            /** @var \Slim\App $this */
            $this->group('/upload', function () {
                /** @var \Slim\App $this */
                $this->post('/account', UploadCtrl::class . ':account')->setName('upload:account');
                $this->post('/screenshot/{torrentid}', UploadCtrl::class . ':screenshot')->setName('upload:screenshot');
                $this->post('/api', UploadCtrl::class . ':api')->setName('upload:api');
            });
            $this->post('/delete/{img}', DeleteCtrl::class . ':delete')->setName('delete');
            $this->get('/{img}', ServeCtrl::class . ':serve')->setName('serve');
            $this->get('/images/{img}', function($request, $response, $args) { // legacy
                /** @var \Slim\Container $this */
                return $response->withRedirect($this->get('router')->pathFor('serve', ['img' => $args['img']]))->withStatus(301);
            })->setName('serve:legacy');
        });
    }
}
