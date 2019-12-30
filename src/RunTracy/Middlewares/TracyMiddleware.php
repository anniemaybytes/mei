<?php declare(strict_types=1);

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
 *
 * @package RunTracy\Middlewares
 */
class TracyMiddleware
{
    private $container;
    private $versions;

    /**
     * TracyMiddleware constructor.
     *
     * @param App|null $app
     */
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
     * @param Request $request
     * @param Response $response
     * @param Callable $next
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        $res = $next($request, $response);

        Debugger::getBar()->addPanel(
            new SlimEnvironmentPanel(
                Dumper::toHtml($this->container->get('environment')),
                $this->versions
            )
        );

        Debugger::getBar()->addPanel(
            new SlimContainerPanel(
                Dumper::toHtml($this->container),
                $this->versions
            )
        );

        Debugger::getBar()->addPanel(
            new SlimRouterPanel(
                Dumper::toHtml($this->container->get('router')),
                $this->versions
            )
        );

        Debugger::getBar()->addPanel(
            new SlimRequestPanel(
                Dumper::toHtml($this->container->get('request')),
                $this->versions
            )
        );

        Debugger::getBar()->addPanel(
            new SlimResponsePanel(
                Dumper::toHtml($this->container->get('response')),
                $this->versions
            )
        );

        Debugger::getBar()->addPanel(
            new XDebugHelper(
                $this->container->get('settings')['xdebugHelperIdeKey']
            )
        );

        Debugger::getBar()->addPanel(new IncludedFiles());

        Debugger::getBar()->addPanel(new ProfilerPanel());

        return $res;
    }
}
