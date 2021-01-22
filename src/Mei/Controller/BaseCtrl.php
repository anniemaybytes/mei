<?php

declare(strict_types=1);

namespace Mei\Controller;

/**
 * Class BaseCtrl
 *
 * @package Mei\Controller
 */
abstract class BaseCtrl
{
    /**
     * The configuration array
     *
     * @Inject("config")
     */
    protected array $config;
}
