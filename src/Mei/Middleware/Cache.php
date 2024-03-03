<?php

declare(strict_types=1);

namespace Mei\Middleware;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RunTracy\Helpers\Profiler\Profiler;
use Slim\HttpCache\CacheProvider;

/**
 * Class Cache
 *
 * @package Cache\Middleware
 */
final class Cache implements MiddlewareInterface
{
    protected Container $di;

    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        Profiler::start(__CLASS__ . '::' . __METHOD__);

        if (!$response->hasHeader('Cache-Control')) {
            $response = $this->di->get(CacheProvider::class)->denyCache($response);
        }

        Profiler::finish(__CLASS__ . '::' . __METHOD__);

        return $response;
    }
}
