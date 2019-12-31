<?php declare(strict_types=1);

namespace Mei\Controller;

use Mei\Exception\AccessDenied;
use Mei\Exception\GeneralException;
use Mei\Exception\NoImages;
use Mei\Exception\NotFound;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;
use Tracy\Debugger;

/**
 * Class ErrorCtrl
 *
 * @package Mei\Controller
 */
class ErrorCtrl extends BaseCtrl
{
    public static $STATUS_MESSAGES = [
        'default' => 'Internal Server Error',
        404 => 'Not Found',
        403 => 'Forbidden',
        415 => 'Unsupported Media Type',
    ];

    /**
     * @param $statusCode
     *
     * @return array
     */
    private function getData($statusCode): array
    {
        $data = [];
        $data['status_code'] = $statusCode;

        if (!isset(self::$STATUS_MESSAGES[$statusCode])) {
            $data['status_message'] = self::$STATUS_MESSAGES['default'];
        } else {
            $data['status_message'] = self::$STATUS_MESSAGES[$statusCode];
        }

        return $data;
    }

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
            $statusCode = 500;
            if ($exception instanceof NotFound) {
                $statusCode = 404;
            }
            if ($exception instanceof AccessDenied) {
                $statusCode = 403;
            }
            if ($exception instanceof NoImages) {
                $statusCode = 415;
            }

            $data = $this->getData($statusCode);

            if (is_subclass_of(
                    $exception,
                    '\Mei\Exception\GeneralException'
                ) || $exception instanceof GeneralException) {
                $desc = $exception->getDescription();
                if (is_string($desc) && strlen($desc) > 0) {
                    $data['status_message'] = $desc;
                }
            }

            $this->logError($request, $exception, $data);

            // clear the body first
            $body = $response->getBody();
            $body->rewind();
            $response = $response->withBody($body);

            // clear output buffer
            while (ob_get_level() > $this->di['obLevel']) {
                $status = ob_get_status();
                if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
                    break;
                }
                if (!@ob_end_clean()) { // @ may be not removable
                    break;
                }
            }

            if ($this->config['site.errors']) {
                $response->getBody()->write($data['status_code'] . ' - ' . $data['status_message']);
            }

            $response = $response->withHeader('Cache-Control', 'max-age=0');
            $response = $response->withHeader('Expires', date('r', 0));

            return $response->withStatus($statusCode);
        } catch (Throwable $e) {
            return (new FatalErrorCtrl($this->di))->handleError($request, $response, $e);
        }
    }

    /**
     * @param Request $request
     * @param Throwable $exception
     * @param array $data
     */
    private function logError(Request $request, Throwable $exception, array $data)
    {
        // don't log 404s
        if ($data['status_code'] == 404 || $data['status_code'] == 403 || $data['status_code'] == 415) {
            return;
        }

        Debugger::log($exception, Debugger::EXCEPTION);
    }
}
