<?php

namespace Mei\Controller;

use Slim\Http\Request;
use Slim\Http\Response;

class ErrorCtrl extends BaseCtrl
{
    public static $STATUS_MESSAGES = array (
        'default' => 'Internal Server Error',
        404       => 'Not Found',
        403       => 'Forbidden',
        415       => 'Unsupported Media Type',
    );

    private function getData($status_code) {
        $data = array();
        $data['status_code'] = $status_code;

        if (!isset(self::$STATUS_MESSAGES[$status_code])) {
            $data['status_message'] = self::$STATUS_MESSAGES['default'];
        }
        else {
            $data['status_message'] = self::$STATUS_MESSAGES[$status_code];
        }

        return $data;
    }

    public function handleException(Request $request, Response $response, $exception)
    {
        // make sure we don't throw an exception
        try {
            $statusCode = 500;
            if ($exception instanceof \Mei\Exception\NotFound) {
                $statusCode = 404;
            }
            if ($exception instanceof \Mei\Exception\AccessDenied) {
                $statusCode = 403;
            }
            if ($exception instanceof \Mei\Exception\NoImages) {
                $statusCode = 415;
            }

            $data = $this->getData($statusCode);

            if (is_subclass_of($exception, '\Mei\Exception\GeneralException')) {
                $desc = $exception->getDescription();
                if(strlen($desc) > 0) $data['status_message'] = $desc;
            }

            $this->logError($request, $exception, $data);

            // clear the body first
            $body = $response->getBody();
            $body->rewind();
            $response = $response->withBody($body);

            if($this->config['site.errors'])
            {
                $response->getBody()->write($data['status_code'].' - '.$data['status_message']);
            }

            $response = $response->withHeader('Cache-Control', 'max-age=0');
            $response = $response->withHeader('Expires', date('r', 0));

            return $response->withStatus($statusCode);
        }
        catch (\Exception $e) {
            error_log('Caught exception in exception handler - ' . $e->getFile() . '(' . $e->getLine() . ') ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $response->getBody()->write('Something broke. Sorry.');
            return $response->withStatus(500);
        }
    }

    private function logError(Request $request, \Exception $exception, $data)
    {
        // don't log 404s
        if ($data['status_code'] == 404) {
            return;
        }

        $uri = $request->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();
        $fragment = $uri->getFragment();
        $path =  $path . ($query ? '?' . $query : '') . ($fragment ? '#' . $fragment : '');
        $method = $request->getMethod();
        $referrer = $request->getHeaderLine('HTTP_REFERER');
        $ua = $request->getHeaderLine('HTTP_USER_AGENT');
        $bt = '';

        $prefix =  "Error: {$data['status_code']} Method: $method $path ";
        if ($referrer) {
            $prefix .= '(referrer: ' . $referrer . ') ';
        }
        $msg = $prefix . ' - ' . $data['status_message'] . ' - ' . $ua;
        $msg = sprintf("%s\n%s:%s", $msg, $exception->getFile(), $exception->getLine());

        $ErrorData = sprintf("%s\nMessage:%s\nBacktrace:\n%s", $msg, $exception->getMessage(), $bt);

        error_log($ErrorData);
    }
}
