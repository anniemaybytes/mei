<?php

declare(strict_types=1);

namespace Mei\Entity;

/**
 * Class EntityHelper
 *
 * @package Mei\Entity
 */
final class EntityHelper
{
    /**
     * @param object $d
     *
     * @return array
     */
    public static function objectToArray($d): array
    {
        return json_decode(json_encode($d), true);
    }
}
