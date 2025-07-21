<?php

declare(strict_types=1);

namespace Mei\Controller;

use Mei\Utilities\SimpleTemplate;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class DebugCtrl
 *
 * @package Mei\Controller
 */
final class DebugCtrl extends BaseCtrl
{
    public function index(Request $request, Response $response, array $args): Response
    {
        $body = $response->getBody();
        $body->write(SimpleTemplate::render('Debug/Index'));

        return $response->withStatus(200)->withBody($body);
    }

    public function upload(Request $request, Response $response, array $args): Response
    {
        $body = $response->getBody();
        $body->write(SimpleTemplate::render('Debug/UploadForm'));

        return $response->withStatus(200)->withBody($body);
    }
}
