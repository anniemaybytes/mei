<?php

declare(strict_types=1);

namespace Mei;

use Mei\Route as R;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteParser;

/**
 * Class Application
 *
 * @package Mei
 */
final class Application
{
    public static function setup(ContainerInterface $di): App
    {
        AppFactory::setContainer($di);
        $app = AppFactory::create();

        $di->set(ResponseFactoryInterface::class, $app->getResponseFactory());
        $di->set(RouteParser::class, $app->getRouteCollector()->getRouteParser());

        $di->set('routes', [
            new R\Main($app),
        ]);

        if ($di->get('config')['mode'] === 'production') { // this explicitly uses production only on purpose
            $app->getRouteCollector()->setCacheFile(BASE_ROOT . '/routes.cache.php');
        }

        return $app;
    }
}
