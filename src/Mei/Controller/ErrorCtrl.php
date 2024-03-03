<?php

declare(strict_types=1);

namespace Mei\Controller;

use Mei\Middleware\AccessControl;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\HttpCache\CacheProvider;
use Throwable;
use Tracy\Debugger;

/**
 * Class ErrorCtrl
 *
 * @package Mei\Controller
 */
final class ErrorCtrl
{
    private Container $di;

    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    public function handleException(Request $request, Response $response, Throwable $exception): Response
    {
        try {
            if ($exception instanceof HttpException) {
                $code = $exception->getCode();
                $description = $exception->getDescription();
            } else {
                $code = 500;
                $description = (new HttpInternalServerErrorException($request))->getDescription();
            }

            if ($code === 500) {
                Debugger::log($exception, Debugger::EXCEPTION);
            }

            // clear the body first
            $body = $response->getBody();
            $body->rewind();
            $response = $response->withBody($body);

            // clear output buffer
            while (ob_get_level() > $this->di->get('ob_level')) {
                $status = ob_get_status();
                if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
                    break;
                }
                if (!@ob_end_clean()) { // @ may be not removable
                    break;
                }
            }

            /*
             * Apply security headers.
             * This is done here again because if we got here it means that in Slim freamwork in process()
             * during callMiddlewareStack() an exception was thrown and catched and $response was trashed in favor
             * of calling exception handler.
             */
            $response = AccessControl::applyHeaders($request, $response);

            /*
             * We need to add Cache-Control header here as it was previously done in middleware.
             * Additionally in case it was changed by code we want to override it to values set below as we
             * really don't want to cache errors
             */
            $response = $this->di->get(CacheProvider::class)->denyCache($response);

            return $response
                ->withStatus($code)
                ->withJson(['success' => false, 'error' => $description]);
        } catch (Throwable $e) {
            return (new FatalErrorCtrl($this->di))->handleError($request, $response, $e);
        }
    }
}
