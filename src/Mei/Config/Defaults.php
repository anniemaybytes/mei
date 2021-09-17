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
    public const CONFIG = [
        'mode' => 'production', // ensure we fail safely and dont expose sensitive data
        'logs_dir' => UndefinedValue::class,
        'proxy' => false,

        'app.max_filesize' => UndefinedValue::class,
        'app.salt' => UndefinedValue::class,
        'app.strip_exif' => UndefinedValue::class,

        'images.directory' => UndefinedValue::class,
        'images.dept' => UndefinedValue::class,
        'images.name_len' => UndefinedValue::class,

        'api.secret' => UndefinedValue::class,

        'cloudflare.enabled' => false,
        'cloudflare.api' => UndefinedValue::class,
        'cloudflare.zone' => UndefinedValue::class,

        'db.username' => UndefinedValue::class,
        'db.password' => UndefinedValue::class,
        'db.database' => UndefinedValue::class,
        'db.hostname' => 'localhost',
        'db.port' => 3306,
        'db.socket' => '/run/mysqld/mysqld.sock',
    ];
}
