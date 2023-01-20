<?php

declare(strict_types=1);

namespace Mei\Controller;

use ArrayAccess;
use DI\Attribute\Inject;

/**
 * Class BaseCtrl
 *
 * @package Mei\Controller
 */
abstract class BaseCtrl
{
    #[Inject("config")]
    protected ArrayAccess $config;
}
