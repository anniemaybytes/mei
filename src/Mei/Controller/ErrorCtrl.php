<?php declare(strict_types=1);

namespace Mei\Controller;

use Mei\Exception\GeneralException;
use Mei\Exception\NoImages;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpException;
use Throwable;
use Tracy\Debugger;

/**
 * Class ErrorCtrl
 *
 * @package Mei\Controller
 */
class ErrorCtrl extends BaseCtrl
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Throwable $exception
     *
     * @return Response
     */
    public function handleException(Request $request, Response $response, Throwable $exception): Response
    {
        try {
            $status = 500;
            $message = '500 Internal Server Error';
            if ($exception instanceof HttpException) {
                $status = $exception->getCode();
                $message = $exception->getTitle();
            } elseif ($exception instanceof NoImages) {
                $status = 415;
                $message = "415 Unsupported Media Type - " . $exception->getMessage();
            } elseif ($exception instanceof GeneralException) {
                $message .= " - {$exception->getMessage()}";
            }
            $this->logError($request, $exception, $status);

            // clear the body first
            $body = $response->getBody();
            $body->rewind();
            $response = $response->withBody($body);

            // clear output buffer
            while (ob_get_level() > $this->di->get('obLevel')) {
                $status = ob_get_status();
                if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
                    break;
                }
                if (!@ob_end_clean()) { // @ may be not removable
                    break;
                }
            }

            return $response->withStatus($status)->write($message);
        } catch (Throwable $e) {
            return (new FatalErrorCtrl($this->di))->handleError($request, $response, $e);
        }
    }

    /**
     * @param Request $request
     * @param Throwable $exception
     * @param int $status
     */
    private function logError(Request $request, Throwable $exception, int $status)
    {
        if ($status !== 500) {
            return;
        }

        Debugger::log($exception, Debugger::EXCEPTION);
    }
}

