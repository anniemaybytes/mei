<?php

declare(strict_types=1);

namespace Mei;

use ArrayAccess;
use Mei\Config\Config;
use Mei\Route as R;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;
use RunTracy\Helpers\Profiler\Profiler;
use Singleton\SingletonInterface;
use Singleton\SingletonTrait;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteParser;

/**
 * Class Dispatcher
 *
 * @package Mei
 */
final class Dispatcher implements SingletonInterface
{
    use SingletonTrait;

    private App $app;
    private ArrayAccess $config;
    private Container $di;

    public static function app(): App
    {
        return self::getInstance()->app;
    }

    public static function config(?string $key = null): mixed
    {
        if ($key) {
            return self::getInstance()->config[$key];
        }

        return self::getInstance()->config;
    }

    public static function di(): Container
    {
        return self::getInstance()->di;
    }

    private function initConfig(): void
    {
        Profiler::start(__CLASS__ . '::' . __METHOD__);
        $config = new Config();
        Profiler::finish(__CLASS__ . '::' . __METHOD__);

        $allowedModes = ['production', 'staging', 'development'];
        if (!in_array(@$config['mode'], $allowedModes, true)) {
            throw new RuntimeException(
                'Can not start application with non-recognized mode: ' .
                $config['mode'] . '. Must be one of: ' . implode(', ', $allowedModes)
            );
        }

        $this->config = $config;
    }

    private function initDependencyInjection(): void
    {
        Profiler::start(__CLASS__ . '::' . __METHOD__);
        $this->di = DependencyInjection::setup($this->config);
        Profiler::finish(__CLASS__ . '::' . __METHOD__);
    }

    private function initApplication(): void
    {
        AppFactory::setContainer($this->di);
        $app = AppFactory::create();

        Profiler::start(__CLASS__ . '::' . __METHOD__);

        $routeCollector = $app->getRouteCollector();
        $this->di->set(ResponseFactoryInterface::class, $app->getResponseFactory());

        $routes = [
            new R\Main($app),
        ];
        $this->di->set('routes', $routes);
        $this->di->set(RouteParser::class, $routeCollector->getRouteParser());

        if ($this->di->get('config')['mode'] === 'production') {
            $routeCollector->setCacheFile(BASE_ROOT . '/routes.cache.php');
        }

        Profiler::finish(__CLASS__ . '::' . __METHOD__);

        $this->app = $app;
    }

    protected function __construct()
    {
        $this->initConfig();
        $this->initDependencyInjection();
        $this->initApplication();
    }
}
