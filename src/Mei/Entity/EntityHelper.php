<?php

namespace Mei\Entity;

class EntityHelper
{
    public static function objectToArray($d)
    {
        return json_decode(json_encode($d), true);
    }
}
