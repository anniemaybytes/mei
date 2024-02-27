<?php

declare(strict_types=1);

namespace Mei\Controller;

use Mei\Utilities\Time;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class AliveCtrl
 *
 * @package Mei\Controller
 */
final class AliveCtrl extends BaseCtrl
{
    public function check(Request $request, Response $response, array $args): Response
    {
        return $response->withStatus(200)->withJson(['success' => true, 'ts' => Time::now()->getTimestamp()]);
    }
}
