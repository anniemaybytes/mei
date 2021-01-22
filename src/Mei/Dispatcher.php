<?php

declare(strict_types=1);

namespace Mei;

use Exception;
use Mei\Route as R;
use PetrKnap\Php\Singleton\SingletonInterface;
use PetrKnap\Php\Singleton\SingletonTrait;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;
use RunTracy\Helpers\Profiler\Profiler;
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
    private array $config;
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

    /**
     * @throws Exception
     */
    private function initConfig(): void
    {
        Profiler::start('initConfig');
        $config = ConfigLoader::load();
        Profiler::finish('initConfig');

        $allowedModes = ['production', 'staging', 'development'];
        if (!in_array(@$config['mode'], $allowedModes, true)) {
            throw new RuntimeException(
                'Can not start application with non-recognized mode: ' .
                ($config['mode'] ?? '(null)') .
                '. Must be one of: ' . implode(', ', $allowedModes)
            );
        }

        $config['app.max_filesize'] = $config['app.max_filesize'] ?? PHP_INT_MAX;
        $config['images.directory'] = BASE_ROOT . '/' . ($config['images.directory'] ?? 'images');
        $this->config = $config;
    }

    /**
     * @throws Exception
     */
    private function initDependencyInjection(): void
    {
        Profiler::start('initDependencyInjection');
        $this->di = DependencyInjection::setup($this->config);
        Profiler::finish('initDependencyInjection');
    }

    private function initApplication(): void
    {
        AppFactory::setContainer($this->di);
        $app = AppFactory::create();

        Profiler::start('initRoutes');

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

        Profiler::finish('initRoutes');

        $this->app = $app;
    }

    /**
     * @throws Exception
     */
    protected function __construct()
    {
        $this->initConfig();
        $this->initDependencyInjection();
        $this->initApplication();
    }
}
