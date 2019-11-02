<?php

namespace Mei\Route;

use Slim\App;

abstract class Base
{
    /**
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->addRoutes();
    }

    abstract protected function addRoutes();
}
