<?php

namespace RunTracy\Middlewares;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RunTracy\Helpers\IncludedFiles;
use RunTracy\Helpers\ProfilerPanel;
use RunTracy\Helpers\SlimContainerPanel;
use RunTracy\Helpers\SlimEnvironmentPanel;
use RunTracy\Helpers\SlimRequestPanel;
use RunTracy\Helpers\SlimResponsePanel;
use RunTracy\Helpers\SlimRouterPanel;
use RunTracy\Helpers\XDebugHelper;
use Slim\App;
use Tracy\Debugger;
use Tracy\Dumper;

/**
 * Class TracyMiddleware
 * @package RunTracy\Middlewares
 */
class TracyMiddleware
{
    private $container;
    private $versions;

    public function __construct(App $app = null)
    {
        if ($app instanceof App) {
            $this->container = $app->getContainer();
            $this->versions = [
                'slim' => App::VERSION,
            ];
        }
    }

    /**
     * @param $request Request
     * @param $response Response
     * @param $next Callable
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $res = $next($request, $response);

        Debugger::getBar()->addPanel(new SlimEnvironmentPanel(
            Dumper::toHtml($this->container->get('environment')),
            $this->versions
        ));

        Debugger::getBar()->addPanel(new SlimContainerPanel(
            Dumper::toHtml($this->container),
            $this->versions
        ));

        Debugger::getBar()->addPanel(new SlimRouterPanel(
            Dumper::toHtml($this->container->get('router')),
            $this->versions
        ));

        Debugger::getBar()->addPanel(new SlimRequestPanel(
            Dumper::toHtml($this->container->get('request')),
            $this->versions
        ));

        Debugger::getBar()->addPanel(new SlimResponsePanel(
            Dumper::toHtml($this->container->get('response')),
            $this->versions
        ));

        Debugger::getBar()->addPanel(new XDebugHelper(
            $this->container->get('settings')['xdebugHelperIdeKey']
        ));

        Debugger::getBar()->addPanel(new IncludedFiles());

        Debugger::getBar()->addPanel(new ProfilerPanel());

        return $res;
    }
}
