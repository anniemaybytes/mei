<?php

namespace spec\Mei;

use Mei\ConfigLoader;
use PhpSpec\ObjectBehavior;

class ConfigLoaderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ConfigLoader::class);
    }

    function it_parses_ini()
    {
        $iniString = 'mode = development
logs_dir = logs
proxy = false
timeout = 30

[site]
site_root =
images_root = images
max_filesize = 5242880
salt = "[b,W3=Ryrt`:uQ=S"
errors = true';
        $iniArray = [
            'mode' => 'development',
            'logs_dir' => 'logs',
            'proxy' => false,
            'timeout' => 30,
            'site.site_root' => '',
            'site.images_root' => 'images',
            'site.max_filesize' => 5242880,
            'site.salt' => '[b,W3=Ryrt`:uQ=S',
            'site.errors' => true
        ];
        self::loadString($iniString)->shouldReturn($iniArray);
    }
}
