<?php

declare(strict_types=1);

use Mei\ConfigLoader;
use org\bovigo\vfs;

/**
 * Class ConfigLoaderTest
 */
class ConfigLoaderTest extends PHPUnit\Framework\TestCase
{
    /**
     * @var vfs\vfsStreamDirectory
     */
    private vfs\vfsStreamDirectory $root;

    public function setup(): void
    {
        $this->root = vfs\vfsStream::setup('configLoaderTest');
    }

    /**
     * Config loader should bork if configs are missing
     */
    public function test_should_fail_if_config_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->root->removeChild('config');
        $str = new vfs\vfsStreamFile('config');
        $this->root->addChild($str);
        ConfigLoader::load(vfs\vfsStream::url('configLoaderTest/config/'));
    }

    /**
     * Config loader should correctly load file
     */
    public function test_check_load_file(): void
    {
        $this->root->removeChild('config');
        vfs\vfsStream::create(
            [
                'config' => [
                    'private.ini' => '
[app]
test = true
',
                ]
            ]
        );
        $c = ConfigLoader::load(vfs\vfsStream::url('configLoaderTest/config/'));
        self::assertArrayHasKey('app.test', $c);
        self::assertEquals(true, $c['app.test']);
    }

    /**
     * Config loader should correctly parse structures
     */
    public function test_parses_ini(): void
    {
        $this->root->removeChild('config');
        vfs\vfsStream::create(
            [
                'config' => [
                    'private.ini' => '
mode = development
logs_dir = logs
proxy = false

[app]
max_filesize = 5242880
salt = "[b,W3=Ryrt`:uQ=S"
',
                ]
            ]
        );
        $c = ConfigLoader::load(vfs\vfsStream::url('configLoaderTest/config/'));
        self::assertEquals(
            [
                'mode' => 'development',
                'logs_dir' => 'logs',
                'proxy' => false,
                'app.max_filesize' => 5242880,
                'app.salt' => '[b,W3=Ryrt`:uQ=S',
            ],
            $c
        );
    }
}
