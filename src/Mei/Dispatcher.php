<?php
namespace Mei;

use Mei\Route as R;
use Slim\App;
use RunTracy\Helpers\Profiler\Profiler;

class Dispatcher extends Singleton
{
    /** @var App $app */
    private $app;

    private $config;

    private $di;

    /**
     * Returns the slim application object
     *
     * @return App
     */
    public static function app()
    {
        return self::getInstance()->app;
    }

    public static function config($key)
    {
        return self::getInstance()->config[$key];
    }

    public static function getConfig()
    {
        return self::getInstance()->config;
    }

    /**
     * Returns the container object
     *
     * @return \Slim\Container
     */
    public static function di()
    {
        return self::getInstance()->di;
    }

    private function initConfig()
    {
        Profiler::start('initConfig');
        $config = ConfigLoader::load();
        Profiler::finish('initConfig');

        $config['site.public_root'] = BASE_ROOT . '/' . $config['site.public_root'];
        $config['site.images_root'] = BASE_ROOT . '/' . $config['site.images_root'];
        $config['site.deleted_root'] = BASE_ROOT . '/' . $config['site.deleted_root'];
        $this->config = $config;
    }

    private function initDependencyInjection()
    {
        Profiler::start('initDependencyInjection');
        $di = DependencyInjection::get($this->config);
        Profiler::finish('initDependencyInjection');

        $this->di = $di;
    }

    private function initApplication()
    {
        $app = new App($this->di);

        Profiler::start('initRoutes');
        $routes = array(
            new R\Main($app),
        );
        $this->di['routes'] = $routes;
        Profiler::finish('initRoutes');

        $this->app = $app;
    }

    protected function __construct($args)
    {
        $this->initConfig();
        $this->initDependencyInjection();
        $this->initApplication();
        parent::__construct($args);
    }
}
