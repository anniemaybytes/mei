<?php

declare(strict_types=1);

namespace Mei\Utilities;

use finfo;
use Mei\Dispatcher;
use RuntimeException;

/**
 * Class ImageUtilities
 *
 * @package Mei\Utilities
 */
final class ImageUtilities
{
    public static array $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    private const SAVE_DIR_DEPTH = 3;

    public static function getSavePath(string $name): string
    {
        $dir = Dispatcher::config('images.directory');
        for ($i = 0; $i < self::SAVE_DIR_DEPTH; ++$i) {
            /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
            $dir .= "/{$name[$i]}";
        }
        return "$dir/$name";
    }

    /** @return array{mime: string, extension: ?string, hash: string, md5: string, length: int} */
    public static function getImageInfo(string $bindata): array
    {
        if (!$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bindata)) {
            throw new RuntimeException('Unable to determine MIME type of binary stream');
        }

        return [
            'mime' => $mime,
            'extension' => self::$allowedTypes[$mime] ?? null,
            'hash' => hash('sha256', $bindata . Dispatcher::config('images.legacy.pepper')),
            'md5' => md5($bindata),
            'length' => strlen($bindata)
        ];
    }
}
