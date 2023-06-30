<?php

declare(strict_types=1);

namespace Mei\Utilities;

use finfo;
use InvalidArgumentException;
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

    public const USER_AGENT = 'mei-image-server/1.0';

    public static function getSavePath(string $name): string
    {
        $depth = Dispatcher::config('images.depth');
        if ($depth >= 32) {
            throw new InvalidArgumentException('Can not fetch path that is >= 32 levels deep');
        }

        $dir = Dispatcher::config('images.directory');
        for ($i = 0; $i < $depth; ++$i) {
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
            'hash' => hash('sha256', $bindata . Dispatcher::config('app.salt')),
            'md5' => md5($bindata),
            'length' => strlen($bindata)
        ];
    }
}
