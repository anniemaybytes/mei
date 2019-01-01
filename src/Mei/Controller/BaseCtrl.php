<?php
namespace Mei\Controller;

abstract class BaseCtrl
{
    protected $di;

    /**
     * The configuration array
     */
    protected $config;

    /**
     * @var \Slim\Http\Environment
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
