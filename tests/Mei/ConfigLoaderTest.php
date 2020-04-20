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
    private $root;

    public function setUp(): void
    {
        $this->root = vfs\vfsStream::setup('configLoaderTest');
    }

    /**
     * Config loader should bork if configs are missing
     */
    public function testShouldFailIfConfigMissing(): void
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
    public function testCheckLoadFile(): void
    {
        $this->root->removeChild('config');
        vfs\vfsStream::create(
            [
                'config' => [
                    'private.ini' => '[site]
test=true',
                ]
            ]
        );
        $c = ConfigLoader::load(vfs\vfsStream::url('configLoaderTest/config/'));
        $this->assertArrayHasKey('site.test', $c);
        $this->assertEquals(true, $c['site.test']);
    }

    /**
     * Config loader should correctly parse structures
     */
    public function testParsesIni(): void
    {
        $this->root->removeChild('config');
        vfs\vfsStream::create(
            [
                'config' => [
                    'private.ini' => 'mode = development
logs_dir = logs
proxy = false
timeout = 30

[site]
site_root =
images_root = images
max_filesize = 5242880
salt = "[b,W3=Ryrt`:uQ=S"
errors = true',
                ]
            ]
        );
        $c = ConfigLoader::load(vfs\vfsStream::url('configLoaderTest/config/'));
        $this->assertEquals(
            [
                'mode' => 'development',
                'logs_dir' => 'logs',
                'proxy' => false,
                'timeout' => 30,
                'site.site_root' => '',
                'site.images_root' => 'images',
                'site.max_filesize' => 5242880,
                'site.salt' => '[b,W3=Ryrt`:uQ=S',
                'site.errors' => true
            ],
            $c
        );
    }
}
