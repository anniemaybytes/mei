<?php

namespace Mei\Entity;

/**
 * Class EntityHelper
 *
 * @package Mei\Entity
 */
class EntityHelper
{
    /**
     * @param $d
     *
     * @return mixed
     */
    public static function objectToArray($d)
    {
        return json_decode(json_encode($d), true);
    }
}
