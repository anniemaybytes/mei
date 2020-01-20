<?php declare(strict_types=1);

namespace RunTracy\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use RunTracy\Helpers\IncludedFiles;
use RunTracy\Helpers\ProfilerPanel;
use RunTracy\Helpers\SlimContainerPanel;
use RunTracy\Helpers\SlimRequestPanel;
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
     * @param RequestHandlerInterface $handler
     *
     * @return Response
     */
    public function __invoke(Request $request, $handler): Response
    {
        $res = $handler->handle($request);

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
            new XDebugHelper(
                $this->container->get('settings')['xdebugHelperIdeKey']
            )
        );

        Debugger::getBar()->addPanel(new IncludedFiles());

        Debugger::getBar()->addPanel(new ProfilerPanel());

        return $res;
    }
}
