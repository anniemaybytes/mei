<?php

namespace Mei\Route;

abstract class Base
{
    /**
     * @var \Slim\App
     */
    protected $app;

    public function __construct(\Slim\App $app)
    {
        $this->app = $app;
        $this->addRoutes();
    }

    abstract protected function addRoutes();
}
