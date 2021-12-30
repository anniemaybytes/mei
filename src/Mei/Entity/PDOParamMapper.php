<?php

declare(strict_types=1);

namespace Mei\Entity;

use PDO;

/**
 * Class PDOParamMapper
 *
 * @package Mei\Utilities
 */
final class PDOParamMapper
{
    public static function map(string $type): int
    {
        return match ($type) {
            'int' => PDO::PARAM_INT,
            'bool' => PDO::PARAM_BOOL,
            default => PDO::PARAM_STR,
        };
    }
}
