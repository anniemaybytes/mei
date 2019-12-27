<?php

namespace Mei\Controller;

use Slim\Http\Environment;

/**
 * Class BaseCtrl
 *
 * @package Mei\Controller
 */
abstract class BaseCtrl
{
    protected $di;

    /**
     * The configuration array
     */
    protected $config;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @param $di
     */
    public function setDependencies($di)
    {
        $this->config = $di['config'];
        $this->environment = $di['environment'];
    }

    /**
     * BaseCtrl constructor.
     *
     * @param $di
     */
    public function __construct($di)
    {
        $this->di = $di;

        $this->setDependencies($di);
    }
}
