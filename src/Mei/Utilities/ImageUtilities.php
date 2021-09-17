<?php

declare(strict_types=1);

namespace Mei\Utilities;

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

    public static function getSavePath(string $name, bool $base = true): string
    {
        $depth = Dispatcher::config('images.depth');
        if ($depth >= 32) {
            throw new InvalidArgumentException('Can not fetch path that is >= 32 levels deep');
        }

        $dir = $base ? Dispatcher::config('images.directory') : '';
        for ($i = 0; $i < $depth; ++$i) {
            /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
            $dir .= "/{$name[$i]}";
        }
        return "$dir/$name";
    }

    public static function getImageInfo(string $bindata): array
    {
        $data = @getimagesizefromstring($bindata);
        if (!$data || !isset($data['mime'])) {
            throw new RuntimeException('Unable to read image info on binary data.');
        }

        return [
            'extension' => self::$allowedTypes[$data['mime']] ?? '',
            'mime' => $data['mime'],
            'hash' => hash('sha256', $bindata . Dispatcher::config('app.salt')),
            'md5' => md5($bindata),
            'width' => $data[0],
            'height' => $data[1],
            'size' => strlen($bindata)
        ];
    }
}
