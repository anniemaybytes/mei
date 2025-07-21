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
    /**
     * @param ContainerInterface $di
     *
     * @return App<ContainerInterface>
     */
    public static function setup(ContainerInterface $di): App
    {
        /** @var App<ContainerInterface> $app */
        $app = AppFactory::create(container: $di);

        $di->set(ResponseFactoryInterface::class, $app->getResponseFactory());
        $di->set(RouteParser::class, $app->getRouteCollector()->getRouteParser());

        $di->set('routes', [
            new R\Main($app),
        ]);

        if ($di->get('config')['mode'] === 'development') { // install debug-only routes
            $di->set('routes', array_merge($di->get('routes'), [new R\Debug($app)]));
        }

        if ($di->get('config')['mode'] === 'production') { // this explicitly uses production only on purpose
            $app->getRouteCollector()->setCacheFile(BASE_ROOT . '/routes.cache.php');
        }

        return $app;
    }
}
