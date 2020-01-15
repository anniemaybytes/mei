<?php declare(strict_types=1);

namespace Mei\Controller;

use DI\Container;

/**
 * Class BaseCtrl
 *
 * @package Mei\Controller
 */
abstract class BaseCtrl
{
    /**
     * @var Container $di
     */
    protected $di;

    /**
     * The configuration array
     */
    protected $config;

    public function setDependencies()
    {
        $this->config = $this->di->get('config');
    }

    /**
     * BaseCtrl constructor.
     *
     * @param Container $di
     */
    public function __construct(Container &$di)
    {
        $this->di = &$di;

        $this->setDependencies();
    }
}
