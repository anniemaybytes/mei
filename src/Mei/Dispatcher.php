<?php declare(strict_types=1);

namespace Mei;

use DI\Container;
use Exception;
use Mei\Route as R;
use RunTracy\Helpers\Profiler\Profiler;
use Slim\App;
use Slim\Factory\AppFactory;

/**
 * Class Dispatcher
 *
 * @package Mei
 */
class Dispatcher extends Singleton
{
    /** @var App $app */
    private $app;

    /** @var array $config */
    private $config;

    /** @var Container $di */
    private $di;

    /**
     * Returns the slim application object
     *
     * @return App
     */
    public static function app(): App
    {
        return self::getInstance()->app;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public static function config($key)
    {
        return self::getInstance()->config[$key];
    }

    /**
     * @return array
     */
    public static function getConfig(): array
    {
        return self::getInstance()->config;
    }

    /**
     * Returns the container object
     *
     * @return Container
     */
    public static function di(): Container
    {
        return self::getInstance()->di;
    }

    /**
     * @throws Exception
     */
    private function initConfig()
    {
        Profiler::start('initConfig');
        $config = ConfigLoader::load();
        Profiler::finish('initConfig');

        $config['site.images_root'] = BASE_ROOT . '/' . $config['site.images_root'];
        $config['logs_dir'] = BASE_ROOT . '/' . $config['logs_dir'];
        $this->config = $config;
    }

    /**
     * @throws Exception
     */
    private function initDependencyInjection()
    {
        Profiler::start('initDependencyInjection');
        $this->di = DependencyInjection::setup($this->config);
        Profiler::finish('initDependencyInjection');
    }

    private function initApplication()
    {
        AppFactory::setContainer($this->di);
        $app = AppFactory::create();

        Profiler::start('initRoutes');

        $routeCollector = $app->getRouteCollector();
        $this->di->set('response.factory', $app->getResponseFactory());

        $routes = [
            new R\Main($app),
        ];
        $this->di->set('routes', $routes);
        $this->di->set('router', $routeCollector->getRouteParser());

        if ($this->di->get('config')['mode'] === 'production') {
            $routeCollector->setCacheFile(BASE_ROOT . '/routes.cache.php');
        }

        Profiler::finish('initRoutes');

        $this->app = $app;
    }

    /**
     * Dispatcher constructor.
     *
     * @param $args
     *
     * @throws Exception
     */
    protected function __construct($args)
    {
        $this->initConfig();
        $this->initDependencyInjection();
        $this->initApplication();
        parent::__construct($args);
    }
}
