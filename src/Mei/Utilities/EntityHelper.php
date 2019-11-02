<?php

namespace Mei\Utilities;

class EntityHelper
{
    public static function objectToArray($d)
    {
        return json_decode(json_encode($d), true);
    }
}
