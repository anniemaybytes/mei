<?php

declare(strict_types=1);

namespace Mei\Config;

/**
 * Class Defaults
 *
 * @package Mei\Config
 */
final class Defaults
{
    public const array CONFIG = [
        'mode' => 'production', // ensure we fail safely and dont expose sensitive data
        'logs_dir' => UndefinedValue::class,
        'proxy' => false,

        'images.directory' => UndefinedValue::class,
        'images.max_filesize' => UndefinedValue::class,
        'images.strip_metadata' => UndefinedValue::class,

        'images.legacy.pepper' => UndefinedValue::class,

        'encryption.secret' => UndefinedValue::class,

        'cloudflare.enabled' => false,
        'cloudflare.api' => UndefinedValue::class,
        'cloudflare.zone' => UndefinedValue::class,

        'db.username' => UndefinedValue::class,
        'db.password' => UndefinedValue::class,
        'db.database' => UndefinedValue::class,
        'db.hostname' => '', // must default to empty value to allow configuring only hostname/port or socket
        'db.port' => 3306,
        'db.socket' => '',
    ];
}
