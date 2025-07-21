<?php

declare(strict_types=1);

namespace Mei\Utilities;

/**
 * Class SimpleTemplate
 *
 * @package Mei\Utilities
 */
final class SimpleTemplate
{
    public static function render(string $name): string
    {
        ob_start();
        require __DIR__ . "../../Templates/$name.phtml";
        return ob_get_clean();
    }
}
