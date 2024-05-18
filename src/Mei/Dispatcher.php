<?php

declare(strict_types=1);

namespace Mei;

use ArrayAccess;
use Exception;
use Mei\Config\Config;
use Mei\Environment\CLI;
use Mei\Environment\SAPI;
use Psr\Container\ContainerInterface as Container;
use Singleton\SingletonInterface;
use Singleton\SingletonTrait;

/**
 * Class Dispatcher
 *
 * @package Mei
 */
final class Dispatcher implements SingletonInterface
{
    use SingletonTrait;

    private ArrayAccess $config;
    private Container $di;

    /** @throws Exception */
    protected function __construct()
    {
        $this->config = new Config();
        $this->di = DependencyInjection::build(
            PHP_SAPI === 'cli' ? CLI::definitions() : SAPI::definitions(),
            $this->config,
            $this->config['mode'] === 'production' && PHP_SAPI !== 'cli'
        );
    }

    public static function config(?string $key = null): mixed
    {
        if ($key) {
            return self::getInstance()->config[$key];
        }

        return self::getInstance()->config;
    }

    public static function di(): Container
    {
        return self::getInstance()->di;
    }
}
