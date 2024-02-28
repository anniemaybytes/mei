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
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        Profiler::start(__CLASS__ . '::' . __METHOD__);
        $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
        Profiler::finish(__CLASS__ . '::' . __METHOD__);

        return $response;
    }
}
