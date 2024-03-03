<?php

declare(strict_types=1);

namespace Mei\Controller;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\HttpCache\CacheProvider;
use Throwable;
use Tracy\Debugger;

/**
 * Class FatalErrorCtrl
 *
 * @package Mei\Controller
 */
final class FatalErrorCtrl
{
    private Container $di;

    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    /**
     * Render very simple error page in case of fatal PHP error
     * More detailed code that may depend on DI wrapped inside try blocks, each their own so that failure of one will not cause
     * previous ones to lose data.
     *
     * @param Request $request
     * @param Response $response
     * @param Throwable $error
     *
     * @return Response
     */
    public function handleError(Request $request, Response $response, Throwable $error): Response
    {
        // have tracy log the error
        Debugger::log($error, Debugger::CRITICAL);

        // clear the body first
        $body = $response->getBody();
        $body->rewind();
        $response = $response->withBody($body);

        // clear output buffer
        while (ob_get_level() > @$this->di->get('ob_level')) {
            $status = ob_get_status();
            if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
                break;
            }
            if (!@ob_end_clean()) { // @ may be not removable
                break;
            }
        }

        /*
         * We need to add Cache-Control header here as it was previously done in middleware.
         * Additionally in case it was changed by code we want to override it to values set below as we
         * really don't want to cache errors
         */
        $response = $this->di->get(CacheProvider::class)->denyCache($response);

        return $response
            ->withStatus(500)
            ->withJson(
                ['success' => false, 'error' => (new HttpInternalServerErrorException($request))->getDescription()]
            );
    }
}
