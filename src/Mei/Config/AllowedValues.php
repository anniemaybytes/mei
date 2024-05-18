<?php

declare(strict_types=1);

namespace Mei\Config;

/**
 * Class Defaults
 *
 * @package Status\Config
 */
final class AllowedValues
{
    public const array CONFIG = [
        'mode' => ['production', 'staging', 'development'],

        'images.strip_metadata' => [true, false],

        'cloudflare.enabled' => [true, false],
    ];
}
