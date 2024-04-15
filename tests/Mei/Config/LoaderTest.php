<?php

declare(strict_types=1);

use Mei\Config\Loader;
use org\bovigo\vfs;

/**
 * Class ConfigLoaderTest
 */
class LoaderTest extends PHPUnit\Framework\TestCase
{
    private vfs\vfsStreamDirectory $root;

    public function setup(): void
    {
        $this->root = vfs\vfsStream::setup(self::class);
        vfs\vfsStream::copyFromFileSystem(__DIR__ . '/config', $this->root);
    }

    /**
     * Config loader should bork if configs are missing
     */
    public function test_should_fail_if_config_missing(): void
    {
        $this->expectException(RuntimeException::class);
        Loader::load($this->root->url() . '/fail.ini');
    }

    /**
     * Config loader should correctly load file
     */
    public function test_check_load_file(): void
    {
        $c = Loader::parse(Loader::load($this->root->url() . '/load.ini'));
        self::assertArrayHasKey('mode', $c);
        self::assertEquals('development', $c['mode']);
    }

    /**
     * Config loader should correctly parse structures
     */
    public function test_parses_simple_ini(): void
    {
        self::assertEquals(
            [
                'mode' => 'development',
                'logs_dir' => '/code/logs',
                'images.strip_metadata' => false,
                'images.max_filesize' => 5242880,
                'images.legacy.pepper' => '[b,W3=Ryrt`:uQ=S',
            ],
            Loader::parse(Loader::load($this->root->url() . '/sample.ini'))
        );
    }
}
