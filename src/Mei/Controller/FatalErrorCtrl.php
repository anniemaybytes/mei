<?php declare(strict_types=1);

namespace Mei\Controller;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;
use Tracy\Debugger;

/**
 * Class FatalErrorCtrl
 *
 * @package Mei\Controller
 */
class FatalErrorCtrl
{
    /**
     * @var Container
     */
    private $di;

    /**
     * FatalErrorCtrl constructor.
     *
     * @param $di
     */
    public function __construct(Container &$di)
    {
        $this->di = &$di;
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
        while (ob_get_level() > @$this->di['obLevel']) {
            $status = ob_get_status();
            if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
                break;
            }
            if (!@ob_end_clean()) { // @ may be not removable
                break;
            }
        }

        $response->getBody()->write('500 - Interval Server Error');
        return $response->withStatus(500);
    }
}
