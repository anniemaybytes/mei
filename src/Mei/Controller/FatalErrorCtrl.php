<?php

declare(strict_types=1);

namespace Mei\Controller;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;
use Throwable;
use Tracy\Debugger;

/**
 * Class FatalErrorCtrl
 *
 * @package Mei\Controller
 */
final class FatalErrorCtrl
{
    private int $obLevel;

    public function __construct(Container $di)
    {
        $this->obLevel = $di->get('obLevel');
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
        Debugger::log($error, Debugger::ERROR);

        // clear the body first
        $body = $response->getBody();
        $body->rewind();
        $response = $response->withBody($body);

        // clear output buffer
        while (ob_get_level() > @$this->obLevel) {
            $status = ob_get_status();
            if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
                break;
            }
            if (!@ob_end_clean()) { // @ may be not removable
                break;
            }
        }

        return $response
            ->withStatus(500)
            ->withJson(
                ['success' => false, 'error' => (new HttpInternalServerErrorException($request))->getDescription()]
            );
    }
}
