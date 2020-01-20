<?php declare(strict_types=1);

namespace Mei\Middleware;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RunTracy\Helpers\Profiler\Profiler;

/**
 * Class RequestHelper
 *
 * Provides accurate 'request' to the Container
 * It should be last middleware to run
 *
 * @package Mei\Middleware
 */
class RequestHelper implements MiddlewareInterface
{
    /**
     * @var Container
     */
    protected $di;

    /**
     * RouteHelper constructor.
     *
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    /**
     * @param Request $request
     * @param RequestHandlerInterface $handler
     *
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        Profiler::start('requestHelperMiddleware');
        $this->di->set('request', $request);
        Profiler::finish('requestHelperMiddleware');
        return $handler->handle($request);
    }
}
