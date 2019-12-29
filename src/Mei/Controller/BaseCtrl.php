<?php declare(strict_types=1);

namespace Mei\Controller;

use Slim\Container;
use Slim\Http\Environment;

/**
 * Class BaseCtrl
 *
 * @package Mei\Controller
 */
abstract class BaseCtrl
{
    /** @var Container $di */
    protected $di;

    /**
     * The configuration array
     */
    protected $config;

    /**
     * @var Environment
     */
    protected $environment;

    public function setDependencies()
    {
        $this->config = $this->di['config'];
        $this->environment = $this->di['environment'];
    }

    /**
     * BaseCtrl constructor.
     *
     * @param $di
     */
    public function __construct(Container &$di)
    {
        $this->di = &$di;

        $this->setDependencies();
    }
}
