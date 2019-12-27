<?php /** @noinspection PhpInconsistentReturnPointsInspection */

namespace Mei\Controller;

use Exception;
use Mei\Exception\AccessDenied;
use Mei\Exception\NoImages;
use Mei\Exception\NotFound;
use Slim\Http\Request;
use Slim\Http\Response;
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
     * @param $status_code
     *
     * @return array
     */
    private function getData($status_code)
    {
        $data = [];
        $data['status_code'] = $status_code;

        if (!isset(self::$STATUS_MESSAGES[$status_code])) {
            $data['status_message'] = self::$STATUS_MESSAGES['default'];
        } else {
            $data['status_message'] = self::$STATUS_MESSAGES[$status_code];
        }

        return $data;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $exception
     *
     * @return Response
     */
    public function handleException(Request $request, Response $response, $exception)
    {
        // make sure we don't throw an exception
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

            if (is_subclass_of($exception, '\Mei\Exception\GeneralException')) {
                $desc = $exception->getDescription();
                if (strlen($desc) > 0) {
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
        } catch (Exception $e) {
            Debugger::log($e, Debugger::EXCEPTION);
        }
    }

    /**
     * @param Request $request
     * @param Exception $exception
     * @param $data
     */
    private function logError(Request $request, Exception $exception, $data)
    {
        // don't log 404s
        if ($data['status_code'] == 404 || $data['status_code'] == 403 || $data['status_code'] == 415) {
            return;
        }

        Debugger::log($exception, Debugger::EXCEPTION);
    }
}
