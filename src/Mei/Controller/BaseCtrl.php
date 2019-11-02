<?php

namespace Mei\Controller;

use Slim\Http\Environment;

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

    public function setDependencies($di)
    {
        $this->config = $di['config'];
        $this->environment = $di['environment'];
    }

    public function __construct($di)
    {
        $this->di = $di;

        $this->setDependencies($di);
    }
}
