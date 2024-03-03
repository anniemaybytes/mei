<?php

declare(strict_types=1);

namespace Mei\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RunTracy\Helpers\Profiler\Profiler;

/**
 * Class AccessControl
 *
 * @package Mei\Middleware
 */
final class AccessControl implements MiddlewareInterface
{
    protected static array $corsAllowList = [
        '/upload',
        '/delete'
    ];

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        Profiler::start(__CLASS__ . '::' . __METHOD__);
        $response = self::applyHeaders($request, $response);
        Profiler::finish(__CLASS__ . '::' . __METHOD__);

        return $response;
    }

    public static function applyHeaders(Request $request, Response $response): Response
    {
        $uri = $request->getUri();

        foreach (self::$corsAllowList as $path) {
            if (str_starts_with($uri->getPath(), $path)) {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            }
        }

        return $response;
    }
}
